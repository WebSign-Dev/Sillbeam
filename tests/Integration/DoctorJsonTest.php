<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Console\Doctor\DoctorCommand;
use Sillbeam\Core\Console\Doctor\InstallationInspector;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Sillbeam\Core\Tests\Support\TestApplicationKernel;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;

final class DoctorJsonTest extends TestCase
{
    private SourceWorkspace $source;

    protected function setUp(): void
    {
        $this->source = new SourceWorkspace();
        $this->source->write('src/Kernel.php', '<?php namespace App; final class Kernel extends \Symfony\Component\HttpKernel\Kernel { use \Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait; }');
        $this->source->write('config/bundles.php', '<?php return [Sillbeam\Core\SillbeamCoreBundle::class => ["all" => true]];');
        $this->source->write('bin/console', '<?php use Sillbeam\Core\Console\Application; $app = new Application($kernel); $app->run();');
        $this->source->write('config/routes.yaml', 'sillbeam: {resource: ., type: sillbeam}');
        $this->source->write('config/services.yaml', "services:\n    App\\: {resource: '../src/', exclude: '../src/{Module,Project}'}\n");
    }

    protected function tearDown(): void
    {
        $this->source->remove();
    }

    public function testJsonUsesOnlyInspectorRowsAndExactlyPreservesDefaultText(): void
    {
        $checks = (new InstallationInspector())->inspect($this->source->directory, 'test');
        self::assertSame(['bundle.registration', 'console.adapter', 'configuration.loading', 'routing.import', 'discovery.configuration', 'services.imports'], array_column($checks, 'id'));
        $tester = new CommandTester(new DoctorCommand($this->source->directory, 'test'));
        self::assertSame(0, $tester->execute([]));
        $text = $tester->getDisplay();
        self::assertSame(0, $tester->execute(['--format' => 'text']));
        self::assertSame($text, $tester->getDisplay());
        self::assertSame(0, $tester->execute(['--format' => 'json'], ['decorated' => true, 'verbosity' => OutputInterface::VERBOSITY_DEBUG]));
        $json = $tester->getDisplay();
        self::assertStringNotContainsString("\033", $json);
        self::assertStringNotContainsString('SILLBEAM CORE', $json);
        $data = $this->decode($json);
        self::assertSame('verified', $data['status']);
        self::assertSame(0, $data['exit_code']);
        self::assertSame(array_map(static fn (array $row): array => ['id' => $row['id'], 'status' => $row['status'], 'description' => $row['label'], 'detail' => $row['detail']], $checks), $data['checks']);
    }

    public function testActionItemsAndUnverifiedDetailsRemainDistinctAndReadOnly(): void
    {
        $this->source->write('config/routes.yaml', '{}');
        $tester = new CommandTester(new DoctorCommand($this->source->directory, 'test'));
        self::assertSame(1, $tester->execute(['--format' => 'json']));
        self::assertSame('action required', $this->decode($tester->getDisplay())['status']);
        $this->source->write('config/services.yaml', "services:\n    App\\: {resource: '../src/'}\n    Other\\: {resource: '%unknown%'}\n");
        $this->source->write('config/routes/dynamic.php', '<?php file_put_contents(__DIR__."/executed", "bad");');
        $before = $this->snapshot();
        self::assertSame(2, $tester->execute(['--format' => 'json']));
        self::assertSame($before, $this->snapshot());
        $data = $this->decode($tester->getDisplay());
        self::assertSame('not verified', $data['status']);
        self::assertGreaterThan(0, $data['summary']['action required']);
        self::assertGreaterThan(0, $data['summary']['not verified']);
        $services = array_values(array_filter($data['checks'], static fn (array $check): bool => 'services.imports' === $check['id']));
        self::assertSame(['action required', 'not verified'], array_column($services, 'status'));
        self::assertStringContainsString('config/services.yaml', $services[0]['detail']);
        self::assertSame([], $data['errors']);
    }

    public function testMalformedYamlRetainsTheInspectorsConfigurationFileDiagnostics(): void
    {
        $this->source->write('config/routes/broken.yaml', "broken: [\n");
        $tester = new CommandTester(new DoctorCommand($this->source->directory, 'test'));
        self::assertSame(2, $tester->execute(['--format' => 'json']));
        $data = $this->decode($tester->getDisplay());
        $files = array_values(array_filter($data['checks'], static fn (array $check): bool => 'configuration.files' === $check['id']));
        self::assertCount(1, $files);
        self::assertSame('not verified', $files[0]['status']);
        self::assertStringContainsString('routes/broken.yaml', $files[0]['detail']);
        self::assertSame([], $data['errors']);
    }

