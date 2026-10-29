<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\InspectModuleCommand;
use Sillbeam\Core\Architecture\DiscoveryOptions;
use Sillbeam\Core\Console\Application;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Sillbeam\Core\Tests\Support\TestApplicationKernel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;

final class InspectModuleTest extends TestCase
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

    public function testModuleOnlyShowsIdentityConfigurationAndValidatedActivation(): void
    {
        $billing = $this->module('Billing');
        $tester = $this->inspect('Billing');
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        foreach (['SILLBEAM CORE', 'MODULE', 'ID: billing', $billing, 'Scope: shared', 'Owner Project: none', 'Configuration key: shared:billing', 'Override: none', 'Enabled: true', 'Active: true', 'State: active', 'No related architecture findings'] as $text) {
            self::assertStringContainsString($text, $this->unwrapped($tester));
        }
        self::assertSame($tester->getDisplay(), $this->inspect('\\'.$billing)->getDisplay());
    }

    public function testDeclaredObservedAndIncomingDependenciesUseExistingEvidence(): void
    {
        $target = $this->module('Target');
        $source = $this->module('Source', [$target]);
        $port = $this->source->php('src/Module/Target/Port.php', 'interface Port {}');
        $this->source->php('src/Module/Source/Consumer.php', 'final class Consumer { public \\'.$port.' $port; }');
        $outgoing = $this->inspect('source', verbose: true);
        self::assertSame(0, $outgoing->getStatusCode(), $outgoing->getDisplay());
        self::assertStringContainsString('DECLARED DEPENDENCIES', $outgoing->getDisplay());
        self::assertStringContainsString('OBSERVED DEPENDENCIES', $outgoing->getDisplay());
        self::assertStringContainsString($target, $this->unwrapped($outgoing));
        self::assertStringContainsString('Consumer.php:1', $outgoing->getDisplay());
        $incoming = $this->inspect('target');
        self::assertStringContainsString('INCOMING DECLARED DEPENDENCIES', $incoming->getDisplay());
        self::assertStringContainsString('INCOMING OBSERVED REFERENCES', $incoming->getDisplay());
        self::assertStringContainsString($source, $this->unwrapped($incoming));
        self::assertStringNotContainsString('No incoming declared dependencies', $incoming->getDisplay());
    }

    public function testEnabledDisabledAndUnassignedAreDifferentFromActive(): void
    {
        $selected = $this->module('Selected');
        $this->module('Disabled');
        $this->module('Unused');
        $this->project('Shop', [$selected]);
        $config = ['modules' => ['shared:disabled' => ['enabled' => false]]];
        $disabled = $this->inspect('disabled', config: $config);
        self::assertSame(0, $disabled->getStatusCode(), $disabled->getDisplay());
        foreach (['Enabled: false', 'Active: false', 'State: disabled', 'Override: shared:disabled', 'Configured enabled override: false'] as $text) {
            self::assertStringContainsString($text, $disabled->getDisplay());
        }
        $unused = $this->inspect('unused', config: $config);
        foreach (['Enabled: true', 'Active: false', 'State: unassigned'] as $text) {
            self::assertStringContainsString($text, $unused->getDisplay());
        }
    }

    public function testSharedModuleCanBeActiveGloballyAndUnassignedToSelectedProject(): void
    {
        $billing = $this->module('Billing');
        $shop = $this->project('Shop', [$billing]);
        $admin = $this->project('Admin');
        $config = ['projects' => ['shop' => ['hosts' => ['shop.test']], 'admin' => ['hosts' => ['admin.test']]]];
        $tester = $this->inspect('billing', 'Admin', $config);
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        foreach (['Inspection Project: admin', 'Global active: true', 'Active: false', 'State: unassigned', $shop, $admin] as $text) {
            self::assertStringContainsString($text, $this->unwrapped($tester));
        }
    }

    public function testPrivateNameConflictsRequireProjectOrDescriptorClass(): void
    {
        $shop = $this->project('Shop');
        $admin = $this->project('Admin');
        $one = $this->module('Billing', [], 'Shop');
        $two = $this->module('Billing', [], 'Admin');
        $config = ['projects' => ['shop' => ['hosts' => ['shop.test']], 'admin' => ['hosts' => ['admin.test']]]];
        $ambiguous = $this->inspect('Billing', config: $config);
        self::assertSame(2, $ambiguous->getStatusCode());
        foreach (['Ambiguous module', '--project', $one, $two] as $text) {
            self::assertStringContainsString($text, $this->unwrapped($ambiguous));
        }
        foreach (['shop', $shop] as $selector) {
            $chosen = $this->inspect('billing', $selector, $config);
            self::assertSame(0, $chosen->getStatusCode(), $chosen->getDisplay());
            foreach (['Scope: private', 'Owner Project: shop', 'Configuration key: project:shop:billing', 'State: active', $one] as $text) {
                self::assertStringContainsString($text, $this->unwrapped($chosen));
            }
            self::assertStringNotContainsString($two, $this->unwrapped($chosen));
        }
        self::assertSame(0, $this->inspect($two, config: $config)->getStatusCode());
        self::assertSame(2, $this->inspect($one, $admin, $config)->getStatusCode());
    }

    public function testSharedAndPrivateIdNeverSilentlyPreferOneCandidate(): void
    {
        $shared = $this->module('Billing');
        $this->project('Shop', [$shared]);
        $private = $this->module('Billing', [], 'Shop');
        $tester = $this->inspect('billing', 'shop');
        self::assertSame(2, $tester->getStatusCode());
        self::assertStringContainsString('Ambiguous module', $tester->getDisplay());
        self::assertSame(0, $this->inspect($private, 'shop')->getStatusCode());
        self::assertSame(0, $this->inspect($shared, 'shop')->getStatusCode());
    }

    public function testCyclesRemainInspectableWithoutPublishingCandidateActivation(): void
    {
        $a = $this->source->namespace.'\\Module\\A\\AModule';
        $b = $this->module('B', [$a]);
        $this->module('A', [$b]);
        $tester = $this->inspect('B');
        self::assertSame(1, $tester->getStatusCode(), $tester->getDisplay());
        self::assertStringContainsString('dependency.cycle', $tester->getDisplay());
        self::assertStringContainsString('Active: unknown', $tester->getDisplay());
        self::assertStringContainsString('State: unknown', $tester->getDisplay());
        self::assertStringNotContainsString('State: active', $tester->getDisplay());
        self::assertStringContainsString('Enabled: true', $tester->getDisplay());
    }

    public function testDisabledDirectDependencyIsRelevantToItsDeclaringModule(): void
    {
        $disabled = $this->module('Disabled');
        $this->module('Billing', [$disabled]);
        $tester = $this->inspect('billing', config: ['modules' => ['disabled' => ['enabled' => false]]]);
        self::assertSame(1, $tester->getStatusCode(), $tester->getDisplay());
        self::assertStringContainsString('dependency.disabled', $tester->getDisplay());
        self::assertStringNotContainsString('No related architecture findings', $this->unwrapped($tester));
        self::assertStringContainsString('Active: unknown', $tester->getDisplay());
    }

    public function testPartialCatalogDoesNotInventActivationOrAbsenceOfObservedDependencies(): void
    {
        $this->module('Billing');
        $analysis = (new ArchitectureAnalyzer())->analyze($this->options());
        $partial = new ArchitectureAnalysisResult([new ArchitectureFinding('source.read', 'A source file is unavailable', [], 'fatal')], discovery: $analysis->discovery);
        $report = ArchitectureReport::fromAnalysis($partial, ['modules' => ['billing' => ['enabled' => false]]]);
        $tester = new CommandTester(new InspectModuleCommand($report));
        self::assertSame(2, $tester->execute(['module' => 'Billing']));
        foreach (['Enabled: unknown', 'Active: unknown', 'State: unknown', 'Reference evidence is incomplete', 'source.read'] as $text) {
            self::assertStringContainsString($text, $this->unwrapped($tester));
        }
        self::assertStringNotContainsString('State: disabled', $tester->getDisplay());
    }

    public function testCanonicalAndPrivateOverridesRemainSeparateFromLegacySharedOverrides(): void
    {
        $shared = $this->module('Billing');
        $this->project('Shop');
        $private = $this->module('Billing', [], 'Shop');
        $config = ['modules' => ['billing' => ['enabled' => true], 'shared:billing' => ['enabled' => false], 'project:shop:billing' => ['enabled' => false]]];
        foreach ([[$shared, 'shared:billing'], [$private, 'project:shop:billing']] as [$class, $key]) {
            $tester = $this->inspect($class, config: $config);
            self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
            self::assertStringContainsString('Override: '.$key, $this->unwrapped($tester));
            self::assertStringContainsString('State: disabled', $tester->getDisplay());
        }
        $report = $this->report($config);
        self::assertSame($report->toJson(), (new ArchitectureReport($report->data))->toJson());
        self::assertStringNotContainsString('module_inspection', $report->toJson());
        $this->module('None');
        $legacy = $this->inspect('none', config: ['modules' => ['none' => ['enabled' => false]]]);
        self::assertSame(0, $legacy->getStatusCode());
        self::assertStringContainsString('Override: none (configured key)', $this->unwrapped($legacy));
    }

    public function testDuplicateLogicalIdentitiesDoNotInventAConfigurationKey(): void
    {
        $a = $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(id: "billing")] final class AModule {}');
        $this->source->php('src/Module/B/BModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(id: "billing")] final class BModule {}');
        self::assertSame(2, $this->inspect('billing')->getStatusCode());
        $tester = $this->inspect($a);
        self::assertSame(2, $tester->getStatusCode());
        self::assertStringContainsString('Configuration key: unknown', $tester->getDisplay());
        self::assertStringContainsString('State: unknown', $tester->getDisplay());
        self::assertStringContainsString('module.duplicate_key', $tester->getDisplay());
    }

    public function testUnavailableCatalogExplainsBlockersInsteadOfClaimingUnknownModule(): void
    {
        $this->source->write('src/Broken.php', '<?php class {');
        $tester = $this->inspect('billing');
        self::assertSame(2, $tester->getStatusCode());
        self::assertStringContainsString('Module catalog unavailable', $tester->getDisplay());
        self::assertStringContainsString('source.parse', $tester->getDisplay());
        self::assertStringNotContainsString('Unknown module', $tester->getDisplay());
    }

    public function testUnrelatedFindingsDoNotBecomeModuleFindingsAndGlobalFailureStillShows(): void
    {
        $this->module('Billing');
        $this->module('Other', ['Missing\\Module']);
        $tester = $this->inspect('billing');
        self::assertSame(1, $tester->getStatusCode());
        self::assertStringNotContainsString('Missing\\Module', $this->unwrapped($tester));
        self::assertStringContainsString('No related architecture findings', $this->unwrapped($tester));
        self::assertStringContainsString('sillbeam:architecture --all', $this->unwrapped($tester));
    }

    public function testInvalidOptionsDoNotInvokeProviderAndQuietRetainsExit(): void
    {
        foreach ([['--format' => 'json', 'module' => 'billing'], ['module' => 'billing', '--unknown' => true], []] as $arguments) {
            $called = false;
            $tester = new CommandTester(new InspectModuleCommand(static function () use (&$called): ArchitectureReport {
                $called = true;

                return ArchitectureReport::unavailable(new \RuntimeException('Unexpected provider invocation'));
            }));
            self::assertSame(2, $tester->execute($arguments));
            self::assertFalse($called);
        }
        $this->module('Billing');
        $report = $this->report();
        $tester = new CommandTester(new InspectModuleCommand($report));
        self::assertSame(0, $tester->execute(['module' => 'billing'], ['verbosity' => OutputInterface::VERBOSITY_QUIET]));
        self::assertSame('', $tester->getDisplay());
        self::assertSame(2, $this->inspect('missing')->getStatusCode());
        self::assertSame(2, $this->inspect('billing', 'missing')->getStatusCode());
    }

    public function testAdapterWorksWithInvalidArchitectureWithoutBootingKernel(): void
    {
        $this->module('Billing', ['Missing\\Module']);
        $this->source->write('config/packages/framework.yaml', 'framework: {secret: inspect-test, test: true}');
        $this->source->write('config/services.yaml', 'services: {}');
        $kernel = new TestApplicationKernel($this->source);
        $app = new Application($kernel);
        $app->setAutoExit(false);
        $tester = new ApplicationTester($app);
        try {
            self::assertSame(1, $tester->run(['command' => 'sillbeam:inspect:module', 'module' => 'Billing']));
            self::assertStringContainsString('dependency.unknown', $tester->getDisplay());
            self::assertStringContainsString('Active: unknown', $tester->getDisplay());
            self::assertFalse((new \ReflectionProperty($kernel, 'booted'))->getValue($kernel));
        } finally {
            $kernel->shutdown();
        }
    }

    public function testRegularSymfonyRegistersCommandAndHelp(): void
    {
        $this->module('Billing');
        $this->source->write('config/packages/framework.yaml', 'framework: {secret: inspect-test, test: true}');
        $this->source->write('config/services.yaml', 'services: {}');
        $kernel = new TestApplicationKernel($this->source);
        $native = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $native->setAutoExit(false);
        $tester = new ApplicationTester($native);
        try {
            self::assertSame(0, $tester->run(['command' => 'sillbeam:inspect:module', 'module' => 'billing']));
            self::assertStringContainsString('State: active', $tester->getDisplay());
            self::assertSame(0, $tester->run(['command' => 'sillbeam:inspect:module', '--help' => true]));
            self::assertStringContainsString('--project', $tester->getDisplay());
        } finally {
            $kernel->shutdown();
        }
    }

    public function testCoreConsoleProcessInspectsViolationsWithoutCompilingBrokenServices(): void
    {
        $this->module('Billing', ['Missing\\Module']);
        $this->project('Shop');
        $kernelClass = $this->source->php('src/Kernel.php', 'final class Kernel extends \\Symfony\\Component\\HttpKernel\\Kernel { use \\Symfony\\Bundle\\FrameworkBundle\\Kernel\\MicroKernelTrait; }');
        $this->source->write('config/bundles.php', '<?php return [\\Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle::class => ["all" => true], \\Sillbeam\\Core\\SillbeamCoreBundle::class => ["all" => true]];');
        $this->source->write('config/packages/framework.yaml', 'framework: {secret: inspect-process}');
        $this->source->write('config/services.yaml', "services:\n    broken: {class: stdClass, arguments: ['@missing_service']}\n");
        $autoload = var_export(\dirname(__DIR__, 2).'/vendor/autoload.php', true);
        $this->source->write('bin/console', '<?php require '.$autoload.'; require \\dirname(__DIR__)."/src/Kernel.php"; $app = new \\Sillbeam\\Core\\Console\\Application(new \\'.$kernelClass.'("test", true)); $app->run();');
        foreach ([
            [['sillbeam:inspect:module', 'Billing', '--project=shop'], 1],
            [['--project', 'shop', 'sillbeam:inspect:module', 'Billing'], 1],
            [['sillbeam:inspect:module', 'Billing', '--quiet'], 1],
            [['sillbeam:inspect:module', '--help'], 0],
            [['sillbeam:inspect:module', 'Billing', '--format=json'], 2],
            [['sillbeam:inspect:module', 'Billing', '--project'], 2],
        ] as [$arguments, $expected]) {
            $process = proc_open([\PHP_BINARY, $this->source->directory.'/bin/console', ...$arguments, '--no-ansi'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, null, ['SHELL_VERBOSITY' => '0'] + getenv());
            self::assertIsResource($process);
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame($expected, proc_close($process), $stdout.$stderr);
            self::assertSame('', $stderr);
            self::assertStringNotContainsString('schema_version', $stdout);
            if (\in_array('--quiet', $arguments, true)) {
                self::assertSame('', $stdout);
            } elseif (1 === $expected) {
                self::assertStringContainsString('ID: billing', $stdout);
                self::assertStringContainsString('State: unknown', $stdout);
                self::assertStringContainsString('dependency.unknown', $stdout);
            } elseif (0 === $expected) {
                self::assertStringContainsString('--project', $stdout);
            }
        }
    }

    /** @param list<string> $dependencies
     * @return class-string
     */
    private function module(string $name, array $dependencies = [], ?string $project = null): string
    {
        $attribute = '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: ['.implode(', ', array_map(static fn (string $class): string => '\\'.$class.'::class', $dependencies)).'])]';
        $path = null === $project ? 'Module/'.$name : 'Project/'.$project.'/Module/'.$name;

        return $this->source->php('src/'.$path.'/'.$name.'Module.php', $attribute.' final class '.$name.'Module {}');
    }

    /** @param list<class-string> $modules
     * @return class-string
     */
    private function project(string $name, array $modules = []): string
    {
        return $this->source->php('src/Project/'.$name.'/'.$name.'Project.php', '#[\\Sillbeam\\Core\\Project\\Attribute\\SillbeamProject(modules: ['.implode(', ', array_map(static fn (string $class): string => '\\'.$class.'::class', $modules)).'])] final class '.$name.'Project {}');
    }

    private function options(): DiscoveryOptions
    {
        return new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\');
    }

    /** @param array<string, mixed> $config */
    private function report(array $config = []): ArchitectureReport
    {
        return ArchitectureReport::fromAnalysis((new ArchitectureAnalyzer())->analyze($this->options(), $config), $config, $this->source->directory);
    }

    /** @param array<string, mixed> $config */
    private function inspect(string $identifier, ?string $project = null, array $config = [], bool $verbose = false): CommandTester
    {
        $tester = new CommandTester(new InspectModuleCommand($this->report($config)));
        $arguments = ['module' => $identifier];
        if (null !== $project) {
            $arguments['--project'] = $project;
        }
        $tester->execute($arguments, ['verbosity' => $verbose ? OutputInterface::VERBOSITY_VERBOSE : OutputInterface::VERBOSITY_NORMAL]);

        return $tester;
    }

    private function unwrapped(CommandTester $tester): string
    {
        return preg_replace('/\s+/', ' ', $tester->getDisplay()) ?? '';
    }
}
