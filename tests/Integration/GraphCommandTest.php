<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\GraphCommand;
use Sillbeam\Core\Architecture\DiscoveryOptions;
use Sillbeam\Core\Console\Application;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Sillbeam\Core\Tests\Support\TestApplicationKernel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;

final class GraphCommandTest extends TestCase
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

    public function testDeclaredEdgesAreDirectAndOutputIgnoresCatalogOrder(): void
    {
        $c = $this->module('C');
        $b = $this->module('B', [$c]);
        $this->module('A', [$b]);
        $report = $this->report();
        $tester = $this->command($report);
        self::assertSame(0, $tester->getStatusCode());
        $text = $tester->getDisplay();
        self::assertStringStartsWith('flowchart LR'."\n", $text);
        self::assertStringContainsString('m0001 -->|declared| m0002', $text);
        self::assertStringContainsString('m0002 -->|declared| m0003', $text);
        self::assertStringNotContainsString('m0001 -->|declared| m0003', $text);
        self::assertStringContainsString('enabled=true; active=true', $text);
        self::assertStringNotContainsString("\033", $text);
        $data = $report->data;
        $data['modules'] = array_reverse($data['modules']);
        $data['modules'][0]['declared_dependencies'] = array_reverse($data['modules'][0]['declared_dependencies']);
        self::assertSame($text, $this->command(new ArchitectureReport($data, $report->presentation))->getDisplay());
    }

    public function testObservedUndeclaredReferencesAreDistinctAndDeduplicated(): void
    {
        $this->module('Target');
        $this->module('Source');
        $port = $this->source->php('src/Module/Target/Port.php', 'interface Port {}');
        $this->source->php('src/Module/Source/Consumer.php', 'final class Consumer { public \\'.$port.' $one; public \\'.$port.' $two; }');
        $tester = $this->command($this->report());
        self::assertSame(1, $tester->getStatusCode());
        self::assertSame(1, substr_count($tester->getDisplay(), 'm0001 -.->|observed undeclared| m0002'));
        self::assertStringContainsString('diagnostic evidence', $tester->getDisplay());
        self::assertStringContainsString('active=unknown', $tester->getDisplay());
        self::assertStringNotContainsString('-->|declared|', $tester->getDisplay());
    }

    public function testRecordedCycleMembersAreMarkedWithoutInventingEdges(): void
    {
        $a = $this->source->namespace.'\\Module\\A\\AModule';
        $b = $this->module('B', [$a]);
        $this->module('A', [$b]);
        $tester = $this->command($this->report());
        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('m0001 -->|declared| m0002', $tester->getDisplay());
        self::assertStringContainsString('m0002 -->|declared| m0001', $tester->getDisplay());
        self::assertStringContainsString('class m0001,m0002 cycle;', $tester->getDisplay());
        self::assertSame(2, substr_count($tester->getDisplay(), 'recorded cycle member'));
        self::assertStringContainsString('dependency#46;cycle', $tester->getDisplay());
    }

    public function testDisabledAndUnassignedModulesRetainValidatedStates(): void
    {
        $this->module('Disabled');
        $this->module('Unused');
        $this->project('Shop');
        $tester = $this->command($this->report(['modules' => ['disabled' => ['enabled' => false]]]));
        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('enabled=false; active=false; state=disabled', $tester->getDisplay());
        self::assertStringContainsString('enabled=true; active=false; state=unassigned', $tester->getDisplay());
        self::assertStringContainsString('class m0001 disabled;', $tester->getDisplay());
    }

    public function testProjectsSeparatePrivateIdentityFromSharedMembership(): void
    {
        $shared = $this->module('Billing');
        $this->project('Shop', [$shared]);
        $this->project('Admin');
        $this->module('Billing', [], 'Shop');
        $this->module('Billing', [], 'Admin');
        $config = ['projects' => ['shop' => ['hosts' => ['shop.test']], 'admin' => ['hosts' => ['admin.test']]]];
        $report = $this->report($config);
        $tester = $this->command($report);
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        $text = $tester->getDisplay();
        self::assertStringContainsString('subgraph shared_scope["Shared modules"]', $text);
        self::assertStringContainsString('subgraph project_scope_0001["Private modules', $text);
        self::assertStringContainsString('subgraph project_scope_0002["Private modules', $text);
        self::assertStringContainsString('p0001 -.->|active membership| m0002', $text);
        self::assertStringContainsString('p0002 -.->|active membership| m0001', $text);
        self::assertStringContainsString('p0002 -.->|active membership| m0003', $text);
        self::assertStringNotContainsString('p0001 -.->|active membership| m0001', $text);
        preg_match_all('/^\s+(m\d+)\["/m', $text, $matches);
        self::assertCount(3, array_unique($matches[1]));
        $data = $report->data;
        $data['projects'] = array_reverse($data['projects']);
        foreach ($data['projects'] as &$project) {
            $project['modules'] = array_reverse($project['modules']);
        }
        unset($project);
        self::assertSame($text, $this->command(new ArchitectureReport($data, $report->presentation))->getDisplay());
    }

    public function testUnknownTargetsAreExplicitPlaceholdersAndActivationIsUnknown(): void
    {
        $this->module('Billing', ['Missing\\Module']);
        $tester = $this->command($this->report());
        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('unresolved module target', $tester->getDisplay());
        self::assertStringContainsString('m0001 -->|declared| u0001', $tester->getDisplay());
        self::assertStringContainsString('active=unknown', $tester->getDisplay());
        self::assertStringNotContainsString('active=true', $tester->getDisplay());
    }

    public function testInvalidDescriptorProducesSourceOnlyUnavailableGraph(): void
    {
        $this->source->php('src/Module/Broken/BrokenModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(id: "invalid id")] final class BrokenModule {}');
        $tester = $this->command($this->report());
        self::assertSame(2, $tester->getStatusCode());
        self::assertStringStartsWith('flowchart LR', $tester->getDisplay());
        self::assertStringContainsString('unavailable', $tester->getDisplay());
        self::assertStringContainsString('Reference evidence incomplete', $tester->getDisplay());
        self::assertStringContainsString('Module catalog unavailable or empty', $tester->getDisplay());
    }

    public function testPartialEvidenceKeepsKnownCatalogWithoutClaimingReferenceAbsence(): void
    {
        $this->module('Billing');
        $analysis = $this->analysis();
        $partial = new ArchitectureAnalysisResult([new ArchitectureFinding('source.parse', 'Partial analysis', [], 'fatal')], discovery: $analysis->discovery);
        $tester = $this->command(ArchitectureReport::fromAnalysis($partial));
        self::assertSame(2, $tester->getStatusCode());
        self::assertStringContainsString('enabled=unknown; active=unknown; state=unknown', $tester->getDisplay());
        self::assertStringContainsString('Reference evidence incomplete', $tester->getDisplay());
        self::assertStringNotContainsString('active=true', $tester->getDisplay());
    }

    public function testExternalLabelsCannotEscapeIntoMermaidStatements(): void
    {
        $this->module('Billing');
        $report = $this->report();
        $data = $report->data;
        $data['modules'][0]['id'] = "end\"]\nclick m0001 \"javascript:alert(1)\"\n%%{init: {}}%%<b>&#35;`|\033";
        $text = $this->command(new ArchitectureReport($data, $report->presentation))->getDisplay();
        self::assertStringContainsString('end#34;#93; click', $text);
        self::assertStringContainsString('#60;b#62;#38;#35;35#59;', $text);
        self::assertStringNotContainsString("\nclick", $text);
        self::assertStringNotContainsString('%%{', $text);
        self::assertStringNotContainsString('<b>', $text);
        self::assertStringNotContainsString("\033", $text);
        self::assertStringContainsString('m0001["', $text);
    }

    public function testProviderOutputAndErrorsCannotPolluteSource(): void
    {
        $tester = new CommandTester(new GraphCommand(static function (): ArchitectureReport {
            echo "unwanted output\n";
            throw new \RuntimeException("Broken\nconfiguration");
        }));
        self::assertSame(2, $tester->execute(['--format' => 'mermaid'], ['decorated' => true]));
        self::assertStringStartsWith('flowchart LR', $tester->getDisplay());
        self::assertStringNotContainsString('unwanted output', $tester->getDisplay());
        self::assertStringNotContainsString("\033", $tester->getDisplay());
        self::assertStringContainsString('report#46;execution', $tester->getDisplay());
    }

    public function testInvalidOptionsDoNotAnalyzeAndQuietRetainsExitStatus(): void
    {
        foreach ([['--format' => 'json'], ['--format' => ''], ['--unknown' => true]] as $arguments) {
            $called = false;
            $tester = new CommandTester(new GraphCommand(static function () use (&$called): ArchitectureReport {
                $called = true;

                return ArchitectureReport::unavailable(new \RuntimeException('Unexpected'));
            }));
            self::assertSame(2, $tester->execute($arguments, ['capture_stderr_separately' => true]));
            self::assertFalse($called);
            self::assertSame('', $tester->getDisplay());
            self::assertNotSame('', $tester->getErrorOutput());
        }
        $this->module('Billing', ['Missing\\Module']);
        $tester = new CommandTester(new GraphCommand($this->report()));
        self::assertSame(1, $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_QUIET]));
        self::assertSame('', $tester->getDisplay());
    }

    public function testBothCliEntrypointsAndHelp(): void
    {
        $this->module('Billing');
        $this->source->write('config/packages/framework.yaml', 'framework: {secret: graph-test, test: true}');
        $this->source->write('config/services.yaml', 'services: {}');
        $kernel = new TestApplicationKernel($this->source);
        try {
            foreach ([new Application($kernel), new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel)] as $app) {
                $app->setAutoExit(false);
                $tester = new ApplicationTester($app);
                self::assertSame(0, $tester->run(['command' => 'sillbeam:graph', '--format' => 'mermaid']));
                self::assertStringStartsWith('flowchart LR', $tester->getDisplay());
                self::assertSame(0, $tester->run(['command' => 'sillbeam:graph', '--help' => true]));
                self::assertStringContainsString('--format', $tester->getDisplay());
            }
        } finally {
            $kernel->shutdown();
        }
    }

    public function testDeclaredObservationsAreNotRenderedTwiceAndEdgesStaySorted(): void
    {
        $b = $this->module('B');
        $c = $this->module('C');
        $this->module('A', [$c, $b]);
        $port = $this->source->php('src/Module/B/Port.php', 'interface Port {}');
        $this->source->php('src/Module/A/Consumer.php', 'final class Consumer { public \\'.$port.' $port; }');
        $report = $this->report();
        $text = $this->command($report)->getDisplay();
        self::assertStringContainsString('m0001 -->|declared| m0002', $text);
        self::assertStringContainsString('m0001 -->|declared| m0003', $text);
        self::assertStringNotContainsString('m0001 -.->|observed undeclared| m0002', $text);
        $data = $report->data;
        $data['modules'][0]['declared_dependencies'] = [$b, $c, $b];
        $data['modules'][0]['references'] = array_reverse($data['modules'][0]['references']);
        self::assertSame($text, $this->command(new ArchitectureReport($data, $report->presentation))->getDisplay());
    }

    public function testResultWithoutRuntimeGraphDoesNotClaimValidatedSnapshot(): void
    {
        $this->module('Billing');
        $analysis = $this->analysis();
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult(discovery: $analysis->discovery));
        $text = $this->command($report)->getDisplay();
        self::assertStringContainsString('active=unknown', $text);
        self::assertStringNotContainsString('validated architecture snapshot', $text);
    }

    public function testMissingReferenceMapCannotBeCalledCompleteEvenForCompleteAnalysis(): void
    {
        $this->module('Billing');
        $analysis = $this->analysis();
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult(architecture: $analysis->architecture, discovery: $analysis->discovery));
        self::assertStringContainsString('Reference evidence incomplete', $this->command($report)->getDisplay());
        $report = new ArchitectureReport($this->report()->data);
        self::assertStringContainsString('Reference evidence incomplete', $this->command($report)->getDisplay());
    }

    public function testDuplicateLogicalIdsAndUnsafeProjectLabelsRetainDistinctNodes(): void
    {
        $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(id: "same")] final class AModule {}');
        $this->source->php('src/Module/B/BModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(id: "same")] final class BModule {}');
        $this->project('Shop');
        $report = $this->report();
        $data = $report->data;
        $data['projects'][0]['id'] = "Shop\"]\nend\nclick attack";
        $tester = $this->command(new ArchitectureReport($data, $report->presentation));
        self::assertSame(2, $tester->getStatusCode());
        self::assertStringContainsString('m0001["same |', $tester->getDisplay());
        self::assertStringContainsString('m0002["same |', $tester->getDisplay());
        self::assertStringContainsString('Shop#34;#93; end click attack', $tester->getDisplay());
        self::assertStringNotContainsString("\nclick", $tester->getDisplay());
        self::assertStringContainsString('membership=unknown', $tester->getDisplay());
        self::assertStringNotContainsString('-.->|active membership|', $tester->getDisplay());
    }

    public function testCoreConsoleProcessExportsBeforeBrokenContainerCompilation(): void
    {
        $this->module('Billing', ['Missing\\Module']);
        $kernelClass = $this->source->php('src/Kernel.php', 'final class Kernel extends \\Symfony\\Component\\HttpKernel\\Kernel { use \\Symfony\\Bundle\\FrameworkBundle\\Kernel\\MicroKernelTrait; }');
        $this->source->write('config/bundles.php', '<?php return [\\Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle::class => ["all" => true], \\Sillbeam\\Core\\SillbeamCoreBundle::class => ["all" => true]];');
        $this->source->write('config/packages/framework.yaml', 'framework: {secret: graph-process}');
        $this->source->write('config/services.yaml', "services:\n    broken: {class: stdClass, arguments: ['@missing_service']}\n");
        $autoload = var_export(\dirname(__DIR__, 2).'/vendor/autoload.php', true);
        $this->source->write('bin/console', '<?php require '.$autoload.'; require \\dirname(__DIR__)."/src/Kernel.php"; $app = new \\Sillbeam\\Core\\Console\\Application(new \\'.$kernelClass.'("test", true)); $app->run();');
        foreach ([
            [['sillbeam:graph', '--format=mermaid'], 1],
            [['--format', 'mermaid', 'sillbeam:graph'], 1],
            [['sillbeam:graph', '--format=mermaid', '--quiet'], 1],
            [['sillbeam:graph', '--help'], 0],
            [['sillbeam:graph', '--format=json'], 2],
            [['sillbeam:graph', '--format'], 2],
            [['sillbeam:graph', '--unknown'], 2],
        ] as [$arguments, $expected]) {
            $process = proc_open([\PHP_BINARY, $this->source->directory.'/bin/console', ...$arguments, '--ansi'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, null, ['SHELL_VERBOSITY' => '0'] + getenv());
            self::assertIsResource($process);
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame($expected, proc_close($process), $stdout.$stderr);
            if (2 === $expected) {
                self::assertSame('', $stdout);
                self::assertStringContainsString('sillbeam:graph:', $stderr);
            } else {
                self::assertSame('', $stderr);
                if (\in_array('--quiet', $arguments, true)) {
                    self::assertSame('', $stdout);
                } elseif (1 === $expected) {
                    self::assertStringStartsWith('flowchart LR', $stdout);
                    self::assertStringContainsString('active=unknown', $stdout);
                    self::assertStringNotContainsString("\033", $stdout);
                } else {
                    self::assertStringContainsString('--format', $stdout);
                }
            }
        }
        $kernel = new TestApplicationKernel($this->source);
        $app = new Application($kernel);
        $app->setAutoExit(false);
        self::assertSame(1, (new ApplicationTester($app))->run(['command' => 'sillbeam:graph']));
        self::assertFalse((new \ReflectionProperty($kernel, 'booted'))->getValue($kernel));
    }

    /** @param list<string> $dependencies
     * @return class-string
     */
    private function module(string $name, array $dependencies = [], ?string $project = null): string
    {
        $path = null === $project ? 'Module/'.$name : 'Project/'.$project.'/Module/'.$name;

        return $this->source->php('src/'.$path.'/'.$name.'Module.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: ['.implode(', ', array_map(static fn (string $class): string => '\\'.$class.'::class', $dependencies)).'])] final class '.$name.'Module {}');
    }

    /** @param list<class-string> $modules */
    private function project(string $name, array $modules = []): void
    {
        $this->source->php('src/Project/'.$name.'/'.$name.'Project.php', '#[\\Sillbeam\\Core\\Project\\Attribute\\SillbeamProject(modules: ['.implode(', ', array_map(static fn (string $class): string => '\\'.$class.'::class', $modules)).'])] final class '.$name.'Project {}');
    }

    /** @param array<string, mixed> $config */
    private function analysis(array $config = []): ArchitectureAnalysisResult
    {
        return (new ArchitectureAnalyzer())->analyze(new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\'), $config);
    }

    /** @param array<string, mixed> $config */
    private function report(array $config = []): ArchitectureReport
    {
        return ArchitectureReport::fromAnalysis($this->analysis($config), $config);
    }

    private function command(ArchitectureReport $report): CommandTester
    {
        $tester = new CommandTester(new GraphCommand($report));
        $tester->execute(['--format' => 'mermaid'], ['decorated' => true]);

        return $tester;
    }
}
