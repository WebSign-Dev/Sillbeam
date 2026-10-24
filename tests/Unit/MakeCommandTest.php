<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureDiscovery;
use Sillbeam\Core\Architecture\Console\MakeModuleCommand;
use Sillbeam\Core\Architecture\Console\MakeProjectCommand;
use Sillbeam\Core\Architecture\DescriptorCreator;
use Sillbeam\Core\Architecture\DiscoveryOptionsResolver;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MakeCommandTest extends TestCase
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

    public function testCreatesOnlyDiscoverableDescriptorsForAllThreeCases(): void
    {
        $creator = $this->creator();
        foreach ([[new MakeModuleCommand($creator), ['Name' => 'User']], [new MakeProjectCommand($creator), ['Name' => 'Shop']], [new MakeModuleCommand($creator), ['Name' => 'Checkout', '--project' => 'Shop']]] as [$command, $input]) {
            $tester = new CommandTester($command);
            self::assertSame(0, $tester->execute($input), $tester->getDisplay());
        }
        $result = (new ArchitectureDiscovery())->discover($this->options());
        self::assertCount(2, $result->modules);
        self::assertCount(1, $result->projects);
        $namespace = $this->source->namespace;
        self::assertNull($result->modules[$namespace.'\\Module\\User\\UserModule']->projectClass);
        self::assertSame($namespace.'\\Project\\Shop\\ShopProject', $result->modules[$namespace.'\\Project\\Shop\\Module\\Checkout\\CheckoutModule']->projectClass);
        $files = iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->source->directory.'/src', \FilesystemIterator::SKIP_DOTS)));
        self::assertCount(3, $files);
    }

    public function testRejectsInvalidNamesAndUnknownProjectsWithoutWriting(): void
    {
        foreach (['', '../User', 'Foo/Bar', 'Foo\\Bar', '1User', 'Bad-name', ' User', 'class'] as $name) {
            $tester = new CommandTester(new MakeModuleCommand($this->creator()));
            self::assertSame(1, $tester->execute(['Name' => $name]));
        }
        $tester = new CommandTester(new MakeModuleCommand($this->creator()));
        self::assertSame(1, $tester->execute(['Name' => 'User', '--project' => 'Missing']));
        self::assertSame(['.', '..'], scandir($this->source->directory.'/src'));
    }

    public function testNeverOverwritesExistingDirectoryOrFile(): void
    {
        $this->source->write('src/Module/User/UserModule.php', 'original');
        $tester = new CommandTester(new MakeModuleCommand($this->creator()));
        self::assertSame(1, $tester->execute(['Name' => 'User']));
        self::assertSame('original', file_get_contents($this->source->directory.'/src/Module/User/UserModule.php'));
    }

    public function testUsesCustomDiscoveryPathsAndComposerNamespace(): void
    {
        mkdir($this->source->directory.'/src/Features');
        mkdir($this->source->directory.'/src/Sites');
        $config = ['modules' => [['path' => 'src/Features']], 'projects' => [['path' => 'src/Sites']]];
        $tester = new CommandTester(new MakeModuleCommand($this->creator($config)));
        self::assertSame(0, $tester->execute(['Name' => 'User']));
        $tester = new CommandTester(new MakeProjectCommand($this->creator($config)));
        self::assertSame(0, $tester->execute(['Name' => 'Shop']));
        $tester = new CommandTester(new MakeModuleCommand($this->creator($config)));
        self::assertSame(0, $tester->execute(['Name' => 'Checkout', '--project' => 'Shop']));
        $result = (new ArchitectureDiscovery())->discover($this->options($config));
        self::assertArrayHasKey($this->source->namespace.'\\Features\\User\\UserModule', $result->modules);
        self::assertArrayHasKey($this->source->namespace.'\\Sites\\Shop\\Module\\Checkout\\CheckoutModule', $result->modules);
    }

    public function testRejectsExcludedDisabledAndAmbiguousRoots(): void
    {
        mkdir($this->source->directory.'/src/Other');
        foreach ([
            ['excludes' => ['Module/**']],
            ['modules' => []],
            ['modules' => [['path' => 'src/Module', 'namespace' => 'One\\'], ['path' => 'src/Other', 'namespace' => 'Two\\']]],
        ] as $config) {
            // Explicit roots must exist before discovery options can be resolved.
            if (!is_dir($this->source->directory.'/src/Module')) {
                mkdir($this->source->directory.'/src/Module');
            }
            $tester = new CommandTester(new MakeModuleCommand($this->creator($config)));
            self::assertSame(1, $tester->execute(['Name' => 'User']));
            self::assertDirectoryDoesNotExist($this->source->directory.'/src/Module/User');
        }
        $tester = new CommandTester(new MakeProjectCommand($this->creator(['projects' => []])));
        self::assertSame(1, $tester->execute(['Name' => 'Shop']));
        self::assertStringContainsString('sillbeam.discovery.projects', $tester->getDisplay());
    }

    public function testRejectsSymlinkAndProjectConflicts(): void
    {
        mkdir($this->source->directory.'/outside');
        symlink($this->source->directory.'/outside', $this->source->directory.'/src/Module');
        $tester = new CommandTester(new MakeModuleCommand($this->creator()));
        self::assertSame(1, $tester->execute(['Name' => 'User']));
        self::assertDirectoryDoesNotExist($this->source->directory.'/outside/User');
        $this->source->write('src/Project/Shop', 'keep');
        $tester = new CommandTester(new MakeProjectCommand($this->creator()));
        self::assertSame(1, $tester->execute(['Name' => 'Shop']));
        self::assertSame('keep', file_get_contents($this->source->directory.'/src/Project/Shop'));
    }

    /** @param array<string, mixed> $config */
    private function creator(array $config = []): DescriptorCreator
    {
        return new DescriptorCreator($this->options($config));
    }

    /** @param array<string, mixed> $config */
    private function options(array $config = []): \Sillbeam\Core\Architecture\DiscoveryOptions
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->source->directory);

        return (new DiscoveryOptionsResolver())->resolve($container, [['discovery' => $config]]);
    }
}
