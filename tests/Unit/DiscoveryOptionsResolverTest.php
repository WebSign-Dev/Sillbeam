<?php

declare(strict_types=1);

namespace Nexum\Tests\Unit;

use Nexum\Architecture\DiscoveryOptionsResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

final class DiscoveryOptionsResolverTest extends TestCase
{
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
}
