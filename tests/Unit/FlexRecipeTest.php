<?php

declare(strict_types=1);

namespace Nexum\Tests\Unit;

use Nexum\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

final class FlexRecipeTest extends TestCase
{
    private const RECIPE_DIRECTORY = __DIR__.'/../../flex-recipe/benjaminoeffinger/nexum/0.1';

    public function testRecipeHasRecipesContribLayoutAndRegistersOnlyNexumBundle(): void
    {
        $manifest = json_decode($this->read('manifest.json'), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(
            ['Nexum\\NexumBundle' => ['all']],
            $manifest['bundles'],
        );
        self::assertSame(['config/' => '%CONFIG_DIR%/'], $manifest['copy-from-recipe']);
        self::assertFileExists(self::RECIPE_DIRECTORY.'/config/packages/nexum.yaml');
        self::assertFileExists(self::RECIPE_DIRECTORY.'/config/routes/nexum.yaml');
    }

    public function testRecipeUsesTheSameAutomaticDefaultsAsAnEmptyConfig(): void
    {
        $recipe = Yaml::parse($this->read('config/packages/nexum.yaml'));
        $config = (new Processor())->processConfiguration(new Configuration(), [$recipe['nexum']]);
        self::assertNull($config['doctrine']['enabled']);
    }

    public function testRecipeInstallsSingleNexumRouteImport(): void
    {
        self::assertSame([
            'nexum' => [
                'resource' => '.',
                'type' => 'nexum',
            ],
        ], Yaml::parse($this->read('config/routes/nexum.yaml')));
    }

    private function read(string $path): string
    {
        $contents = file_get_contents(self::RECIPE_DIRECTORY.'/'.$path);
        self::assertIsString($contents);

        return $contents;
    }
}
