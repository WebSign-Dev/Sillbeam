<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\DependencyInjection\ArchitectureServiceLoader;
use Sillbeam\Tests\Support\SourceWorkspace;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ArchitectureServiceLoaderTest extends TestCase
{
    public function testNativeExclusionsAndEnvironmentRulesAreHonored(): void
    {
        $source = new SourceWorkspace();
        try {
            $excluded = $source->php('src/Module/Test/Data.php', '#[\\Symfony\\Component\\DependencyInjection\\Attribute\\Exclude] final class Data {}');
            $dev = $source->php('src/Module/Test/Dev.php', '#[\\Symfony\\Component\\DependencyInjection\\Attribute\\When("dev")] final class Dev {}');
            $live = $source->php('src/Module/Test/Live.php', 'final class Live {}');
            $container = new ContainerBuilder();
            $container->setParameter('kernel.environment', 'prod');
            (new ArchitectureServiceLoader())->load($container, $source->directory.'/src/Module/Test', $source->namespace.'\\Module\\Test', 'missing');
            self::assertTrue($container->getDefinition($excluded)->hasTag('container.excluded'));
            self::assertTrue($container->getDefinition($dev)->hasTag('container.excluded'));
            $container->getDefinition($live)->setPublic(true);
            $container->compile();
            self::assertTrue($container->has($live));
            self::assertFalse($container->has($excluded));
            self::assertFalse($container->has($dev));
        } finally {
            $source->remove();
        }
    }

    public function testSingleInterfaceImplementationIsAutowired(): void
    {
        $source = new SourceWorkspace();
        try {
            $source->php('src/Module/Test/Port.php', 'interface Port {}');
            $adapter = $source->php('src/Module/Test/Adapter.php', 'final class Adapter implements Port {}');
            $consumer = $source->php('src/Module/Test/Consumer.php', 'final class Consumer { public function __construct(public Port $port) {} }');
            $container = new ContainerBuilder();
            (new ArchitectureServiceLoader())->load($container, $source->directory.'/src/Module/Test', $source->namespace.'\\Module\\Test', 'missing');
            $container->getDefinition($consumer)->setPublic(true);
            $container->compile();
            /** @var object{port: object} $instance */
            $instance = $container->get($consumer);
            self::assertInstanceOf($adapter, $instance->port);
        } finally {
            $source->remove();
        }
    }

    public function testAutowiringAcrossAreasSeesAllImplementationsBeforeCreatingAliases(): void
    {
        $source = new SourceWorkspace();
        try {
            $port = $source->php('src/Module/Contract/Port.php', 'interface Port {}');
            $adapter = $source->php('src/Module/Adapter/Adapter.php', 'final class Adapter implements \\'.$port.' {}');
            $container = new ContainerBuilder();
            $loader = new ArchitectureServiceLoader();
            $loader->load($container, $source->directory.'/src/Module/Contract', $source->namespace.'\\Module\\Contract', 'missing', false, [], true);
            $loader->load($container, $source->directory.'/src/Module/Adapter', $source->namespace.'\\Module\\Adapter', 'missing', false, [], true);
            $loader->finish();
            self::assertSame($adapter, (string) $container->getAlias($port));
        } finally {
            $source->remove();
        }
    }

    public function testAmbiguousImplementationsDoNotCreateAnAutomaticAlias(): void
    {
        $source = new SourceWorkspace();
        try {
            $port = $source->php('src/Module/Contract/Port.php', 'interface Port {}');
            $source->php('src/Module/First/Adapter.php', 'final class Adapter implements \\'.$port.' {}');
            $source->php('src/Module/Second/Adapter.php', 'final class Adapter implements \\'.$port.' {}');
            $container = new ContainerBuilder();
            $loader = new ArchitectureServiceLoader();
            foreach (['Contract', 'First', 'Second'] as $area) {
                $loader->load($container, $source->directory.'/src/Module/'.$area, $source->namespace.'\\Module\\'.$area, 'missing', false, [], true);
            }
            $loader->finish();
            self::assertFalse($container->hasAlias($port));
        } finally {
            $source->remove();
        }
    }

    public function testExplicitNativeAliasAndWhenNotAreHonored(): void
    {
        $source = new SourceWorkspace();
        try {
            $port = $source->php('src/Module/Test/Port.php', 'interface Port {}');
            $adapter = $source->php('src/Module/Test/Adapter.php', '#[\Symfony\Component\DependencyInjection\Attribute\AsAlias(public: true)] final class Adapter implements Port {}');
            $excluded = $source->php('src/Module/Test/TestOnly.php', '#[\Symfony\Component\DependencyInjection\Attribute\WhenNot("prod")] final class TestOnly {}');
            $source->php('src/Module/Test/OtherAdapter.php', 'final class OtherAdapter implements Port {}');
            $container = new ContainerBuilder();
            $container->setParameter('kernel.environment', 'prod');
            (new ArchitectureServiceLoader())->load($container, $source->directory.'/src/Module/Test', $source->namespace.'\Module\Test', 'missing');
            $container->compile();
            self::assertInstanceOf($adapter, $container->get($port));
            self::assertFalse($container->has($excluded));
        } finally {
            $source->remove();
        }
    }
}
