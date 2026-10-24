<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\ReportCommand;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Symfony\Component\Console\Tester\CommandTester;

final class MarkdownReportTest extends TestCase
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

    public function testWritesDefaultReportAndReturnsAnalysisExitCode(): void
    {
        foreach ([
            ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult()),
            ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([new ArchitectureFinding('dependency.unknown', 'Unknown dependency.', ['module' => 'App\\UserModule', 'file' => '/app/User.php', 'line' => 12])])),
            ArchitectureReport::unavailable(new \RuntimeException('Invalid configuration.')),
        ] as $report) {
            $tester = new CommandTester(new ReportCommand($report, $this->source->directory));
            self::assertSame($report->exitCode(), $tester->execute([]), $tester->getDisplay());
            $markdown = (string) file_get_contents($this->source->directory.'/architecture-report.md');
            self::assertStringContainsString('# Sillbeam architecture report', $markdown);
            self::assertStringContainsString('Sillbeam version:', $markdown);
            self::assertMatchesRegularExpression('/Checked at:.*\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00/', $markdown);
            self::assertStringContainsString('Status: '.$report->data['status'], $markdown);
            self::assertStringContainsString('Modules checked: 0', $markdown);
            self::assertStringContainsString('Projects checked: 0', $markdown);
            self::assertStringContainsString('Only Sillbeam architecture rules', $markdown);
            if (1 === $report->exitCode()) {
                self::assertStringContainsString('Violations: 1', $markdown);
                self::assertStringContainsString('/app/User.php:12', $markdown);
                self::assertStringContainsString('Correct the referenced module class', $markdown);
            }
        }
    }

    public function testCustomRelativeAndAbsoluteOutputPaths(): void
    {
        mkdir($this->source->directory.'/docs');
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult());
        foreach (['docs/custom.md', $this->source->directory.'/absolute.md'] as $path) {
            $tester = new CommandTester(new ReportCommand($report, $this->source->directory));
            self::assertSame(0, $tester->execute(['--output' => $path]));
            self::assertFileExists(str_starts_with($path, '/') ? $path : $this->source->directory.'/'.$path);
        }
    }

    public function testFailedReplacementPreservesExistingContentAndCleansTemporaryFile(): void
    {
        mkdir($this->source->directory.'/existing.md');
        $this->source->write('existing.md/keep', 'original');
        $tester = new CommandTester(new ReportCommand(ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult()), $this->source->directory));
        self::assertSame(2, $tester->execute(['--output' => 'existing.md']));
        self::assertStringContainsString('Cannot write architecture report', $tester->getDisplay());
        self::assertSame('original', file_get_contents($this->source->directory.'/existing.md/keep'));
        self::assertSame([], glob($this->source->directory.'/.sillbeam-report-*'));
        self::assertSame(2, $tester->execute(['--output' => 'missing/report.md']));
        self::assertDirectoryDoesNotExist($this->source->directory.'/missing');
    }

    public function testUnwritableDirectoryLeavesExistingReportIntact(): void
    {
        mkdir($this->source->directory.'/readonly');
        $path = $this->source->directory.'/readonly/report.md';
        file_put_contents($path, 'previous report');
        chmod(\dirname($path), 0555);
        try {
            if (is_writable(\dirname($path))) {
                self::markTestSkipped('The current user bypasses filesystem permissions.');
            }
            $tester = new CommandTester(new ReportCommand(ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult()), $this->source->directory));
            self::assertSame(2, $tester->execute(['--output' => $path]));
            self::assertSame('previous report', file_get_contents($path));
            self::assertSame([], glob(\dirname($path).'/.sillbeam-report-*'));
        } finally {
            chmod(\dirname($path), 0755);
        }
    }

    public function testMarkdownEscapesSourceContentInsteadOfEmbeddingHtmlOrFormatting(): void
    {
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([
            new ArchitectureFinding('custom.rule', '<script>bad</script> [link](https://example.test) **bold**'),
        ]));
        $tester = new CommandTester(new ReportCommand($report, $this->source->directory));
        self::assertSame(1, $tester->execute([]));
        $markdown = (string) file_get_contents($this->source->directory.'/architecture-report.md');
        self::assertStringNotContainsString('<script>', $markdown);
        self::assertStringNotContainsString('**bold**', $markdown);
        self::assertStringContainsString('\\<script\\>', $markdown);
    }

    public function testProviderFailureStillWritesUnavailableReportWithoutLeakingOutput(): void
    {
        $tester = new CommandTester(new ReportCommand(static function (): ArchitectureReport {
            echo 'unexpected config output';
            throw new \RuntimeException('Broken config.');
        }, $this->source->directory));
        self::assertSame(2, $tester->execute([]));
        self::assertStringNotContainsString('unexpected config output', $tester->getDisplay());
        self::assertStringContainsString('Broken config.', (string) file_get_contents($this->source->directory.'/architecture-report.md'));
    }
}