    public function testInvalidOptionsProduceUsageErrorsBeforeAnyInspection(): void
    {
        // An unusable directory must not turn invalid usage into inspection findings.
        foreach ([['--format' => 'json', '--unknown' => true], ['--format' => 'json', 'extra' => 'unexpected']] as $arguments) {
            $tester = new CommandTester(new DoctorCommand('/does/not/exist', 'test'));
            self::assertSame(2, $tester->execute($arguments));
            $data = $this->decode($tester->getDisplay());
            self::assertSame('doctor.usage', $data['errors'][0]['code']);
            self::assertSame([], $data['checks']);
        }
        $tester = new CommandTester(new DoctorCommand('/does/not/exist', 'test'));
        self::assertSame(2, $tester->execute(['--format' => 'xml']));
        self::assertStringContainsString('Supported formats: text, json.', $tester->getDisplay());
        self::assertStringNotContainsString('Bundle registration', $tester->getDisplay());
    }

    public function testQuietSuppressesEveryStatusInBothFormats(): void
    {
        foreach ([0, 1, 2] as $code) {
            if (1 === $code) {
                $this->source->write('config/routes.yaml', '{}');
            } elseif (2 === $code) {
                $this->source->write('config/routes/dynamic.php', '<?php throw new \LogicException("Must not execute");');
            }
            foreach (['text', 'json'] as $format) {
                $tester = new CommandTester(new DoctorCommand($this->source->directory, 'test'));
                self::assertSame($code, $tester->execute(['--format' => $format], ['verbosity' => OutputInterface::VERBOSITY_QUIET]));
                self::assertSame('', $tester->getDisplay());
            }
        }
    }

    public function testRealNativeAndAdapterConsoleEntrypointsHandleJsonOptionsAndHelp(): void
    {
        $this->source->write('config/packages/framework.yaml', 'framework: {secret: doctor-json, test: true}');
        foreach ([false, true] as $adapter) {
            $kernel = new TestApplicationKernel($this->source);
            $app = $adapter ? new \Sillbeam\Core\Console\Application($kernel) : new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
            $app->setAutoExit(false);
            $tester = new ApplicationTester($app);
            try {
                foreach ([[], ['--format' => 'json']] as $arguments) {
                    self::assertSame(2, $tester->run(['command' => 'sillbeam:doctor', '--format' => 'json', ...$arguments], ['capture_stderr_separately' => true]));
                    self::assertSame('', $tester->getErrorOutput());
                    // The custom test kernel is deliberately not statically verified.
                    self::assertSame('not verified', $this->decode($tester->getDisplay())['status']);
                }
                self::assertSame(2, $tester->run(['command' => 'sillbeam:doctor', '--format' => 'json', '--unknown' => true], ['capture_stderr_separately' => true]));
                self::assertSame('', $tester->getErrorOutput());
                self::assertSame('doctor.usage', $this->decode($tester->getDisplay())['errors'][0]['code']);
                self::assertSame(2, $tester->run(['command' => 'sillbeam:doctor', '--format' => 'json', '--quiet' => true]));
                self::assertSame('', $tester->getDisplay());
                self::assertSame(0, $tester->run(['command' => 'sillbeam:doctor', '--help' => true]));
                self::assertStringContainsString('--format', $tester->getDisplay());
                self::assertStringContainsString('No files are written', $tester->getDisplay());
            } finally {
                $kernel->shutdown();
            }
        }
    }

