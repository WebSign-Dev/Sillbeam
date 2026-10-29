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

    public function testSourceManifestContainsOnlySupportedConfiguratorsAndSeparateInstructions(): void
    {
        $manifest = json_decode($this->read('manifest.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame(['bundles', 'copy-from-recipe'], array_keys($manifest));
        $instructions = $this->read('post-install.txt');
        foreach (['php bin/console sillbeam:doctor', 'use Sillbeam\\Core\\Console\\Application;', 'bin/console', 'bootstrap and Kernel', 'services.yaml', 'Module', 'Project', 'php bin/console sillbeam:make:module Hello', 'php bin/console sillbeam:architecture'] as $text) {
            self::assertStringContainsString($text, $instructions);
        }
        self::assertLessThan(strpos($instructions, 'sillbeam:architecture'), strpos($instructions, 'sillbeam:doctor'));
        self::assertStringEndsWith("\n", $instructions);
        self::assertFileDoesNotExist(self::RECIPE_DIRECTORY.'/bin/console');
        self::assertFileDoesNotExist(self::RECIPE_DIRECTORY.'/config/services.yaml');
    }

    public function testInstructionsPrepareTheAdapterBeforeDoctorAndLinkTheWorkingExample(): void
    {
        $instructions = $this->read('post-install.txt');
        self::assertLessThan(strpos($instructions, 'php bin/console sillbeam:doctor'), strpos($instructions, 'use Sillbeam\\Core\\Console\\Application;'));
        self::assertStringContainsString('config/routes/sillbeam.yaml', $instructions);
        self::assertStringContainsString('docs/guides/getting-started.md', $instructions);
        self::assertStringContainsString('Never overwrite', $instructions);
        self::assertStringNotContainsString('sillbeam:install', $instructions);
    }

    public function testRecipeTextAndConfigurationFollowApplicableSymfonyChecks(): void
    {
        foreach (['manifest.json', 'post-install.txt', 'config/routes/sillbeam.yaml'] as $file) {
            $contents = $this->read($file);
            self::assertStringEndsWith("\n", $contents);
            self::assertStringNotContainsString("\t", $contents);
            self::assertFalse(is_link(self::RECIPE_DIRECTORY.'/'.$file));
            if (!str_ends_with($file, '.txt')) {
                foreach (explode("\n", $contents) as $line) {
                    self::assertMatchesRegularExpression('/^(?:(?: {4})*[^ \t]|$)/', $line);
                }
            }
        }
        $package = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('MIT', $package['license']);
        self::assertArrayNotHasKey('symfony/symfony', $package['require']);
        self::assertArrayNotHasKey('symfony/security', $package['require']);
    }

    private function read(string $path): string
    {
        $contents = file_get_contents(self::RECIPE_DIRECTORY.'/'.$path);
        self::assertIsString($contents);

        return $contents;
    }
}
