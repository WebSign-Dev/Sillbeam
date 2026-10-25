<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\ReportCommand;
use Sillbeam\Core\Architecture\Console\ReportRenderer;
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
                self::assertStringContainsString('**Next steps**', $markdown);
            }
        }
    }

    public function testAllDiagnosesRetainStructuredOriginalEvidenceAndJson(): void
    {
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([
            new ArchitectureFinding('code.unknown_class', 'Missing Café type <unsafe> | value.', ['module' => 'App\\UserModule', 'class' => 'App\\Missing', 'file' => '/app/日本/Café|[x].php', 'line' => 17]),
            new ArchitectureFinding('dependency.unknown', 'Unknown target.', ['module' => 'App\\CatalogModule', 'target' => 'App\\Absent', 'file' => '/app/CatalogModule.php']),
        ]));
        $before = $report->toJson();
        $text = (new ReportRenderer($report))->markdown('test', new \DateTimeImmutable('2026-10-25T10:00:00+02:00'));
        self::assertStringContainsString('Checked at: 2026-10-25T08:00:00+00:00', $text);
        foreach (['Analysis complete: yes', 'Findings: 2', 'Diagnoses: 2', '## Architecture overview', '## Diagnoses', '### 1.', '### 2.', '**Problem**', '**Why it matters**', '**Next steps**', '**Technical evidence**', 'code.unknown\\_class', 'dependency.unknown', 'Café', '日本', '17', 'App\\\\Absent'] as $expected) {
            self::assertStringContainsString($expected, $text);
        }
        self::assertStringNotContainsString('<unsafe>', $text);
        self::assertStringNotContainsString("\033", $text);
        self::assertSame($before, $report->toJson());
        self::assertSame(2, substr_count($text, '**Original finding '));
    }

    public function testManyFindingsUseTheSharedGroupsWithoutLosingLocations(): void
    {
        $findings = [];
        for ($line = 1; $line <= 20; ++$line) {
            $findings[] = new ArchitectureFinding('code.unknown_class', 'Unresolved type '.$line, ['module' => 'App\\UserModule', 'class' => 'App\\Missing', 'file' => '/app/User.php', 'line' => $line]);
        }
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult($findings));
        $text = (new ReportRenderer($report))->markdown('source', new \DateTimeImmutable());
        self::assertStringContainsString('Findings: 20 · Diagnoses: '.\count($report->presentation['issues']), $text);
        self::assertSame(20, substr_count($text, '**Original finding '));
        foreach ($report->presentation['issues'] as $issue) {
            self::assertStringContainsString(str_replace(['\\', '-'], ['\\\\', '\\-'], $issue['problem']), $text);
            self::assertStringContainsString(str_replace(['\\', '-'], ['\\\\', '\\-'], $issue['impact']), $text);
        }
        foreach (range(1, 20) as $line) {
            self::assertStringContainsString('/app/User.php:'.$line, $text);
        }
    }

    public function testCustomProviderRetainsEveryOriginalContextIncludingSuggestions(): void
    {
        $data = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([
            new ArchitectureFinding('custom.rule', 'Original message', ['suggestion' => 'PRESERVE_FIRST', 'file' => '/app/Original.php', 'line' => 3]),
            new ArchitectureFinding('custom.rule', 'Original message', ['suggestion' => 'PRESERVE_SECOND', 'file' => '/app/Original.php', 'line' => 3]),
        ]))->data;
        $text = (new ReportRenderer(new ArchitectureReport($data)))->markdown('source', new \DateTimeImmutable());
        self::assertSame(2, substr_count($text, '**Original finding '));
        self::assertStringContainsString('suggestion: PRESERVE\\_FIRST', $text);
        self::assertStringContainsString('suggestion: PRESERVE\\_SECOND', $text);
    }

    public function testIncompleteReportDoesNotClaimAnApprovedGraph(): void
    {
        $report = ArchitectureReport::unavailable(new \RuntimeException('Broken <config>.'));
        $text = (new ReportRenderer($report))->markdown('source', new \DateTimeImmutable());
        foreach (['Status: unavailable', 'Analysis complete: no', 'Analysis incomplete', 'activation and graph validity are unknown', 'report.execution', 'Broken'] as $expected) {
            self::assertStringContainsString($expected, $text);
        }
    }

    public function testCouplingsAreOptInAndUseTheSharedPresentation(): void
    {
        $report = new ArchitectureReport([
            'schema_version' => 1, 'status' => 'valid', 'valid' => true, 'complete' => true, 'findings' => [], 'projects' => [],
            'modules' => [['id' => 'user', 'class' => 'App\\UserModule', 'scope' => 'shared', 'project' => null, 'enabled' => true, 'active' => true, 'descriptor' => '/app/UserModule.php', 'declared_dependencies' => [], 'application_references' => [['class' => 'App\\Mail', 'file' => '/app/Consumer.php', 'line' => 9, 'target_file' => '/app/Mail.php']]]],
        ]);
        $tester = new CommandTester(new ReportCommand($report, $this->source->directory));
        self::assertSame(0, $tester->execute([]));
        self::assertStringNotContainsString('Application couplings', (string) file_get_contents($this->source->directory.'/architecture-report.md'));
        self::assertSame(0, $tester->execute(['--couplings' => true]));
        $text = (string) file_get_contents($this->source->directory.'/architecture-report.md');
        foreach (['Application couplings (informational)', 'not architecture violations', 'Mail.php', 'Consumer.php:9', 'App\\\\Mail'] as $expected) {
            self::assertStringContainsString($expected, $text);
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
            new ArchitectureFinding('custom.rule', '1. <script>bad</script> [link](https://example.test) **bold** &lt;img&gt; Café | 日本'),
        ]));
        $tester = new CommandTester(new ReportCommand($report, $this->source->directory));
        self::assertSame(1, $tester->execute([]));
        $markdown = (string) file_get_contents($this->source->directory.'/architecture-report.md');
        self::assertStringContainsString('1\\. \\<script', $markdown);
        self::assertStringNotContainsString('<script>', $markdown);
        self::assertStringNotContainsString('**bold**', $markdown);
        self::assertStringContainsString('\\<script\\>', $markdown);
        self::assertStringContainsString('&amp;lt;img&amp;gt;', $markdown);
        self::assertStringContainsString('Café \\| 日本', $markdown);
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
