<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sillbeam\Module\ModuleRegistryInterface;
use Sillbeam\Tests\Support\SourceWorkspace;
use Sillbeam\Tests\Support\TestApplicationKernel;
use Symfony\Component\Routing\RouterInterface;

final class SymfonySkeletonTest extends TestCase
{
    public function testUnrestrictedNativeImportRemainsAuthoritativeEvenForDisabledModules(): void
    {
        $source = new SourceWorkspace();
        $kernel = new TestApplicationKernel($source);
        try {
            $source->php('src/Module/Off/OffModule.php', '#[\Sillbeam\Module\Attribute\SillbeamModule] final class OffModule {}');
            $service = $source->php('src/Module/Off/Service.php', '#[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "native.off", public: true)] final class Service {}');
            $source->write('config/packages/framework.yaml', 'framework: { secret: test }');
            $source->write('config/packages/sillbeam.yaml', 'sillbeam: { modules: { off: { enabled: false } } }');
            $source->write('config/services.yaml', $this->services($source, false));
            $kernel->boot();
            $registry = $kernel->getContainer()->get(ModuleRegistryInterface::class);
            self::assertInstanceOf(ModuleRegistryInterface::class, $registry);
            self::assertSame([], $registry->active());
            self::assertInstanceOf($service, $kernel->getContainer()->get('native.off'));
        } finally {
            $kernel->shutdown();
            $source->remove();
        }
    }

    public function testZeroConfigurationBundleBootsWithoutAPackageConfigurationFile(): void
    {
        $source = new SourceWorkspace();
        $kernel = new TestApplicationKernel($source);
        try {
            $source->php('src/Module/Hello/HelloModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class HelloModule {}');
            $source->write('config/packages/framework.yaml', 'framework: { secret: test }');
            $source->write('config/services.yaml', $this->services($source, true));
            $kernel->boot();
            $registry = $kernel->getContainer()->get(ModuleRegistryInterface::class);
            self::assertInstanceOf(ModuleRegistryInterface::class, $registry);
            self::assertCount(1, $registry->active());
            self::assertFileDoesNotExist($source->directory.'/config/packages/sillbeam.yaml');
        } finally {
            $kernel->shutdown();
            $source->remove();
        }
    }

    public function testExcludedProjectRootDoesNotRegisterItsIntegrations(): void
    {
        $source = new SourceWorkspace();
        $kernel = new TestApplicationKernel($source, true);
        try {
            $source->php('src/Project/Hidden/HiddenProject.php', '#[\\Sillbeam\\Project\\Attribute\\SillbeamProject] final class HiddenProject {}');
            $source->php('src/Project/Hidden/Leak.php', '#[\\Symfony\\Component\\DependencyInjection\\Attribute\\AsAlias(id: "hidden.leak", public: true)] final class Leak {}');
            $source->php('src/Project/Hidden/Controller/Endpoint.php', 'final class Endpoint { #[\\Symfony\\Component\\Routing\\Attribute\\Route("/hidden", name: "hidden")] public function run(): void {} }');
            $source->php('src/Project/Hidden/Entity/Record.php', '#[\\Doctrine\\ORM\\Mapping\\Entity] final class Record { #[\\Doctrine\\ORM\\Mapping\\Id] #[\\Doctrine\\ORM\\Mapping\\Column] public int $id = 1; }');
            $source->write('config/packages/framework.yaml', 'framework: { secret: test, router: { utf8: true } }');
            $source->write('config/packages/doctrine.yaml', "doctrine: { dbal: { url: 'sqlite:///:memory:' }, orm: { auto_mapping: false } }");
            $source->write('config/packages/sillbeam.yaml', 'sillbeam: { discovery: { excludes: [Project] } }');
            $source->write('config/services.yaml', $this->services($source, true));
            $source->write('config/routes.yaml', 'sillbeam: { resource: ., type: sillbeam }');
            $kernel->boot();
            $container = $kernel->getContainer();
            self::assertFalse($container->has('hidden.leak'));
            $router = $container->get('router');
            self::assertInstanceOf(RouterInterface::class, $router);
            self::assertCount(0, $router->getRouteCollection());
            $manager = $container->get('doctrine.orm.entity_manager');
            self::assertInstanceOf(\Doctrine\ORM\EntityManagerInterface::class, $manager);
            self::assertSame([], $manager->getMetadataFactory()->getAllMetadata());
        } finally {
            $kernel->shutdown();
            $source->remove();
        }
    }

    /** @return iterable<string, array{string, bool}> */
    public static function environments(): iterable
    {
        yield 'development' => ['dev', true];
        yield 'production' => ['prod', false];
    }

