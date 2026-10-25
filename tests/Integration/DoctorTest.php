<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Console\Doctor\DoctorCommand;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Symfony\Component\Console\Tester\CommandTester;

final class DoctorTest extends TestCase
{
    private SourceWorkspace $source;

    protected function setUp(): void
    {
        $this->source = new SourceWorkspace();
        $this->source->write('src/Kernel.php', '<?php namespace App; final class Kernel extends \Symfony\Component\HttpKernel\Kernel { use \Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait; }');
        $this->source->write('config/bundles.php', '<?php return [Sillbeam\Core\SillbeamCoreBundle::class => ["all" => true]];');
        $this->source->write('bin/console', '<?php use Sillbeam\Core\Console\Application; $app = new Application($kernel); $app->run();');
        $this->source->write('config/routes.yaml', "sillbeam:\n    resource: .\n    type: sillbeam\n");
        $this->source->write('config/services.yaml', "services:\n    App\\:\n        resource: '../src/'\n        exclude: ['../src/Module/', '../src/Project/']\n");
    }

    protected function tearDown(): void
    {
        $this->source->remove();
    }

    public function testStaticEvidenceAndReadOnlyExecution(): void
    {
        $before = $this->snapshot();
        $tester = $this->runDoctor();
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        self::assertSame($before, $this->snapshot());
    }

    public function testMissingAdapterHasConcreteInstructions(): void
    {
        $this->source->write('bin/console', '<?php use Symfony\Bundle\FrameworkBundle\Console\Application; $app = new Application($kernel); $app->run();');
        $tester = $this->runDoctor();
        self::assertSame(1, $tester->getStatusCode(), $tester->getDisplay());
        self::assertStringContainsString('use Sillbeam\\Core\\Console\\Application;', $tester->getDisplay());
        self::assertStringContainsString('existing bootstrap', $tester->getDisplay());
    }

    public function testMissingRoutesAndServiceOverlap(): void
    {
        $this->source->write('config/routes.yaml', '{}');
        $this->source->write('config/services.yaml', "services:\n    App\\: {resource: '../src/'}\n");
        $tester = $this->runDoctor();
        self::assertStringContainsString('config/routes.yaml', $tester->getDisplay());
        self::assertStringContainsString('bypass activation', $tester->getDisplay());
    }

    public function testDynamicConfigurationIsNeverExecuted(): void
    {
        $this->source->write('config/packages/dynamic.php', '<?php file_put_contents(__DIR__."/executed", "bad");');
        $before = $this->snapshot();
        $tester = $this->runDoctor();
        self::assertSame(2, $tester->getStatusCode());
        self::assertStringContainsString('not verified', $tester->getDisplay());
        self::assertSame($before, $this->snapshot());
    }

