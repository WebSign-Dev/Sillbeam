<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\DotGraph;
use Sillbeam\Core\Architecture\Console\GraphCommand;
use Sillbeam\Core\Architecture\Console\GraphView;
use Sillbeam\Core\Architecture\Console\GraphvizRenderer;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class GraphSvgTest extends TestCase
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

    public function testDefaultSvgUsesCwdAndRefusesOverwriteUnlessForced(): void
    {
        $renderer = $this->renderer('echo \'<svg xmlns="http://www.w3.org/2000/svg"><text>ok</text></svg>\';');
        $tester = new CommandTester(new GraphCommand($this->report(), $renderer));
        $cwd = getcwd();
        self::assertNotFalse($cwd);
        chdir($this->source->directory);
        try {
            self::assertSame(0, $tester->execute([]));
            $path = $this->source->directory.'/architecture.svg';
            self::assertStringContainsString($path, $tester->getDisplay());
            self::assertStringContainsString('<svg', (string) file_get_contents($path));
            file_put_contents($path, 'existing artifact');
            self::assertSame(4, $tester->execute([], ['capture_stderr_separately' => true]));
            self::assertSame('', $tester->getDisplay());
            self::assertStringContainsString('--force', $tester->getErrorOutput());
            self::assertSame('existing artifact', file_get_contents($path));
            self::assertSame(0, $tester->execute(['--force' => true], ['verbosity' => OutputInterface::VERBOSITY_QUIET]));
            self::assertSame('', $tester->getDisplay());
            self::assertStringContainsString('<svg', (string) file_get_contents($path));
            self::assertSame([], glob($this->source->directory.'/.sillbeam-svg-*'));
        } finally {
            chdir($cwd);
        }
    }

    public function testExplicitOutputTreatsShellCharactersAsLiteralPath(): void
    {
        $path = $this->source->directory.'/graph $(touch injected); ü.svg';
        $tester = new CommandTester(new GraphCommand($this->report('violations'), $this->renderer('echo \'<svg xmlns="http://www.w3.org/2000/svg"></svg>\';')));
        self::assertSame(1, $tester->execute(['--format' => 'svg', '--output' => $path]));
        self::assertFileExists($path);
        self::assertFileDoesNotExist($this->source->directory.'/injected');
        self::assertStringContainsString($path, $tester->getDisplay());
    }

    public function testRendererFailuresPreserveExistingArtifactAndUseStderr(): void
    {
        $path = $this->source->directory.'/architecture.svg';
        file_put_contents($path, 'keep');
        foreach ([new GraphvizRenderer($this->source->directory.'/missing-dot'), $this->renderer('fwrite(STDERR, "failure"); echo "partial"; exit(7);'), $this->renderer('echo "not SVG";')] as $renderer) {
            $tester = new CommandTester(new GraphCommand($this->report(), $renderer));
            self::assertSame(4, $tester->execute(['--output' => $path, '--force' => true], ['capture_stderr_separately' => true]));
            self::assertSame('', $tester->getDisplay());
            self::assertNotSame('', $tester->getErrorOutput());
            self::assertSame('keep', file_get_contents($path));
            self::assertSame([], glob($this->source->directory.'/.sillbeam-svg-*'));
        }
    }

    public function testMissingRendererExplainsOptionalInstallation(): void
    {
        $tester = new CommandTester(new GraphCommand($this->report(), new GraphvizRenderer($this->source->directory.'/no-dot')));
        self::assertSame(4, $tester->execute(['--output' => $this->source->directory.'/new.svg'], ['capture_stderr_separately' => true]));
        self::assertStringContainsString('Graphviz', $tester->getErrorOutput());
        self::assertStringContainsString('install', $tester->getErrorOutput());
        self::assertFileDoesNotExist($this->source->directory.'/new.svg');
        self::assertSame(4, $tester->execute(['--output' => $this->source->directory.'/new.svg'], ['capture_stderr_separately' => true, 'verbosity' => OutputInterface::VERBOSITY_QUIET]));
        self::assertSame('', $tester->getDisplay());
        self::assertSame('', $tester->getErrorOutput());
    }

    public function testOutputErrorsAndSymlinksNeverClobberTargets(): void
    {
        $renderer = $this->renderer('echo \'<svg xmlns="http://www.w3.org/2000/svg"></svg>\';');
        $this->source->write('keep.svg', 'keep');
        symlink($this->source->directory.'/keep.svg', $this->source->directory.'/link.svg');
        symlink($this->source->directory.'/absent.svg', $this->source->directory.'/dangling.svg');
        foreach (['missing/graph.svg', 'src', 'link.svg', 'dangling.svg'] as $path) {
            $tester = new CommandTester(new GraphCommand($this->report(), $renderer));
            self::assertSame(4, $tester->execute(['--output' => $this->source->directory.'/'.$path, '--force' => true]));
        }
        self::assertSame('keep', file_get_contents($this->source->directory.'/keep.svg'));
        self::assertFileDoesNotExist($this->source->directory.'/absent.svg');
    }

    public function testConcurrentCreationIsNotOverwritten(): void
    {
        $path = $this->source->directory.'/race.svg';
        $renderer = $this->renderer('file_put_contents('.var_export($path, true).', "concurrent"); echo \'<svg xmlns="http://www.w3.org/2000/svg"></svg>\';');
        $tester = new CommandTester(new GraphCommand($this->report(), $renderer));
        self::assertSame(4, $tester->execute(['--output' => $path]));
        self::assertSame('concurrent', file_get_contents($path));
        self::assertSame([], glob($this->source->directory.'/.sillbeam-svg-*'));
    }

    public function testInvalidSvgOptionsFailBeforeAnalysis(): void
    {
        foreach ([['--format' => 'mermaid', '--output' => 'x'], ['--format' => 'mermaid', '--force' => true], ['--module' => ''], ['--project' => ''], ['--output' => ''], ['--format' => 'SVG']] as $options) {
            $called = false;
            $tester = new CommandTester(new GraphCommand(static function () use (&$called): ArchitectureReport {
                $called = true;

                return ArchitectureReport::unavailable(new \RuntimeException('unexpected'));
            }));
            self::assertSame(3, $tester->execute($options, ['capture_stderr_separately' => true]));
            self::assertFalse($called);
            self::assertSame('', $tester->getDisplay());
            self::assertNotSame('', $tester->getErrorOutput());
        }
    }

    public function testSuccessfulSvgPreservesAllThreeAnalysisExitCodes(): void
    {
        foreach (['valid' => 0, 'violations' => 1, 'unavailable' => 2] as $status => $code) {
            $path = $this->source->directory.'/'.$status.'.svg';
            $tester = new CommandTester(new GraphCommand($this->report($status), $this->renderer('echo \'<svg xmlns="http://www.w3.org/2000/svg"></svg>\';')));
            self::assertSame($code, $tester->execute(['--output' => $path]));
            self::assertFileExists($path);
            $dot = (new DotGraph())->render(GraphView::fromReport($this->report($status)));
            if (0 !== $code) {
                self::assertStringContainsString('NOT a validated runtime graph', $dot);
                self::assertStringNotContainsString('state=active', $dot);
                self::assertStringContainsString('state=unknown', $dot);
            }
        }
    }

    public function testDotUsesQuotedLabelsAndSameDirectEdgesAsReport(): void
    {
        $view = GraphView::fromReport($this->report());
        $dot = (new DotGraph())->render($view);
        self::assertStringContainsString('m0001 -> m0002;', $dot);
        self::assertStringContainsString('subgraph cluster_shared_scope', $dot);
        self::assertStringNotContainsString('analysis_status [', $dot);
        self::assertStringContainsString('label="Billing', $dot);
        self::assertStringContainsString('tooltip="App\\\\Billing"', $dot);
        $data = $this->report()->data;
        $data['modules'][0]['id'] = 'x" ]; injected [URL="https://evil"] // <tag> \\N';
        $dot = (new DotGraph())->render(GraphView::fromReport(new ArchitectureReport($data)));
        self::assertStringContainsString('x\\" ]; injected [URL=\\"https://evil\\"] // <tag> \\\\N', $dot);
    }

    public function testRealGraphvizWhenAvailable(): void
    {
        $available = false;
        foreach (explode(\PATH_SEPARATOR, (string) getenv('PATH')) as $directory) {
            if (is_executable($directory.'/dot')) {
                $available = true;
                break;
            }
        }
        if (!$available) {
            self::markTestSkipped('Optional Graphviz dot is not installed; controlled subprocess tests still run.');
        }
        $svg = (new GraphvizRenderer())->render((new DotGraph())->render(GraphView::fromReport($this->report())));
        $xml = simplexml_load_string($svg);
        self::assertNotFalse($xml);
        self::assertSame('svg', $xml->getName());
        self::assertStringContainsString('Billing', $svg);
        self::assertStringContainsString('m0001', $svg);
    }

    private function renderer(string $body): GraphvizRenderer
    {
        $path = 'fake-dot-'.bin2hex(random_bytes(4));
        $this->source->write($path, '#!'.\PHP_BINARY."\n<?php if ([\"-Tsvg\"] !== array_slice(\$argv, 1)) { exit(90); } if (!str_starts_with(stream_get_contents(STDIN), \"digraph architecture {\")) { exit(91); } ".$body);
        chmod($this->source->directory.'/'.$path, 0700);

        return new GraphvizRenderer($this->source->directory.'/'.$path);
    }

    private function report(string $status = 'valid'): ArchitectureReport
    {
        $modules = [];
        foreach (['Billing', 'Payments'] as $id) {
            $modules[] = ['id' => $id, 'class' => 'App\\'.$id, 'scope' => 'shared', 'project' => null, 'enabled' => true,
                'active' => 'valid' === $status ? true : null, 'declared_dependencies' => 'Billing' === $id ? ['App\\Payments'] : [], 'observed_dependencies' => [], 'references' => []];
        }

        return new ArchitectureReport(['schema_version' => 1, 'status' => $status, 'complete' => 'unavailable' !== $status, 'valid' => 'valid' === $status, 'modules' => $modules, 'projects' => [], 'findings' => []]);
    }
}
