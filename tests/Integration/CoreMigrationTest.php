<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CoreMigrationTest extends TestCase
{
    public function testCoreBundleDiscoversExtensionWithStableConfigurationAlias(): void
    {
        self::assertTrue(class_exists(\Sillbeam\Core\SillbeamCoreBundle::class));
        $bundle = new \Sillbeam\Core\SillbeamCoreBundle();
        $extension = $bundle->getContainerExtension();
        self::assertInstanceOf(\Sillbeam\Core\DependencyInjection\SillbeamCoreExtension::class, $extension);
        self::assertSame('sillbeam', $extension->getAlias());
        self::assertSame($extension, $bundle->getContainerExtension());
        $container = new ContainerBuilder();
        $container->registerExtension($extension);
        $container->loadFromExtension('sillbeam', ['doctrine' => false]);
        self::assertSame([['doctrine' => false]], $container->getExtensionConfig('sillbeam'));
        self::assertFalse($container->hasExtension('sillbeam_core'));
    }

    public function testComposerAndRecipeUseCoreIdentity(): void
    {
        $root = \dirname(__DIR__, 2);
        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('websign-dev/sillbeam-core', $composer['name']);
        self::assertSame(['Sillbeam\\Core\\' => 'src/'], $composer['autoload']['psr-4']);
        self::assertSame('https://github.com/WebSign-Dev/sillbeam-core', $composer['support']['source']);
        $recipe = json_decode((string) file_get_contents($root.'/flex-recipe/websign-dev/sillbeam-core/0.2/manifest.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame(['Sillbeam\\Core\\SillbeamCoreBundle' => ['all']], $recipe['bundles']);
    }
}
