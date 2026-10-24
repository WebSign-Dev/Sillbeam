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
        foreach ([[new MakeModuleCommand($creator), ['Name' => 'user']], [new MakeProjectCommand($creator), ['Name' => 'shop']], [new MakeModuleCommand($creator), ['Name' => 'checkout', '--project' => 'Shop']]] as [$command, $input]) {
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
        foreach (['', '../user', 'foo/bar', 'foo\\bar', '123user', 'user-profile', ' user', 'user ', 'user profile', 'user_profile', 'üser', 'éUser'] as $name) {
            foreach ([new MakeModuleCommand($this->creator()), new MakeProjectCommand($this->creator())] as $command) {
                $tester = new CommandTester($command);
                self::assertSame(1, $tester->execute(['Name' => $name]), $name);
            }
        }
        $tester = new CommandTester(new MakeModuleCommand($this->creator()));
        self::assertSame(1, $tester->execute(['Name' => 'User', '--project' => 'Missing']));
        self::assertSame(['.', '..'], scandir($this->source->directory.'/src'));
    }

    public function testNeverOverwritesExistingDirectoryOrFile(): void
    {
        $this->source->write('src/Module/User/UserModule.php', 'original');
        $tester = new CommandTester(new MakeModuleCommand($this->creator()));
        self::assertSame(1, $tester->execute(['Name' => 'user']));
        self::assertSame('original', file_get_contents($this->source->directory.'/src/Module/User/UserModule.php'));
    }

    public function testUsesCustomDiscoveryPathsAndComposerNamespace(): void
    {
        mkdir($this->source->directory.'/src/Features');
        mkdir($this->source->directory.'/src/Sites');
        $config = ['modules' => [['path' => 'src/Features']], 'projects' => [['path' => 'src/Sites']]];
        $tester = new CommandTester(new MakeModuleCommand($this->creator($config)));
        self::assertSame(0, $tester->execute(['Name' => 'user']));
        $tester = new CommandTester(new MakeProjectCommand($this->creator($config)));
        self::assertSame(0, $tester->execute(['Name' => 'shop']));
        $tester = new CommandTester(new MakeModuleCommand($this->creator($config)));
        self::assertSame(0, $tester->execute(['Name' => 'checkout', '--project' => 'Shop']));
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
            self::assertSame(1, $tester->execute(['Name' => 'user']));
            self::assertDirectoryDoesNotExist($this->source->directory.'/src/Module/User');
        }
        $tester = new CommandTester(new MakeProjectCommand($this->creator(['projects' => []])));
        self::assertSame(1, $tester->execute(['Name' => 'shop']));
        self::assertStringContainsString('sillbeam.discovery.projects', $tester->getDisplay());
    }

    public function testRejectsSymlinkAndProjectConflicts(): void
    {
        mkdir($this->source->directory.'/outside');
        symlink($this->source->directory.'/outside', $this->source->directory.'/src/Module');
        $tester = new CommandTester(new MakeModuleCommand($this->creator()));
        self::assertSame(1, $tester->execute(['Name' => 'user']));
        self::assertDirectoryDoesNotExist($this->source->directory.'/outside/User');
        $this->source->write('src/Project/Shop', 'keep');
        $tester = new CommandTester(new MakeProjectCommand($this->creator()));
        self::assertSame(1, $tester->execute(['Name' => 'shop']));
        self::assertSame('keep', file_get_contents($this->source->directory.'/src/Project/Shop'));
    }

    public function testCreatorChangesOnlyFirstAsciiLetterAndKeepsMinimalContract(): void
    {
        foreach ([false, true] as $project) {
            $suffix = $project ? 'Project' : 'Module';
            $area = $project ? 'Project' : 'Module';
            foreach (['user' => 'User', 'userProfile' => 'UserProfile', 'UserABC12' => 'UserABC12', 'aBC' => 'ABC'] as $input => $expected) {
                $file = $this->creator()->create($input, project: $project);
                self::assertSame($this->source->directory.'/src/'.$area.'/'.$expected.'/'.$expected.$suffix.'.php', $file);
                $namespace = $this->source->namespace.'\\'.$area.'\\'.$expected;
                $attribute = 'Sillbeam'.$suffix;
                self::assertSame("<?php\n\ndeclare(strict_types=1);\n\nnamespace $namespace;\n\nuse Sillbeam\\Core\\$area\\Attribute\\$attribute;\n\n#[$attribute]\nfinal class $expected$suffix\n{\n}\n", file_get_contents($file));
            }
        }
    }

    public function testBothCommandsRejectNormalizedCollisionsAndSuffixes(): void
    {
        foreach ([false, true] as $project) {
            $command = $project ? new MakeProjectCommand($this->creator()) : new MakeModuleCommand($this->creator());
            $tester = new CommandTester($command);
            self::assertSame(0, $tester->execute(['Name' => 'User']));
            $suffix = $project ? 'Project' : 'Module';
            $file = $this->source->directory.'/src/'.$suffix.'/User/User'.$suffix.'.php';
            $original = file_get_contents($file);
            self::assertSame(1, $tester->execute(['Name' => 'user']));
            self::assertStringContainsString('already exists', $tester->getDisplay());
            self::assertSame($original, file_get_contents($file));
            self::assertDirectoryDoesNotExist($this->source->directory.'/src/'.$suffix.'/user');
            self::assertSame(1, $tester->execute(['Name' => 'other'.$suffix]));
            self::assertDirectoryDoesNotExist($this->source->directory.'/src/'.$suffix.'/Other'.$suffix);
        }
    }

    public function testProjectSelectorRetainsDirectoryIdAndClassSemantics(): void
    {
        $this->creator()->create('shop', project: true);
        foreach (['Shop', 'shop', $this->source->namespace.'\\Project\\Shop\\ShopProject'] as $index => $owner) {
            $tester = new CommandTester(new MakeModuleCommand($this->creator()));
            self::assertSame(0, $tester->execute(['Name' => 'checkout'.$index, '--project' => $owner]), $tester->getDisplay());
            self::assertFileExists($this->source->directory.'/src/Project/Shop/Module/Checkout'.$index.'/Checkout'.$index.'Module.php');
        }
        $tester = new CommandTester(new MakeModuleCommand($this->creator()));
        self::assertSame(1, $tester->execute(['Name' => 'other', '--project' => 'SHOP']));
    }

    public function testRejectsInvalidNamespacesForBothNormalizedTargets(): void
    {
        foreach ([false, true] as $project) {
            $area = $project ? 'Project' : 'Module';
            mkdir($this->source->directory.'/src/'.$area);
            $root = new \Sillbeam\Core\Architecture\DiscoveryRoot($this->source->directory.'/src/'.$area, 'Bad-Name\\');
            $options = new \Sillbeam\Core\Architecture\DiscoveryOptions(
                $this->source->directory.'/src',
                $this->source->namespace.'\\',
                moduleRoots: $project ? [] : [$root],
                projectRoots: $project ? [$root] : [],
            );
            $creator = new DescriptorCreator($options);
            $command = $project ? new MakeProjectCommand($creator) : new MakeModuleCommand($creator);
            $tester = new CommandTester($command);
            self::assertSame(1, $tester->execute(['Name' => 'user']));
            self::assertDirectoryDoesNotExist($this->source->directory.'/src/'.$area.'/User');
        }
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
