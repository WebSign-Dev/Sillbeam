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
