<?php

declare(strict_types=1);

namespace Nexum\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Nexum\Module\ModuleRegistryInterface;
use Nexum\Tests\Support\SourceWorkspace;
use Nexum\Tests\Support\TestApplicationKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ModuleLayerTest extends TestCase
{
    private SourceWorkspace $source;
    private ?TestApplicationKernel $kernel = null;

    protected function setUp(): void
    {
        $this->source = new SourceWorkspace();
        $this->source->write('config/packages/framework.yaml', "framework:\n    secret: test\n    test: true\n    trusted_hosts: ['.*']\n    router: { utf8: true }\n");
        $this->source->write('config/services.yaml', 'services: {}');
        $this->source->write('config/routes.yaml', "nexum:\n    resource: .\n    type: nexum\n");
    }

    protected function tearDown(): void
    {
        $this->kernel?->shutdown();
        $this->source->remove();
    }

    public function testEmptyNexumConfigWorksWithoutDoctrineOrFoundation(): void
    {
        $module = $this->source->php('src/Module/Hello/HelloModule.php', '#[\\Nexum\\Module\\Attribute\\NexumModule] final class HelloModule {}');
        $this->source->php('src/Module/Hello/Controller/HelloController.php', 'final class HelloController { #[\\Symfony\\Component\\Routing\\Attribute\\Route("/hello", name: "hello")] public function hello(): \\Symfony\\Component\\HttpFoundation\\Response { return new \\Symfony\\Component\\HttpFoundation\\Response("hello"); } }');
        $this->kernel = new TestApplicationKernel($this->source);
        $this->kernel->boot();
        $response = $this->kernel->handle(Request::create('/hello'));
        self::assertSame('hello', $response->getContent());
        self::assertFalse($response->headers->has('X-Request-Id'));
        $registry = $this->kernel->getContainer()->get(ModuleRegistryInterface::class);
        self::assertInstanceOf(ModuleRegistryInterface::class, $registry);
        self::assertTrue($registry->get($module)->enabled);
    }

    public function testDoctrineOnlyMapsActiveModulesAndKeepsNativeMapping(): void
    {
        $live = $this->source->php('src/Module/Live/LiveModule.php', '#[\\Nexum\\Module\\Attribute\\NexumModule] final class LiveModule {}');
        $this->source->php('src/Module/Off/OffModule.php', '#[\\Nexum\\Module\\Attribute\\NexumModule] final class OffModule {}');
        $body = '#[\\Doctrine\\ORM\\Mapping\\Entity] final class Record { #[\\Doctrine\\ORM\\Mapping\\Id] #[\\Doctrine\\ORM\\Mapping\\Column(type: "integer")] public int $id = 1; }';
        $active = $this->source->php('src/Module/Live/Entity/Record.php', $body);
        $disabled = $this->source->php('src/Module/Off/Entity/Record.php', $body);
        $unused = $this->source->php('src/Module/Unused/Entity/Record.php', $body);
        $this->source->php('src/Module/Unused/UnusedModule.php', '#[\\Nexum\\Module\\Attribute\\NexumModule] final class UnusedModule {}');
        $legacy = $this->source->php('src/Entity/Record.php', $body);
        $this->source->php('src/Project/Shop/ShopProject.php', '#[\\Nexum\\Project\\Attribute\\NexumProject(modules: [\\'.$live.'::class])] final class ShopProject {}');
        $this->source->write('config/packages/nexum.yaml', "nexum:\n    modules:\n        off: { enabled: false }\n");
        $this->source->write('config/packages/doctrine.yaml', "doctrine:\n    dbal: { url: 'sqlite:///:memory:' }\n    orm:\n        auto_mapping: false\n        mappings:\n            Legacy:\n                type: attribute\n                is_bundle: false\n                dir: '%kernel.project_dir%/src/Entity'\n                prefix: '".$this->source->namespace."\\Entity'\n");
        $this->kernel = new TestApplicationKernel($this->source, true);
        $this->kernel->boot();
        $manager = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        $classes = array_map(static fn ($metadata): string => $metadata->getName(), $manager->getMetadataFactory()->getAllMetadata());
        self::assertContains($active, $classes);
        self::assertContains($legacy, $classes);
        self::assertNotContains($disabled, $classes);
        self::assertNotContains($unused, $classes);
        self::assertCount(2, $classes);
    }

    public function testNativeServiceOverrideWinsOverModuleDefaults(): void
    {
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\\Nexum\\Module\\Attribute\\NexumModule] final class HelloModule {}');
        $service = $this->source->php('src/Module/Hello/Greeting.php', 'final class Greeting { public function __construct(public string $text = "default") {} }');
        $this->source->write('config/services.yaml', "services:\n    '".$service."':\n        public: true\n        arguments: ['native']\n");
        $this->source->write('config/packages/nexum.yaml', "nexum: {}\n");
        $this->kernel = new TestApplicationKernel($this->source);
        $this->kernel->boot();
        /** @var object{text: string} $greeting */
        $greeting = $this->kernel->getContainer()->get($service);
        self::assertSame('native', $greeting->text);
    }

    public function testArchitectureCommandIncludesInactiveCatalogAndJsonEvidence(): void
    {
        $this->source->php('src/Module/Off/OffModule.php', '#[\\Nexum\\Module\\Attribute\\NexumModule] final class OffModule {}');
        $this->source->write('config/packages/nexum.yaml', "nexum:\n    modules:\n        off: { enabled: false }\n");
        $this->kernel = new TestApplicationKernel($this->source);
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($this->kernel);
        $tester = new \Symfony\Component\Console\Tester\CommandTester($application->find('nexum:architecture'));
        self::assertSame(0, $tester->execute(['--format' => 'json']));
        $data = json_decode($tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('off', $data['modules'][0]['id']);
        self::assertFalse($data['modules'][0]['enabled']);
        self::assertFalse($data['modules'][0]['active']);
        self::assertSame('disabled', $data['modules'][0]['inactive_reason']);
        self::assertSame([], $data['modules'][0]['observed_dependencies']);
    }

    public function testExcludedCustomRootControllerDoesNotLeakIntoRoutes(): void
    {
        $this->source->php('src/Domain/Hello/Hello.php', '#[\\Nexum\\Module\\Attribute\\NexumModule(id: "hello")] final class Hello {}');
        $controller = '#[\\Symfony\\Component\\DependencyInjection\\Attribute\\Autoconfigure(public: true)] final class Endpoint { #[\\Symfony\\Component\\Routing\\Attribute\\Route("/hidden", name: "hidden")] public function run(): \\Symfony\\Component\\HttpFoundation\\Response { return new \\Symfony\\Component\\HttpFoundation\\Response("hidden"); } }';
        $endpoint = $this->source->php('src/Domain/Hello/Http/Hidden/Endpoint.php', $controller);
        $this->source->write('config/packages/nexum.yaml', "nexum:\n    discovery:\n        modules:\n            - path: src/Domain\n              excludes: ['Hello/Http/Hidden/**']\n    integrations:\n        controllers: [Http]\n");
        $this->kernel = new TestApplicationKernel($this->source);
        $this->kernel->boot();
        $router = $this->kernel->getContainer()->get('router');
        self::assertInstanceOf(\Symfony\Component\Routing\RouterInterface::class, $router);
        self::assertNull($router->getRouteCollection()->get('hidden'));
        self::assertFalse($this->kernel->getContainer()->has($endpoint));
    }

    public function testExcludedEntitiesAndDisabledPrivateAreasDoNotLeakThroughParentMappings(): void
    {
        $this->source->php('src/Project/Shop/ShopProject.php', '#[\\Nexum\\Project\\Attribute\\NexumProject] final class ShopProject {}');
        $this->source->php('src/Project/Shop/Entity/Private/OffModule.php', '#[\\Nexum\\Module\\Attribute\\NexumModule(id: "off")] final class OffModule {}');
        $body = '#[\\Doctrine\\ORM\\Mapping\\Entity] final class Record { #[\\Doctrine\\ORM\\Mapping\\Id] #[\\Doctrine\\ORM\\Mapping\\Column(type: "integer")] public int $id = 1; }';
        $live = $this->source->php('src/Project/Shop/Entity/Record.php', $body);
        $off = $this->source->php('src/Project/Shop/Entity/Private/Entity/Record.php', $body);
        $hidden = $this->source->php('src/Project/Shop/Entity/Hidden/Record.php', $body);
        $this->source->write('config/packages/nexum.yaml', "nexum:\n    discovery:\n        excludes: ['Project/Shop/Entity/Hidden/**']\n    modules:\n        'project:shop:off': { enabled: false }\n");
        $this->source->write('config/packages/doctrine.yaml', "doctrine:\n    dbal: { url: 'sqlite:///:memory:' }\n    orm: { auto_mapping: false }\n");
        $this->kernel = new TestApplicationKernel($this->source, true);
        $this->kernel->boot();
        $manager = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        $classes = array_map(static fn ($metadata): string => $metadata->getName(), $manager->getMetadataFactory()->getAllMetadata());
        self::assertContains($live, $classes);
        self::assertNotContains($off, $classes);
        self::assertNotContains($hidden, $classes);
        self::assertCount(1, $classes);
    }

    public function testBuildTimeEnvironmentActivationIsResolvedBeforeCompilation(): void
    {
        $module = $this->source->php('src/Module/Off/OffModule.php', '#[\\Nexum\\Module\\Attribute\\NexumModule] final class OffModule {}');
        $this->source->write('config/packages/nexum.yaml', "nexum:\n    modules:\n        off: { enabled: '%env(bool:NEXUM_TEST_ENABLED)%' }\n");
        $_ENV['NEXUM_TEST_ENABLED'] = '0';
        try {
            $this->kernel = new TestApplicationKernel($this->source);
            $this->kernel->boot();
            $registry = $this->kernel->getContainer()->get(ModuleRegistryInterface::class);
            self::assertInstanceOf(ModuleRegistryInterface::class, $registry);
            self::assertFalse($registry->get($module)->enabled);
            self::assertSame([], $registry->active());
        } finally {
            unset($_ENV['NEXUM_TEST_ENABLED']);
        }
    }

    public function testRoutingResourcesDetectCreationOfMissingDefaultRoot(): void
    {
        $this->kernel = new TestApplicationKernel($this->source);
        $this->kernel->boot();
        $router = $this->kernel->getContainer()->get('router');
        self::assertInstanceOf(\Symfony\Component\Routing\RouterInterface::class, $router);
        $resources = array_filter($router->getRouteCollection()->getResources(), fn ($resource): bool => $resource instanceof \Symfony\Component\Config\Resource\FileExistenceResource && $resource->getResource() === $this->source->directory.'/src/Module');
        self::assertCount(1, $resources);
        $resource = array_values($resources)[0];
        self::assertInstanceOf(\Symfony\Component\Config\Resource\FileExistenceResource::class, $resource);
        self::assertTrue($resource->isFresh(time()));
        $this->source->php('src/Module/NewModule/NewModule.php', '#[\\Nexum\\Module\\Attribute\\NexumModule] final class NewModule {}');
        self::assertFalse($resource->isFresh(time()));
    }

    public function testRoutingResourcesTrackAdditionsAndDeletionsInCustomRoot(): void
    {
        $path = $this->source->directory.'/packages/Modules';
        mkdir($path, 0777, true);
        $this->source->write('config/packages/nexum.yaml', "nexum:\n    discovery:\n        modules:\n            - { path: packages/Modules, namespace: External }\n");
        $this->kernel = new TestApplicationKernel($this->source);
        $this->kernel->boot();
        $router = $this->kernel->getContainer()->get('router');
        self::assertInstanceOf(\Symfony\Component\Routing\RouterInterface::class, $router);
        $resources = array_filter($router->getRouteCollection()->getResources(), static fn ($resource): bool => $resource instanceof \Symfony\Component\Config\Resource\DirectoryResource && $resource->getResource() === $path);
        self::assertCount(1, $resources);
        $resource = array_values($resources)[0];
        self::assertInstanceOf(\Symfony\Component\Config\Resource\DirectoryResource::class, $resource);
        $timestamp = time() + 10;
        self::assertTrue($resource->isFresh($timestamp));
        $this->source->write('packages/Modules/Added.php', '<?php namespace External; final class Added {}');
        touch($path.'/Added.php', $timestamp + 1);
        clearstatcache();
        self::assertFalse($resource->isFresh($timestamp));
        unlink($path.'/Added.php');
        touch($path, $timestamp + 1);
        clearstatcache();
        self::assertFalse($resource->isFresh($timestamp));
    }

    public function testRootLocalExclusionsAlsoApplyToEnclosingProjectServiceImport(): void
    {
        $this->source->php('src/Project/Shop/ShopProject.php', '#[\Nexum\Project\Attribute\NexumProject] final class ShopProject {}');
        $this->source->php('src/Project/Shop/Extension/Hidden/Leak.php', '#[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "leak", public: true)] final class Leak {}');
        $this->source->write('config/packages/nexum.yaml', "nexum:\n    discovery:\n        modules:\n            - path: src/Project/Shop/Extension\n              excludes: ['Hidden/**']\n");
        $this->kernel = new TestApplicationKernel($this->source);
        $this->kernel->boot();
        self::assertFalse($this->kernel->getContainer()->has('leak'));
    }

    public function testRoutingResourcesInvalidateWhenArchitectureConfigOrComposerChanges(): void
    {
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Nexum\Module\Attribute\NexumModule] final class HelloModule {}');
        $this->source->write('config/packages/nexum.yaml', "nexum: {}\n");
        $this->kernel = new TestApplicationKernel($this->source);
        $this->kernel->boot();
        $router = $this->kernel->getContainer()->get('router');
        self::assertInstanceOf(\Symfony\Component\Routing\RouterInterface::class, $router);
        $resources = array_filter($router->getRouteCollection()->getResources(), static fn ($resource): bool => $resource instanceof \Symfony\Component\Config\Resource\DirectoryResource || $resource instanceof \Symfony\Component\Config\Resource\FileResource || $resource instanceof \Symfony\Component\Config\Resource\FileExistenceResource);
        $timestamp = time() + 10;
        foreach (['config/packages/nexum.yaml', 'composer.json'] as $file) {
            $path = $this->source->directory.'/'.$file;
            touch($path, $timestamp + 1);
            clearstatcache();
            self::assertNotEmpty(array_filter($resources, static fn ($resource): bool => !$resource->isFresh($timestamp)), $file.' must invalidate the route cache.');
            touch($path, $timestamp - 1);
            clearstatcache();
        }
    }
}