    public function testAdapterJsonWorksBeforeCommandAndWithoutKernelBoot(): void
    {
        $source = $this->source;
        $kernel = new class($source) extends \Symfony\Component\HttpKernel\Kernel {
            public function __construct(private readonly SourceWorkspace $source)
            {
                parent::__construct('test', true);
            }

            public function getProjectDir(): string
            {
                return $this->source->directory;
            }

            public function registerBundles(): iterable
            {
                throw new \LogicException('Must not boot');
            }

            public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader): void
            {
                throw new \LogicException('Must not configure');
            }
        };
        $app = new \Sillbeam\Core\Console\Application($kernel);
        $app->setAutoExit(false);
        foreach ([['--format=json', 'sillbeam:doctor'], ['--format', 'json', 'sillbeam:doctor']] as $arguments) {
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
            self::assertSame(2, $app->run(new ArgvInput(['bin/console', ...$arguments]), $output));
            self::assertSame('not verified', $this->decode($output->fetch())['status']);
        }
    }

    public function testRepeatedFormatsUseTheLastValueForErrorsAndOutputGuards(): void
    {
        foreach ([
            ['--format=text', '--format=json'],
            ['--format', 'text', '--format', 'json'],
            ['--format', 'text', '--format=json'],
        ] as $formats) {
            $command = new DoctorCommand($this->source->directory, 'test');
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
            self::assertSame(2, $command->run(new ArgvInput(['doctor', ...$formats, '--unknown']), $output));
            self::assertSame('doctor.usage', $this->decode($output->fetch())['errors'][0]['code']);
            $command->setCode(static function (): int {
                echo 'must be buffered';
                throw new \RuntimeException('Expected execution failure');
            });
            self::assertSame(2, $command->run(new ArgvInput(['doctor', ...$formats]), $output));
            self::assertSame('doctor.execution', $this->decode($output->fetch())['errors'][0]['code']);
        }
        $command = new DoctorCommand($this->source->directory, 'test');
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        self::assertSame(2, $command->run(new ArgvInput(['doctor', '--format=json', '--format=text', '--unknown']), $output));
        self::assertStringContainsString('does not exist', $output->fetch());
        foreach ([['--format=', 'json'], ['--', '--format=json']] as $arguments) {
            self::assertSame(2, $command->run(new ArgvInput(['doctor', ...$arguments]), $output));
            self::assertStringNotContainsString('schema_version', $output->fetch());
        }
    }

    public function testExecutionFailuresStayJsonAndRestoreOutputAndErrorHandling(): void
    {
        $command = new DoctorCommand($this->source->directory, 'test');
        $command->setCode(static function (): int {
            echo 'incidental output must disappear';
            trigger_error('Inspection infrastructure failed', \E_USER_WARNING);

            return 0;
        });
        $tester = new CommandTester($command);
        $level = ob_get_level();
        $sentinel = static fn (): bool => false;
        set_error_handler($sentinel);
        try {
            self::assertSame(2, $tester->execute(['--format' => 'json']));
            self::assertSame($level, ob_get_level());
            self::assertSame($sentinel, set_error_handler($sentinel));
            restore_error_handler();
            $data = $this->decode($tester->getDisplay());
            self::assertSame('doctor.execution', $data['errors'][0]['code']);
            self::assertSame('Inspection infrastructure failed', $data['errors'][0]['message']);
            self::assertSame([], $data['checks']);
            self::assertStringNotContainsString('incidental', $tester->getDisplay());
        } finally {
            restore_error_handler();
        }
    }

    public function testAdapterCommandCreationFailureUsesDoctorSchemaWithoutBoot(): void
    {
        $kernel = new class('test', true) extends \Symfony\Component\HttpKernel\Kernel {
            public function getProjectDir(): string
            {
                echo 'command preparation noise';
                throw new \RuntimeException('No project directory');
            }

            public function registerBundles(): iterable
            {
                throw new \LogicException('Must not boot');
            }

            public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader): void
            {
                throw new \LogicException('Must not configure');
            }
        };
        $app = new \Sillbeam\Core\Console\Application($kernel);
        $app->setAutoExit(false);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        ob_start();
        try {
            $code = $app->run(new ArgvInput(['bin/console', 'sillbeam:doctor', '--format=text', '--format=json']), $output);
            $noise = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        self::assertSame(2, $code);
        self::assertSame('', $noise);
        $data = $this->decode($output->fetch());
        self::assertSame('doctor.execution', $data['errors'][0]['code']);
        self::assertSame('No project directory', $data['errors'][0]['message']);
    }

    public function testSubprocessStreamsUsageErrorsQuietAndHelpInBothEntrypoints(): void
    {
        $kernel = $this->source->php('src/Kernel.php', 'final class Kernel extends \\Symfony\\Component\\HttpKernel\\Kernel { use \\Symfony\\Bundle\\FrameworkBundle\\Kernel\\MicroKernelTrait; }');
        $this->source->write('config/bundles.php', '<?php return [\\Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle::class => ["all" => true], \\Sillbeam\\Core\\SillbeamCoreBundle::class => ["all" => true]];');
        $this->source->write('config/packages/framework.yaml', 'framework: {secret: json-subprocess}');
        $this->source->write('config/services.yaml', "services:\n    App\\: {resource: '../src/', exclude: '../src/{Kernel.php,Module,Project}'}\n");
        $autoload = var_export(\dirname(__DIR__, 2).'/vendor/autoload.php', true);
        foreach ([false, true] as $adapter) {
            $application = $adapter ? '\\Sillbeam\\Core\\Console\\Application' : '\\Symfony\\Bundle\\FrameworkBundle\\Console\\Application';
            $this->source->write('bin/console', '<?php require '.$autoload.'; require \\dirname(__DIR__)."/src/Kernel.php"; $app = new '.$application.'(new \\'.$kernel.'("test", true)); $app->run();');
            [$code, $stdout, $stderr] = $this->subprocess(['sillbeam:doctor', '--format=json', '--ansi', '-vvv']);
            self::assertSame($adapter ? 0 : 1, $code, $stdout.$stderr);
            self::assertStringNotContainsString('[critical]', $stderr);
            if ($adapter) {
                self::assertSame('', $stderr);
            }
            self::assertStringNotContainsString("\033", $stdout);
            self::assertSame($code, $this->decode($stdout)['exit_code']);
            foreach ([
                ['sillbeam:doctor', '--format=json', '--bad-option'],
                ['--format=json', 'sillbeam:doctor', '--bad-option'],
                ['sillbeam:doctor', '--format=json', 'unexpected'],
                ['sillbeam:doctor', '--format=text', '--format=json', '--bad-option'],
            ] as $arguments) {
                [$code, $stdout, $stderr] = $this->subprocess($arguments);
                self::assertSame(2, $code, $stdout.$stderr);
                self::assertSame('', $stderr);
                self::assertSame('doctor.usage', $this->decode($stdout)['errors'][0]['code']);
            }
            foreach ([['sillbeam:doctor', '--format'], ['sillbeam:doctor', '--format=xml']] as $arguments) {
                [$code, $stdout, $stderr] = $this->subprocess($arguments);
                self::assertSame(2, $code, $stdout.$stderr);
                self::assertSame('', $stderr);
            }
            [$code, $stdout, $stderr] = $this->subprocess(['sillbeam:doctor', '--format=json', '--bad-option', '--quiet']);
            self::assertSame(2, $code);
            self::assertSame('', $stdout.$stderr);
            [$code, $stdout, $stderr] = $this->subprocess(['sillbeam:doctor', '--help']);
            self::assertSame(0, $code, $stdout.$stderr);
            self::assertStringContainsString('--format', $stdout);
            self::assertSame('', $stderr);
            [$code, $stdout, $stderr] = $this->subprocess(['sillbeam:doctor', '--help', '--format=json']);
            self::assertSame(0, $code, $stdout.$stderr);
            self::assertSame('', $stderr);
            if ($adapter) {
                self::assertStringContainsString('Usage:', $stdout);
            } else {
                // Native Symfony's HelpCommand owns --format at this point.
                self::assertSame('sillbeam:doctor', json_decode($stdout, true, flags: \JSON_THROW_ON_ERROR)['name']);
            }
        }
    }

    /** @param list<string> $arguments
     * @return array{int, string, string}
     */
    private function subprocess(array $arguments): array
    {
        $process = proc_open([\PHP_BINARY, $this->source->directory.'/bin/console', ...$arguments], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, null, ['SHELL_VERBOSITY' => '0'] + getenv());
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), (string) $stdout, (string) $stderr];
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        $data = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
        $schema = json_decode((string) file_get_contents(\dirname(__DIR__, 2).'/docs/reference/doctor-report.schema.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame($schema['required'], array_keys($data));
        self::assertSame(1, $data['schema_version']);
        self::assertContains($data['status'], $schema['properties']['status']['enum']);
        self::assertContains($data['exit_code'], [0, 1, 2]);
        self::assertSame(['verified', 'action required', 'not verified'], array_keys($data['summary']));
        foreach ($data['summary'] as $count) {
            self::assertIsInt($count);
            self::assertGreaterThanOrEqual(0, $count);
        }
        foreach ($data['checks'] as $check) {
            self::assertSame($schema['properties']['checks']['items']['required'], array_keys($check));
            self::assertContains($check['id'], $schema['properties']['checks']['items']['properties']['id']['enum']);
            self::assertContains($check['status'], $schema['properties']['status']['enum']);
            self::assertIsString($check['description']);
            self::assertIsString($check['detail']);
        }
        foreach ($data['errors'] as $error) {
            self::assertSame(['code', 'message'], array_keys($error));
            self::assertContains($error['code'], ['doctor.usage', 'doctor.execution']);
            self::assertIsString($error['message']);
        }

        return $data;
    }

    /** @return array<string, string|false> */
    private function snapshot(): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->source->directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $files[$file->getPathname()] = hash_file('sha256', $file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }
}
