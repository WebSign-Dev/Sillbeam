<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

final class FlexRecipeTest extends TestCase
{
    private const RECIPE_DIRECTORY = __DIR__.'/../../flex-recipe/websign-dev/sillbeam-core/0.2';

    public function testRecipeHasRecipesContribLayoutAndRegistersOnlySillbeamCoreBundle(): void
    {
        $manifest = json_decode($this->read('manifest.json'), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(
            ['Sillbeam\\Core\\SillbeamCoreBundle' => ['all']],
            $manifest['bundles'],
        );
        self::assertSame(['config/' => '%CONFIG_DIR%/'], $manifest['copy-from-recipe']);
        self::assertFileDoesNotExist(self::RECIPE_DIRECTORY.'/config/packages/sillbeam.yaml');
        self::assertFileExists(self::RECIPE_DIRECTORY.'/config/routes/sillbeam.yaml');
    }

    public function testRecipeUsesTheSameAutomaticDefaultsAsAnEmptyConfig(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);
        self::assertNull($config['doctrine']['enabled']);
    }

    public function testRecipeInstallsSingleSillbeamRouteImport(): void
    {
        self::assertSame([
            'sillbeam' => [
                'resource' => '.',
                'type' => 'sillbeam',
            ],
        ], Yaml::parse($this->read('config/routes/sillbeam.yaml')));
    }

    public function testPackageDeclaresSymfonyBundleTypeForFlex(): void
    {
        $package = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('symfony-bundle', $package['type']);
    }

    private function read(string $path): string
    {
        $contents = file_get_contents(self::RECIPE_DIRECTORY.'/'.$path);
        self::assertIsString($contents);

        return $contents;
    }
}
