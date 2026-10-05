<?php

declare(strict_types=1);

namespace Nexum\Tests\Unit;

use Nexum\Architecture\DiscoveryOptionsResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

final class DiscoveryOptionsResolverTest extends TestCase
{
    public function testRelativeRootIsResolvedAgainstApplicationDirectory(): void
    {
        $source = new \Nexum\Tests\Support\SourceWorkspace();
        try {
            $container = new ContainerBuilder();
            $container->setParameter('kernel.project_dir', $source->directory);
            $options = (new DiscoveryOptionsResolver())->resolve($container, [['discovery' => ['root' => 'src']]]);
            self::assertSame($source->directory.'/src', $options->root);
            self::assertSame($source->namespace.'\\', $options->namespace);
        } finally {
            $source->remove();
        }
    }

    public function testSelectsTheMostSpecificMappingThatCoversTheRoot(): void
    {
        $directory = sys_get_temp_dir().'/nexum-discovery-'.bin2hex(random_bytes(6));
        $filesystem = new Filesystem();
        try {
            $filesystem->mkdir([$directory.'/src', $directory.'/legacy']);
            $filesystem->dumpFile($directory.'/composer.json', json_encode([
                'autoload' => ['psr-4' => ['App\\' => 'src/', 'Legacy\\' => 'legacy/']],
            ], \JSON_THROW_ON_ERROR));
            $container = new ContainerBuilder();
            $container->setParameter('kernel.project_dir', $directory);

            $options = (new DiscoveryOptionsResolver())->resolve($container, []);
            self::assertSame($directory.'/src', $options->root);
            self::assertSame('App\\', $options->namespace);
        } finally {
            $filesystem->remove($directory);
        }
    }

    public function testExplicitNamespaceResolvesAnOtherwiseAmbiguousRoot(): void
    {
        $directory = sys_get_temp_dir().'/nexum-discovery-'.bin2hex(random_bytes(6));
        $filesystem = new Filesystem();
        try {
            $filesystem->mkdir($directory.'/src');
            $container = new ContainerBuilder();
            $container->setParameter('kernel.project_dir', $directory);
            $options = (new DiscoveryOptionsResolver())->resolve($container, [[
                'discovery' => ['root' => '%kernel.project_dir%/src', 'namespace' => 'Domain\\'],
            ]]);

            self::assertSame('Domain\\', $options->namespace);
        } finally {
            $filesystem->remove($directory);
        }
    }

    public function testLayeredListsUseSymfonyConfigurationMergeSemantics(): void
    {
        $source = new \Nexum\Tests\Support\SourceWorkspace();
        try {
            $source->php('src/One/First/First.php', '#[\Nexum\Module\Attribute\NexumModule] final class First {}');
            $source->php('src/Two/Second/Second.php', '#[\Nexum\Module\Attribute\NexumModule] final class Second {}');
            $container = new ContainerBuilder();
            $container->setParameter('kernel.project_dir', $source->directory);
            $configs = [
                ['discovery' => ['modules' => [['path' => 'src/One']], 'excludes' => ['Ignored/**']], 'integrations' => ['controllers' => ['Http'], 'entities' => ['Model'], 'services' => ['exclude' => ['Data/**']]]],
                ['discovery' => ['modules' => [['path' => 'src/Two']], 'excludes' => ['Generated/**']], 'integrations' => ['controllers' => ['Endpoints'], 'entities' => ['Records'], 'services' => ['exclude' => ['Dto/**']]]],
            ];
            $options = (new DiscoveryOptionsResolver())->resolve($container, $configs);
            self::assertCount(2, $options->moduleRoots);
            self::assertSame(['Ignored/**', 'Generated/**'], $options->excludes);
            self::assertSame(['Http', 'Endpoints'], $options->controllerPatterns);
            self::assertSame(['Model', 'Records'], $options->entityPatterns);
            self::assertSame(['Data/**', 'Dto/**'], $options->serviceExcludes);
            self::assertCount(2, (new \Nexum\Architecture\ArchitectureDiscovery())->discover($options)->modules);
        } finally {
            $source->remove();
        }
    }
}
