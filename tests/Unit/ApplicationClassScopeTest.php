<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\DiscoveryOptions;
use Sillbeam\Core\Architecture\DiscoveryOptionsResolver;
use Sillbeam\Core\Architecture\DiscoveryRoot;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ApplicationClassScopeTest extends TestCase
{
    private SourceWorkspace $source;

    protected function setUp(): void
    {
        $this->source = new SourceWorkspace();
    }

    protected function tearDown(): void
    {
        $this->source->remove();
    }

    public function testExternalReferencesNeverProduceUnknownClassFindings(): void
    {
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule(dependencies: [\Unknown\UnknownModule::class])] final class HelloModule {}');
        $missing = $this->source->namespace.'\Module\Missing\MissingModule';
        $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer {
public function __construct(public \\'.$missing.' $missing, public \Psr\Log\LoggerInterface $logger, public \ExternalPackage\Unresolved $external) {}
public function response(): \Symfony\Component\HttpFoundation\Response { return new \Symfony\Component\HttpFoundation\Response(); }
public function other(): \Symfony\Component\HttpFoundation\Response { return new \Symfony\Component\HttpFoundation\Response(); }
}');
        $result = (new ArchitectureAnalyzer())->analyze($this->options());
        self::assertSame(['code.unknown_class', 'dependency.unknown'], array_column($result->findings, 'rule'));
        self::assertSame($missing, $result->findings[0]->context['class']);
        self::assertSame(2, $result->findings[0]->context['line']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testSymfonyResponseIsNotAnErrorWithWrappedComposerAutoloaders(): void
    {
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
        $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer {
public function response(): \Symfony\Component\HttpFoundation\Response { return new \Symfony\Component\HttpFoundation\Response(); }
public function other(): \Symfony\Component\HttpFoundation\Response { return new \Symfony\Component\HttpFoundation\Response(); }
}');
        $options = $this->options();
        self::assertFalse(class_exists(\Symfony\Component\HttpFoundation\Response::class, false));
        $autoloaders = spl_autoload_functions();
        $wrappers = [];
        foreach ($autoloaders as $autoload) {
            spl_autoload_unregister($autoload);
            $wrapper = static function (string $class) use ($autoload): void { $autoload($class); };
            $wrappers[] = $wrapper;
            spl_autoload_register($wrapper);
        }
        try {
            $result = (new ArchitectureAnalyzer())->analyze($options);
            self::assertSame([], $result->findings);
            self::assertFalse(class_exists(\Symfony\Component\HttpFoundation\Response::class, false));
        } finally {
            foreach ($wrappers as $wrapper) {
                spl_autoload_unregister($wrapper);
            }
            foreach ($autoloaders as $autoload) {
                spl_autoload_register($autoload);
            }
        }
    }

    public function testComposerApplicationNamespacesOutsideDiscoveryAndDevMappingsAreChecked(): void
    {
        $custom = 'Business'.bin2hex(random_bytes(4));
        $dev = 'Development'.bin2hex(random_bytes(4));
        $this->source->write('composer.json', json_encode([
            'autoload' => ['psr-4' => [$this->source->namespace.'\\' => 'src/', $custom.'\\' => ['lib/', 'other-lib/']]],
            'autoload-dev' => ['psr-4' => [$dev.'\\' => 'tests/']],
        ], \JSON_THROW_ON_ERROR));
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
        $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer { public function __construct(public \\'.$custom.'\Missing $custom, public \\'.$dev.'\Missing $dev) {} }');
        $result = (new ArchitectureAnalyzer())->analyze($this->options());
        self::assertSame([$custom.'\Missing', $dev.'\Missing'], array_column(array_column($result->findings, 'context'), 'class'));
    }

    public function testExistingApplicationFileInCustomMappingDoesNotNeedLoading(): void
    {
        $namespace = 'Local'.bin2hex(random_bytes(4));
        $this->source->write('composer.json', json_encode([
            'autoload' => ['psr-4' => [$this->source->namespace.'\\' => 'src/', $namespace.'\\' => 'lib/']],
        ], \JSON_THROW_ON_ERROR));
        $this->source->write('lib/Mail.php', '<?php namespace '.$namespace.'; throw new \\RuntimeException("Do not execute"); class Mail {}');
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
        $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer { public \\'.$namespace.'\\Mail $mail; }');
        self::assertTrue((new ArchitectureAnalyzer())->analyze($this->options())->isValid());
        self::assertFalse(class_exists($namespace.'\\Mail', false));
    }

    public function testApplicationComposerMappingsIntoCustomVendorDirectoryAreExcluded(): void
    {
        $namespace = 'Package'.bin2hex(random_bytes(4));
        $this->source->write('composer.json', json_encode([
            'config' => ['vendor-dir' => 'dependencies'],
            'autoload' => ['psr-4' => [$this->source->namespace.'\\' => 'src/', $namespace.'\\' => 'dependencies/package/']],
        ], \JSON_THROW_ON_ERROR));
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
        $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer { public \\'.$namespace.'\\Unavailable $external; }');
        self::assertTrue((new ArchitectureAnalyzer())->analyze($this->options())->isValid());
    }

    public function testExplicitDiscoveryNamespaceIsApplicationOwnedWithoutComposerMapping(): void
    {
        $namespace = 'Custom'.bin2hex(random_bytes(4)).'\Modules';
        $this->source->write('custom/Hello/HelloModule.php', '<?php namespace '.$namespace.'\Hello; #[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule { public function __construct(public \\'.$namespace.'\Missing $missing) {} }');
        $options = new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\', moduleRoots: [new DiscoveryRoot($this->source->directory.'/custom', $namespace.'\\')]);
        $result = (new ArchitectureAnalyzer())->analyze($options);
        self::assertCount(1, $result->findings);
        self::assertSame($namespace.'\Missing', $result->findings[0]->context['class']);
    }

    public function testMoreSpecificVendorMappingAndNamespaceLookalikesAreNotApplicationOwned(): void
    {
        $prefix = $this->source->namespace.'\External\\';
        $loader = new \Composer\Autoload\ClassLoader();
        $loader->addPsr4($prefix, $this->source->directory.'/vendor/package');
        $loader->register(true);
        try {
            $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
            $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer { public function __construct(public \\'.$prefix.'Missing $vendor, public \\'.$this->source->namespace.'Extra\Missing $lookalike) {} }');
            self::assertTrue((new ArchitectureAnalyzer())->analyze($this->options())->isValid());
        } finally {
            $loader->unregister();
        }
    }

    public function testMostSpecificLocalMappingIsNotHiddenByBroaderVendorMapping(): void
    {
        $prefix = $this->source->namespace.'\\Module\\Adapter\\';
        $missing = $prefix.'Local\\Missing';
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
        $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer { public \\'.$missing.' $missing; }');
        foreach ([false, true] as $reverse) {
            $mappings = [
                $prefix => $this->source->directory.'/vendor/adapter',
                $prefix.'Local\\' => $this->source->directory.'/src/Module/Adapter/Local',
            ];
            $loader = new \Composer\Autoload\ClassLoader();
            foreach ($reverse ? array_reverse($mappings, true) : $mappings as $namespace => $path) {
                $loader->addPsr4($namespace, $path);
            }
            $loader->register(true);
            try {
                $result = (new ArchitectureAnalyzer())->analyze($this->options());
                self::assertCount(1, $result->findings);
                self::assertSame('code.unknown_class', $result->findings[0]->rule);
                self::assertSame($missing, $result->findings[0]->context['class']);
            } finally {
                $loader->unregister();
            }
        }
    }

    public function testDuplicateUsesOnOneLineAreCombinedButSeparateLocationsAreRetained(): void
    {
        $missing = $this->source->namespace.'\Module\Missing\Type';
        $this->source->php('src/Module/Hello/HelloModule.php', '#[\Sillbeam\Core\Module\Attribute\SillbeamModule] final class HelloModule {}');
        $this->source->php('src/Module/Hello/Consumer.php', 'class Consumer {
public function run(\\'.$missing.' $value): \\'.$missing.' { return new \\'.$missing.'(); }
public function other(): \\'.$missing.' { return new \\'.$missing.'(); }
}');
        $result = (new ArchitectureAnalyzer())->analyze($this->options());
        self::assertSame([2, 3], array_column(array_column($result->findings, 'context'), 'line'));
    }

    private function options(): DiscoveryOptions
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->source->directory);

        return (new DiscoveryOptionsResolver())->resolve($container, []);
    }
}
