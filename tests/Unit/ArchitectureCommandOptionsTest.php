<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\ArchitectureCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class ArchitectureCommandOptionsTest extends TestCase
{
    public function testDefaultIndexFocusAndAllRetainEveryOriginalFinding(): void
    {
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([
            new ArchitectureFinding('dependency.unknown', 'FIRST ORIGINAL', ['module' => 'App\\AModule', 'target' => 'App\\MissingOne', 'file' => '/consumer/src/Module/A/A.php', 'line' => 3]),
            new ArchitectureFinding('dependency.unknown', 'SECOND ORIGINAL', ['module' => 'App\\BModule', 'target' => 'App\\MissingTwo', 'file' => '/consumer/src/Module/B/B.php', 'line' => 7]),
        ]));
        $tester = new CommandTester(new ArchitectureCommand($report));
        self::assertSame(1, $tester->execute([]));
        $default = $tester->getDisplay();
        self::assertStringContainsString('2 findings · 2 diagnoses', $default);
        self::assertStringContainsString('DIAGNOSIS OVERVIEW', $default);
        self::assertSame(1, substr_count($default, 'WHAT IS WRONG'));
        self::assertStringNotContainsString('APPLICATION COUPLINGS', $default);
        self::assertStringNotContainsString('/consumer/', $default);
        self::assertStringNotContainsString('dependency.unknown', $default);
        self::assertSame(1, $tester->execute(['--issue' => '2']));
        self::assertStringContainsString('SECOND ORIGINAL', $tester->getDisplay());
        self::assertStringNotContainsString('FIRST ORIGINAL', $tester->getDisplay());
        self::assertSame(1, $tester->execute(['--all' => true]));
        self::assertSame(2, substr_count($tester->getDisplay(), 'WHAT IS WRONG'));
        self::assertStringContainsString('FIRST ORIGINAL', $tester->getDisplay());
        self::assertStringContainsString('SECOND ORIGINAL', $tester->getDisplay());
        self::assertSame(1, $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]));
        self::assertStringContainsString('dependency.unknown', $tester->getDisplay());
        self::assertStringContainsString('/consumer/src/Module/A/A.php:3', $tester->getDisplay());
    }

    /** @return iterable<string, array<array<string, mixed>>> */
    public static function invalid(): iterable
    {
        yield 'unknown option' => [['--unknown' => true]];
        yield 'unknown json option' => [['--format' => 'json', '--unknown' => true]];
        yield 'unknown format' => [['--format' => 'xml']];
        yield 'noninteger issue' => [['--issue' => 'abc']];
        yield 'negative issue' => [['--issue' => '-1']];
        yield 'zero issue' => [['--issue' => '0']];
        yield 'two selectors' => [['--issue' => '1', '--all' => true]];
        yield 'json and all' => [['--format' => 'json', '--all' => true]];
        yield 'json and issue' => [['--format' => 'json', '--issue' => '1']];
        yield 'json and couplings' => [['--format' => 'json', '--couplings' => true]];
    }

    /** @param array<string, mixed> $arguments */
    #[DataProvider('invalid')]
    public function testInvalidOptionsNeverRunTheProvider(array $arguments): void
    {
        $tester = new CommandTester(new ArchitectureCommand(static function (): ArchitectureReport {
            self::fail('Invalid usage must not analyze architecture.');
        }));
        self::assertSame(2, $tester->execute($arguments));
        if ('json' === ($arguments['--format'] ?? null)) {
            $data = json_decode($tester->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);
            self::assertSame(1, $data['schema_version']);
            self::assertSame('unavailable', $data['status']);
            self::assertSame('report.execution', $data['findings'][0]['rule']);
        } else {
            self::assertStringNotContainsString('Architecture violations found', $tester->getDisplay());
        }
    }

    public function testJsonIsUnchangedAndQuietSuppressesOutputWithoutChangingExitCode(): void
    {
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([new ArchitectureFinding('source.read', 'Bad <error>literal</error>'."\033[2J", ['file' => '/known.php'], 'fatal')]));
        $tester = new CommandTester(new ArchitectureCommand($report));
        self::assertSame(2, $tester->execute(['--format' => 'json'], ['decorated' => true, 'verbosity' => OutputInterface::VERBOSITY_DEBUG]));
        self::assertSame($report->toJson()."\n", $tester->getDisplay());
        self::assertSame(2, $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_QUIET]));
        self::assertSame('', $tester->getDisplay());
        self::assertSame(2, $tester->execute(['--format' => 'json'], ['verbosity' => OutputInterface::VERBOSITY_QUIET]));
        self::assertSame('', $tester->getDisplay());
        self::assertSame(2, $tester->execute([], ['decorated' => false]));
        self::assertStringNotContainsString("\033", $tester->getDisplay());
        self::assertStringContainsString('<error>literal</error>', $tester->getDisplay());
    }
}
