<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\ArchitectureCommand;
use Symfony\Component\Console\Tester\CommandTester;

final class ArchitectureReportTest extends TestCase
{
    public function testStatusAndSchemaAreStableForValidViolationsAndUnexecutableResults(): void
    {
        $schema = json_decode((string) file_get_contents(\dirname(__DIR__, 2).'/docs/reference/architecture-report.schema.json'), true, flags: \JSON_THROW_ON_ERROR);
        $reports = [
            [ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult()), 0, 'valid'],
            [ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([new ArchitectureFinding('dependency.unknown', 'Unknown dependency.')])), 1, 'violations'],
            [ArchitectureReport::unavailable(new \RuntimeException('Cannot load configuration.')), 2, 'unavailable'],
        ];
        foreach ($reports as [$report, $exit, $status]) {
            self::assertSame($exit, $report->exitCode());
            $data = json_decode($report->toJson(), true, flags: \JSON_THROW_ON_ERROR);
            self::assertSame($schema['required'], array_keys($data));
            self::assertSame($status, $data['status']);
        }
        $json = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([new ArchitectureFinding('dependency.unknown', 'Unknown dependency.')]))->toJson();
        self::assertInstanceOf(\stdClass::class, json_decode($json, flags: \JSON_THROW_ON_ERROR)->findings[0]->context);
    }

    public function testRemediationAndLocationsAreSharedByTextJsonAndExceptions(): void
    {
        $finding = new ArchitectureFinding('dependency.unknown', 'Unknown dependency.', ['module' => 'App\\HelloModule', 'file' => '/app/src/HelloModule.php', 'line' => 12]);
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([$finding]));
        $json = json_decode($report->toJson(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame(1, $json['schema_version']);
        self::assertSame($finding->suggestion(), $json['findings'][0]['context']['suggestion']);
        self::assertSame(12, $json['findings'][0]['context']['line']);
        $tester = new CommandTester(new ArchitectureCommand($report));
        self::assertSame(1, $tester->execute([]));
        self::assertStringContainsString('App\\HelloModule', $tester->getDisplay());
        self::assertStringContainsString('src/HelloModule.php:12', $tester->getDisplay());
        self::assertStringContainsString('POSSIBLE NEXT STEPS', $tester->getDisplay());
        self::assertSame(1, $tester->execute([], ['verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE]));
        self::assertStringContainsString('/app/src/HelloModule.php:12', $tester->getDisplay());
        self::assertStringContainsString('module=App\\HelloModule', $finding->describe());
        self::assertStringContainsString('line=12', $finding->describe());
        self::assertNull((new ArchitectureFinding('custom.rule', 'Custom finding.'))->suggestion());
    }

    public function testTextGroupsFindingsAndShowsSummaryBeforeDetails(): void
    {
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([
            new ArchitectureFinding('dependency.unknown', 'First unknown module.', ['module' => 'App\\A', 'file' => '/app/A.php', 'line' => 3]),
            new ArchitectureFinding('dependency.unknown', 'Other unknown module.', ['module' => 'App\\B']),
            new ArchitectureFinding('dependency.unknown', 'Second unknown module.', ['module' => 'App\\A']),
            new ArchitectureFinding('project.host_required', 'Project needs a host.', ['project' => 'App\\Shop']),
        ]));
        $tester = new CommandTester(new ArchitectureCommand($report));
        self::assertSame(1, $tester->execute([]));
        $text = $tester->getDisplay();
        self::assertStringContainsString('4 findings · 4 diagnoses', $text);
        self::assertLessThan(strpos($text, 'WHAT IS WRONG'), strpos($text, '4 findings'));
        self::assertSame(1, substr_count($text, 'WHAT IS WRONG'));
        self::assertStringContainsString('Unknown module dependency', $text);
        self::assertSame(1, $tester->execute(['--all' => true]));
        $text = $tester->getDisplay();
        foreach (['First unknown module.', 'Second unknown module.', 'Other unknown module.', 'Project needs a host.'] as $message) {
            self::assertStringContainsString($message, $text);
        }
        self::assertSame(4, substr_count($text, 'ORIGINAL FINDINGS'));
        self::assertStringContainsString('app/A.php:3', $text);
        self::assertStringContainsString('POSSIBLE NEXT STEPS', $text);
    }

    public function testProviderFailuresUseCleanJsonAndExitTwo(): void
    {
        $tester = new CommandTester(new ArchitectureCommand(static function (): ArchitectureReport {
            echo 'unexpected output';
            throw new \RuntimeException('Cannot load configuration.');
        }));
        self::assertSame(2, $tester->execute(['--format' => 'json'], ['decorated' => true]));
        $json = $tester->getDisplay();
        self::assertStringNotContainsString("\033", $json);
        self::assertStringNotContainsString('unexpected output', $json);
        self::assertSame('Cannot load configuration.', json_decode($json, true, flags: \JSON_THROW_ON_ERROR)['findings'][0]['message']);
    }

    public function testUnknownFormatReturnsTwoWithoutRunningAnalysis(): void
    {
        $tester = new CommandTester(new ArchitectureCommand(static function (): ArchitectureReport {
            self::fail('Unsupported output format must not run analysis.');
        }));
        self::assertSame(2, $tester->execute(['--format' => 'xml']));
        self::assertStringContainsString('Supported formats: text, json.', $tester->getDisplay());
    }
}