    #[DataProvider('environments')]
    public function testSkeletonExclusionsPreserveNativeServicesOverridesAndAttributes(string $environment, bool $debug): void
    {
        $source = new SourceWorkspace();
        $kernel = new TestApplicationKernel($source, false, $environment, $debug);
        try {
            $live = $source->php('src/Module/Live/LiveModule.php', '#[\Sillbeam\Module\Attribute\SillbeamModule] final class LiveModule {}');
            $source->php('src/Module/Off/OffModule.php', '#[\Sillbeam\Module\Attribute\SillbeamModule] final class OffModule {}');
            $source->php('src/Module/Unused/UnusedModule.php', '#[\Sillbeam\Module\Attribute\SillbeamModule] final class UnusedModule {}');
            $source->php('src/Project/Shop/ShopProject.php', '#[\Sillbeam\Project\Attribute\SillbeamProject(modules: [\\'.$live.'::class])] final class ShopProject {}');
            $source->php('src/Project/Shop/Module/Off/OffModule.php', '#[\Sillbeam\Module\Attribute\SillbeamModule] final class OffModule {}');
            foreach (['Module/Off', 'Module/Unused', 'Project/Shop/Module/Off'] as $index => $area) {
                $source->php('src/'.$area.'/Leak.php', '#[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "leak.'.$index.'", public: true)] final class Leak {}');
                $source->php('src/'.$area.'/Controller/Endpoint.php', 'final class Endpoint { #[\Symfony\Component\Routing\Attribute\Route("/leak'.$index.'", name: "leak'.$index.'")] public function run(): void {} }');
            }
            $port = $source->php('src/Module/Live/Port.php', 'interface Port {}');
            $source->php('src/Module/Live/Adapter.php', '#[\Symfony\Component\DependencyInjection\Attribute\AsAlias] final class Adapter implements Port {}');
            $native = $source->php('src/NativeAdapter.php', 'final class NativeAdapter implements \\'.$port.' {}');
            $consumer = $source->php('src/Module/Live/Consumer.php', 'final class Consumer { public function __construct(public Port $port, public string $text = "default") {} }');
            $source->php('src/Module/Live/Excluded.php', '#[\Symfony\Component\DependencyInjection\Attribute\Exclude] #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "excluded", public: true)] final class Excluded {}');
            $source->php('src/Module/Live/DevOnly.php', '#[\Symfony\Component\DependencyInjection\Attribute\When("dev")] #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "dev.only", public: true)] final class DevOnly {}');
            $source->php('src/Module/Live/NotProd.php', '#[\Symfony\Component\DependencyInjection\Attribute\WhenNot("prod")] #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "not.prod", public: true)] final class NotProd {}');
            $source->php('src/Legacy.php', '#[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "legacy", public: true)] final class Legacy {}');
            $source->write('config/packages/framework.yaml', 'framework: { secret: test, router: { utf8: true } }');
            $source->write('config/packages/sillbeam.yaml', 'sillbeam: { modules: { off: { enabled: false }, "project:shop:off": { enabled: false } } }');
            $source->write('config/routes.yaml', "sillbeam: { resource: ., type: sillbeam }\n");
            $source->write('config/services.yaml', $this->services($source, true)."    '".$port."': { alias: '".$native."' }\n    '".$consumer."': { public: true, autowire: true, arguments: { \$text: native } }\n");
            $kernel->boot();
            $container = $kernel->getContainer();
            /** @var object{port: object, text: string} $instance */
            $instance = $container->get($consumer);
            self::assertInstanceOf($native, $instance->port);
            self::assertSame('native', $instance->text);
            self::assertTrue($container->has('legacy'));
            self::assertFalse($container->has('excluded'));
            self::assertSame('dev' === $environment, $container->has('dev.only'));
            self::assertSame('dev' === $environment, $container->has('not.prod'));
            $router = $container->get('router');
            self::assertInstanceOf(RouterInterface::class, $router);
            for ($index = 0; $index < 3; ++$index) {
                self::assertFalse($container->has('leak.'.$index));
                self::assertNull($router->getRouteCollection()->get('shop.leak'.$index));
            }
            $kernel->shutdown();
            // Boot the same dumped container again to exercise the warm cache.
            $kernel->boot();
            /** @var object{text: string} $warmInstance */
            $warmInstance = $kernel->getContainer()->get($consumer);
            self::assertSame('native', $warmInstance->text);
        } finally {
            $kernel->shutdown();
            $source->remove();
        }
    }

    #[DataProvider('environments')]
    public function testDevelopmentDirectoriesAreNotAutomaticallyRegistered(string $environment, bool $debug): void
    {
        $source = new SourceWorkspace();
        $kernel = new TestApplicationKernel($source, false, $environment, $debug);
        try {
            $module = $source->php('src/Module/Live/LiveModule.php', '#[\Sillbeam\Module\Attribute\SillbeamModule] final class LiveModule {}');
            $source->php('src/Project/Shop/ShopProject.php', '#[\Sillbeam\Project\Attribute\SillbeamProject(modules: [\\'.$module.'::class])] final class ShopProject {}');
            $aliases = [];
            foreach (['Module/Live', 'Project/Shop'] as $areaIndex => $area) {
                foreach (['Tests', 'Fixtures', 'Migrations', 'Nested/Tests', 'Nested/Fixtures', 'Nested/Migrations'] as $index => $directory) {
                    $alias = 'development.'.$areaIndex.'.'.$index;
                    $parent = str_ends_with($directory, 'Tests') ? ' extends \PHPUnit\Framework\TestCase' : '';
                    $source->php('src/'.$area.'/'.$directory.'/Helper.php', '#[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "'.$alias.'", public: true)] final class Helper'.$parent.' {}');
                    $aliases[] = $alias;
                }
            }
            $fixture = $source->php('src/Module/Live/Fixtures/Manual.php', 'final class Manual { public function __construct(public string $value) {} }');
            $source->write('config/packages/framework.yaml', 'framework: { secret: test }');
            $source->write('config/services.yaml', $this->services($source, true)."    '".$fixture."': { public: true, arguments: { \$value: explicit } }\n");
            $kernel->boot();
            foreach ($aliases as $alias) {
                self::assertFalse($kernel->getContainer()->has($alias), $alias);
            }
            /** @var object{value: string} $manual */
            $manual = $kernel->getContainer()->get($fixture);
            self::assertSame('explicit', $manual->value);
        } finally {
            $kernel->shutdown();
            $source->remove();
        }
    }

    private function services(SourceWorkspace $source, bool $excludeAreas): string
    {
        return "services:\n    _defaults: { autowire: true, autoconfigure: true }\n    '".$source->namespace."\\':\n        resource: '../src/'\n        exclude: '../src/{DependencyInjection,Entity,Kernel.php".($excludeAreas ? ',Module,Project' : '')."}'\n";
    }
}