    public function testAdapterDoesNotBootKernelAndSupportsHelpAndQuiet(): void
    {
        $kernel = new class('test', true) extends \Symfony\Component\HttpKernel\Kernel {
            public function registerBundles(): iterable
            {
                throw new \LogicException('Must not initialize');
            }

            public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader): void
            {
                throw new \LogicException('Must not configure');
            }
        };
        $app = new \Sillbeam\Core\Console\Application($kernel);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        self::assertSame(2, $app->doRun(new \Symfony\Component\Console\Input\ArrayInput(['command' => 'sillbeam:doctor']), $output));
        self::assertStringContainsString('not verified', $output->fetch());
        self::assertSame(0, $app->doRun(new \Symfony\Component\Console\Input\ArrayInput(['command' => 'sillbeam:doctor', '--help' => true]), $output));
        self::assertStringContainsString('No files are written', $output->fetch());
    }

    public function testCommentsAndRuntimeCallbacksDoNotProveAdapter(): void
    {
        $this->source->write('bin/console', '<?php /* new \\Sillbeam\\Core\\Console\\Application($kernel); */ return function () { return new \\Sillbeam\\Core\\Console\\Application($kernel); };');
        self::assertSame(2, $this->runDoctor()->getStatusCode());
    }

    public function testRegularSymfonyRegistrationAndCommandDoNotWriteFilesAfterBoot(): void
    {
        $this->source->write('config/packages/framework.yaml', 'framework: {secret: doctor-test, test: true}');
        $kernel = new \Sillbeam\Core\Tests\Support\TestApplicationKernel($this->source);
        try {
            $kernel->boot();
            $app = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
            $command = $app->find('sillbeam:doctor');
            self::assertSame('sillbeam:doctor', $command->getName());
            $before = $this->snapshot();
            $tester = new CommandTester($command);
            self::assertSame(2, $tester->execute([]), $tester->getDisplay());
            self::assertStringContainsString('Configuration loading convention: not verified', $tester->getDisplay());
            self::assertSame($before, $this->snapshot());
        } finally {
            $kernel->shutdown();
        }
    }

    public function testMissingBundleAndAmbiguousServiceGlob(): void
    {
        $this->source->write('config/bundles.php', '<?php return [];');
        self::assertSame(1, $this->runDoctor()->getStatusCode());
        $this->source->write('config/services.yaml', "services:\n    App\\: {resource: '../src/*'}\n");
        $tester = $this->runDoctor();
        self::assertSame(2, $tester->getStatusCode());
        self::assertStringContainsString('Service imports: not verified', $tester->getDisplay());
    }

    public function testUnresolvedImportsAndDiscoveryParametersRequireManualReview(): void
    {
        $this->source->write('config/packages/sillbeam.yaml', "sillbeam:\n    discovery: {root: '%custom_root%'}\n");
        self::assertSame(2, $this->runDoctor()->getStatusCode());
        $this->source->write('config/packages/sillbeam.yaml', "imports: [{resource: external.yaml}]\n");
        $tester = $this->runDoctor();
        self::assertSame(2, $tester->getStatusCode());
        self::assertStringContainsString('Discovery configuration: not verified', $tester->getDisplay());
    }

    public function testSelectedEnvironmentCanDisableBundle(): void
    {
        $this->source->write('config/bundles.php', '<?php return [Sillbeam\\Core\\SillbeamCoreBundle::class => ["all" => true, "test" => false]];');
        $tester = $this->runDoctor();
        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Bundle registration: action required', $tester->getDisplay());
    }

    public function testConsoleAssignmentDoesNotProveExecutedApplication(): void
    {
        $this->source->write('bin/console', '<?php $app = new \\Sillbeam\\Core\\Console\\Application($kernel); $app = getOtherApplication(); $app->run();');
        $tester = $this->runDoctor();
        self::assertSame(2, $tester->getStatusCode());
    }

    public function testUnloadedFilesAndOtherEnvironmentsCannotAffectEvidence(): void
    {
        $this->source->write('config/services_prod.yaml', "services:\n    App\\: {resource: '../src/'}\n");
        $this->source->write('config/unused.yaml', "services:\n    App\\: {resource: '../src/'}\n");
        $this->source->write('config/packages/prod/dynamic.php', '<?php throw new \\Exception();');
        $this->source->write('config/routes/unused/deep.yaml', "bogus: {resource: external.yaml}\n");
        $tester = $this->runDoctor();
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
    }

    public function testRealBinConsoleWorksWithInvalidArchitectureWithoutWrites(): void
    {
        $kernelClass = $this->source->php('src/Kernel.php', <<<'PHP'
final class Kernel extends \Symfony\Component\HttpKernel\Kernel {
    public function getProjectDir(): string { return \dirname(__DIR__); }
    public function registerBundles(): iterable { throw new \LogicException('Boot forbidden'); }
    public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader): void { throw new \LogicException('Configuration execution forbidden'); }
}
PHP);
        $this->source->php('src/Module/Broken/BrokenModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: [\\Missing\\Module::class])] final class BrokenModule {}');
        $autoload = var_export(\dirname(__DIR__, 2).'/vendor/autoload.php', true);
        $this->source->write('bin/console', '<?php require '.$autoload.'; require \dirname(__DIR__)."/src/Kernel.php"; $app = new \\Sillbeam\\Core\\Console\\Application(new \\'.$kernelClass.'("test", true)); $app->run();');
        $before = $this->snapshot();
        foreach ([['sillbeam:doctor', '--no-ansi'], ['sillbeam:doctor', '--help'], ['sillbeam:doctor', '--quiet']] as $arguments) {
            $process = proc_open([\PHP_BINARY, $this->source->directory.'/bin/console', ...$arguments], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($process);
            self::assertSame('', $error);
            self::assertSame(\in_array('--help', $arguments, true) ? 0 : 2, $code, $output);
            if (\in_array('--quiet', $arguments, true)) {
                self::assertSame('', $output);
            } else {
                self::assertStringContainsString(\in_array('--help', $arguments, true) ? 'Usage:' : 'not verified', $output);
            }
        }
        self::assertSame($before, $this->snapshot());
    }

    public function testFilesystemRootResourceCannotClaimSafeImports(): void
    {
        $this->source->write('config/services.yaml', "services:\n    App\\: {resource: '/'}\n");
        self::assertSame(1, $this->runDoctor()->getStatusCode());
    }

    public function testDiscoveryConfiguredInServicesFileUsesCustomRoots(): void
    {
        $this->source->write('src/Custom/placeholder', '');
        $this->source->write('config/services.yaml', "sillbeam:\n    discovery:\n        modules: [{path: '%kernel.project_dir%/src/Custom'}]\nservices:\n    App\\:\n        resource: '../src/'\n        exclude: ['../src/Module/', '../src/Project/']\n");
        $tester = $this->runDoctor();
        self::assertSame(1, $tester->getStatusCode(), $tester->getDisplay());
    }

    private function runDoctor(): CommandTester
    {
        $tester = new CommandTester(new DoctorCommand($this->source->directory, 'test'));
        $tester->execute([]);

        return $tester;
    }

    /** @return array<string, string> */
    private function snapshot(): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->source->directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $hash = hash_file('sha256', $file->getPathname());
                self::assertIsString($hash);
                $files[$file->getPathname()] = $hash;
            }
        }
        ksort($files);

        return $files;
    }
}
