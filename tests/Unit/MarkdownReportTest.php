<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\MarkdownCatalog;
use Sillbeam\Core\Architecture\Console\ReportCommand;
use Sillbeam\Core\Architecture\Console\ReportRenderer;
use Sillbeam\Core\Architecture\DiscoveryOptions;
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
                self::assertStringContainsString('/app/User.php', $markdown);
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
        foreach (['Analysis complete: yes', 'Findings: 2', 'Diagnoses: 2', '## Architecture Overview', '## Diagnoses & Resolutions', '### 1.', '### 2.', '**Problem**', '**Why it matters**', '**Next steps**', '## Technical Evidence', 'code.unknown\\_class', 'dependency.unknown', 'Café', '日本', '17', 'App\\\\Absent'] as $expected) {
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
            self::assertStringContainsString(str_replace(['\\', '-'], ['\\\\', '\\-'], explode(' Reported class:', $issue['problem'])[0]), $text);
            self::assertStringContainsString(str_replace(['\\', '-'], ['\\\\', '\\-'], $issue['impact']), $text);
        }
        foreach (range(1, 20) as $line) {
            self::assertStringContainsString('app/User.php:'.$line, $text);
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

    public function testOverviewUsesNamesAndMovesOriginalIdentitiesToEvidence(): void
    {
        $data = $this->catalogData();
        $text = (new ReportRenderer(new ArchitectureReport($data)))->markdown('test', new \DateTimeImmutable());
        $overview = explode('## Diagnoses & Resolutions', explode('## Architecture Overview', $text)[1])[0];
        self::assertStringContainsString('| Module | Scope | State | Dependencies |', $overview);
        self::assertStringContainsString('| User | Shared | Active | Catalog |', $overview);
        self::assertStringContainsString('| Shop / Secret | Private | Active | User |', $overview);
        self::assertStringContainsString('| Shop | shop.test | User |', $overview);
        self::assertStringNotContainsString('App', $overview);
        self::assertStringNotContainsString('/app/', $overview);
        self::assertStringNotContainsString('<br', $text);
        $evidence = explode('## Technical Evidence', $text)[1];
        self::assertStringContainsString('App\\\\Module\\\\User\\\\UserModule', $evidence);
        self::assertStringContainsString('/app/src/Module/User/UserModule.php', $evidence);
    }

    public function testDuplicateNamesRemainUnambiguousAndDeterministic(): void
    {
        $data = $this->catalogData();
        $data['projects'][] = ['id' => 'outlet', 'class' => 'Other\\ShopProject', 'descriptorFile' => '/app/Other/ShopProject.php', 'hosts' => [], 'declaredModules' => []];
        $data['modules'][] = ['id' => 'secret', 'class' => 'Other\\SecretModule', 'scope' => 'private', 'project' => 'Other\\ShopProject', 'active' => true, 'descriptor' => '/app/Other/SecretModule.php', 'declared_dependencies' => []];
        $data['modules'][] = ['id' => 'legacy-user', 'class' => 'Legacy\\UserModule', 'scope' => 'shared', 'project' => null, 'active' => true, 'descriptor' => '/app/Legacy/UserModule.php', 'declared_dependencies' => []];
        $renderer = static fn (array $d): string => (new ReportRenderer(new ArchitectureReport($d)))->markdown('test', new \DateTimeImmutable('2026-10-25T00:00:00Z'));
        $text = $renderer($data);
        foreach (['User \\[shared:user\\]', 'User \\[shared:legacy\\-user\\]', 'Shop \\[shop\\] / Secret', 'Shop \\[outlet\\] / Secret'] as $label) {
            self::assertStringContainsString($label, $text);
        }
        $data['modules'] = array_reverse($data['modules']);
        $data['projects'] = array_reverse($data['projects']);
        self::assertSame($text, $renderer($data));
    }

    public function testCouplingAppendixKeepsPrivateSourcesUnambiguous(): void
    {
        $data = $this->catalogData();
        $data['projects'][] = ['id' => 'outlet', 'class' => 'Other\\ShopProject', 'descriptorFile' => '/app/Other/ShopProject.php', 'hosts' => [], 'declaredModules' => []];
        $data['modules'][2]['application_references'] = [['class' => 'App\\Mail', 'file' => '/app/Secret/Mail.php', 'line' => 2, 'target_file' => '/app/Mail.php']];
        $data['modules'][] = ['id' => 'secret', 'class' => 'Other\\SecretModule', 'scope' => 'private', 'project' => 'Other\\ShopProject', 'active' => true, 'descriptor' => '/app/Other/SecretModule.php', 'declared_dependencies' => [], 'application_references' => [['class' => 'App\\Mail', 'file' => '/app/Other/Mail.php', 'line' => 7, 'target_file' => '/app/Mail.php']]];
        $text = (new ReportRenderer(new ArchitectureReport($data)))->markdown('test', new \DateTimeImmutable(), true);
        $appendix = explode('## Application couplings (informational)', $text)[1];
        self::assertStringContainsString('Shop \\[shop\\] / Secret →', $appendix);
        self::assertStringContainsString('Shop \\[outlet\\] / Secret →', $appendix);
        self::assertStringContainsString('/app/Secret/Mail.php:2', $appendix);
        self::assertStringContainsString('/app/Other/Mail.php:7', $appendix);
    }

    public function testLongListsStayCompleteOutsideTablesAndUnverifiedStatesStayUnknown(): void
    {
        $data = $this->catalogData();
        $data['valid'] = false;
        $data['complete'] = false;
        $data['status'] = 'unavailable';
        $data['modules'][0]['declared_dependencies'] = array_map(static fn (int $n): string => 'Missing\\DependencyWithAnExtremelyLongName'.$n.'Module', range(1, 8));
        $text = (new ReportRenderer(new ArchitectureReport($data)))->markdown('test', new \DateTimeImmutable());
        $overview = explode('## Diagnoses & Resolutions', explode('## Architecture Overview', $text)[1])[0];
        self::assertStringContainsString('| Unknown | 8 declared |', $overview);
        self::assertStringContainsString('#### Dependencies of User', $overview);
        self::assertStringNotContainsString('| Active |', $overview);
        foreach (range(1, 8) as $n) {
            self::assertStringContainsString('- DependencyWithAnExtremelyLongName'.$n.' \\(unresolved\\)', $overview);
            self::assertStringContainsString('Missing\\\\DependencyWithAnExtremelyLongName'.$n.'Module', $text);
        }
    }

    public function testVeryLongNamesUseListsAndRetainKnownInactiveStates(): void
    {
        $data = $this->catalogData();
        $class = 'App\\'.str_repeat('VeryLongModuleName', 4).'Module';
        $data['modules'][] = ['id' => 'long', 'class' => $class, 'scope' => 'shared', 'project' => null, 'active' => false, 'inactive_reason' => 'disabled', 'descriptor' => '/app/LongModule.php', 'declared_dependencies' => []];
        $data['modules'][0]['active'] = false;
        $data['modules'][0]['inactive_reason'] = 'unassigned';
        $text = (new ReportRenderer(new ArchitectureReport($data)))->markdown('test', new \DateTimeImmutable());
        $overview = explode('## Diagnoses & Resolutions', explode('## Architecture Overview', $text)[1])[0];
        self::assertStringContainsString('| Module |', $overview);
        self::assertStringContainsString('#### '.str_repeat('VeryLongModuleName', 4), $overview);
        self::assertStringContainsString('- State: Disabled', $overview);
        self::assertStringContainsString('| Unassigned |', $overview);
        self::assertStringContainsString('- Dependencies: —', $overview);
    }

    public function testLongProjectEntriesAndHostListsDoNotWidenTheTable(): void
    {
        $data = $this->catalogData();
        $name = str_repeat('RegionalStorefront', 4);
        $data['projects'][] = ['id' => 'regional', 'class' => 'App\\'.$name.'Project', 'descriptorFile' => '/app/Regional/RegionalProject.php', 'hosts' => ['one.test', 'two.test', 'three.test', 'four.test'], 'declaredModules' => ['App\\Module\\User\\UserModule']];
        $text = (new ReportRenderer(new ArchitectureReport($data)))->markdown('test', new \DateTimeImmutable());
        $overview = explode('## Diagnoses & Resolutions', explode('## Architecture Overview', $text)[1])[0];
        self::assertStringContainsString('| Project | Configured hosts | Declared modules |', $overview);
        self::assertStringContainsString('#### '.$name, $overview);
        self::assertStringContainsString('- Configured hosts: 4 hosts', $overview);
        self::assertStringContainsString('#### Hosts of '.$name, $overview);
        foreach (['one.test', 'two.test', 'three.test', 'four.test'] as $host) {
            self::assertStringContainsString('- '.$host, $overview);
        }
        self::assertStringNotContainsString('/app/', $overview);
    }

    public function testReadableDiagnosesKeepFullSourceClassesInEvidence(): void
    {
        $report = ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult([
            new ArchitectureFinding('code.unknown_class', 'Missing source class.', ['class' => 'App\\Rendering\\MissingRenderer', 'file' => '/app/src/Consumer.php', 'line' => 8]),
        ]));
        $text = (new ReportRenderer($report))->markdown('test', new \DateTimeImmutable());
        [$readable, $technical] = explode('## Technical Evidence', $text);
        self::assertStringContainsString('MissingRenderer', $readable);
        self::assertStringNotContainsString('App\\\\Rendering', $readable);
        self::assertStringContainsString('App\\\\Rendering\\\\MissingRenderer', $technical);
        self::assertStringContainsString('/app/src/Consumer.php', $technical);
        self::assertStringContainsString('- line: 8', $technical);
    }

    public function testDiagnosesUseTheSameUnambiguousLabelsAsTheOverview(): void
    {
        $data = $this->catalogData();
        $data['valid'] = false;
        $data['status'] = 'violations';
        $data['projects'][] = ['id' => 'outlet', 'class' => 'Other\\ShopProject', 'descriptorFile' => '/app/Other/ShopProject.php', 'hosts' => [], 'declaredModules' => []];
        $data['modules'][] = ['id' => 'secret', 'class' => 'Other\\SecretModule', 'scope' => 'private', 'project' => 'Other\\ShopProject', 'active' => null, 'descriptor' => '/app/Other/SecretModule.php', 'declared_dependencies' => []];
        $data['findings'] = [['rule' => 'code.private', 'severity' => 'error', 'message' => 'Private access.', 'context' => ['module' => 'App\\Module\\User\\UserModule', 'target' => 'Other\\SecretModule', 'file' => '/app/User.php', 'line' => 1]]];
        $text = (new ReportRenderer(new ArchitectureReport($data)))->markdown('test', new \DateTimeImmutable());
        $diagnoses = explode('## Technical Evidence', explode('## Diagnoses & Resolutions', $text)[1])[0];
        self::assertStringContainsString('User → Shop \\[outlet\\] / Secret', $diagnoses);
        self::assertStringNotContainsString('Other\\\\SecretModule', $diagnoses);
    }

    public function testRealPrivateDiagnosisDisambiguatesEqualProjectDescriptorNames(): void
    {
        $user = $this->source->php('src/Module/User/UserModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class UserModule {}');
        foreach (['North', 'South'] as $area) {
            $this->source->php('src/Project/'.$area.'/ShopProject.php', '#[\\Sillbeam\\Core\\Project\\Attribute\\SillbeamProject(id: "'.strtolower($area).'", modules: [\\'.$user.'::class])] final class ShopProject {}');
            $this->source->php('src/Project/'.$area.'/Module/Secret/SecretModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class SecretModule {}');
        }
        $target = $this->source->php('src/Project/South/Module/Secret/Item.php', 'final class Item {}');
        $this->source->php('src/Module/User/Consumer.php', 'final class Consumer { public \\'.$target.' $target; }');
        $config = ['projects' => ['north' => ['hosts' => ['north.test']], 'south' => ['hosts' => ['south.test']]]];
        $analysis = (new ArchitectureAnalyzer())->analyze(new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\'), $config);
        $report = ArchitectureReport::fromAnalysis($analysis, $config);
        self::assertSame(1, $report->exitCode());
        $text = (new ReportRenderer($report))->markdown('test', new \DateTimeImmutable());
        $diagnoses = explode('## Technical Evidence', explode('## Diagnoses & Resolutions', $text)[1])[0];
        self::assertStringContainsString('User → Shop \\[south\\] / Secret', $diagnoses);
        self::assertStringContainsString('private to Project Shop \\[south\\]', $diagnoses);
    }

    public function testReferencedClassesCannotImpersonateModuleOrProjectLabels(): void
    {
        $data = $this->catalogData();
        $data['findings'] = [
            ['rule' => 'code.unknown_class', 'severity' => 'error', 'message' => 'Unknown class.', 'context' => ['module' => 'App\\Module\\User\\UserModule', 'class' => 'Other\\Catalog']],
            ['rule' => 'code.unknown_class', 'severity' => 'error', 'message' => 'Unknown class.', 'context' => ['module' => 'App\\Module\\User\\UserModule', 'class' => 'Other\\Shop']],
        ];
        $text = (new ReportRenderer(new ArchitectureReport($data)))->markdown('test', new \DateTimeImmutable());
        [$readable, $technical] = explode('## Technical Evidence', $text);
        self::assertStringContainsString('User → Catalog \\(class\\)', $readable);
        self::assertStringContainsString('User → Shop \\(class\\)', $readable);
        self::assertStringContainsString('Other\\\\Catalog', $technical);
        self::assertStringContainsString('Other\\\\Shop', $technical);
    }

    public function testProseLabelsNeverRewriteExecutableClassReferences(): void
    {
        $catalog = new MarkdownCatalog($this->catalogData());
        $reference = 'App\\Module\\Catalog\\CatalogModule::class';
        self::assertSame('Use '.$reference, $catalog->prose('Use '.$reference));
        self::assertSame('Module Catalog.', $catalog->prose('Module App\\Module\\Catalog\\CatalogModule.'));
        self::assertSame('App\\Module\\Catalog\\CatalogModuleSuffix', $catalog->prose('App\\Module\\Catalog\\CatalogModuleSuffix'));
    }

    public function testEvenInvalidDuplicateIdsReceiveDistinctLocalLabels(): void
    {
        $data = $this->catalogData();
        $data['valid'] = false;
        $data['modules'][] = ['id' => 'user', 'class' => 'Legacy\\UserModule', 'scope' => 'shared', 'project' => null, 'active' => true, 'descriptor' => '/app/Legacy/UserModule.php', 'declared_dependencies' => []];
        $text = (new ReportRenderer(new ArchitectureReport($data)))->markdown('test', new \DateTimeImmutable());
        self::assertStringContainsString('User \\[shared:user\\] \\#1', $text);
        self::assertStringContainsString('User \\[shared:user\\] \\#2', $text);
    }

    /** @return array<string, mixed> */
    private function catalogData(): array
    {
        return [
            'schema_version' => 1, 'status' => 'valid', 'valid' => true, 'complete' => true, 'findings' => [],
            'modules' => [
                ['id' => 'user', 'class' => 'App\\Module\\User\\UserModule', 'scope' => 'shared', 'project' => null, 'active' => true, 'descriptor' => '/app/src/Module/User/UserModule.php', 'declared_dependencies' => ['App\\Module\\Catalog\\CatalogModule']],
                ['id' => 'catalog', 'class' => 'App\\Module\\Catalog\\CatalogModule', 'scope' => 'shared', 'project' => null, 'active' => true, 'descriptor' => '/app/src/Module/Catalog/CatalogModule.php', 'declared_dependencies' => []],
                ['id' => 'secret', 'class' => 'App\\Project\\Shop\\Module\\Secret\\SecretModule', 'scope' => 'private', 'project' => 'App\\Project\\Shop\\ShopProject', 'active' => true, 'descriptor' => '/app/src/Project/Shop/Module/Secret/SecretModule.php', 'declared_dependencies' => ['App\\Module\\User\\UserModule']],
            ],
            'projects' => [['id' => 'shop', 'class' => 'App\\Project\\Shop\\ShopProject', 'descriptorFile' => '/app/src/Project/Shop/ShopProject.php', 'hosts' => ['shop.test'], 'declaredModules' => ['App\\Module\\User\\UserModule']]],
        ];
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
