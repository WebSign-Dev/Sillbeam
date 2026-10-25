<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Composer\InstalledVersions;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\FilesystemPath;
use Sillbeam\Core\Console\Style\ConsoleStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sillbeam:report', description: 'Write a standalone Markdown architecture report.')]
final class ReportCommand extends Command
{
    /** @param ArchitectureReport|\Closure(): ArchitectureReport $report */
    public function __construct(private readonly ArchitectureReport|\Closure $report, private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('couplings', null, InputOption::VALUE_NONE, 'Include the informational Application coupling appendix.');
        $this->setHelp(<<<'HELP'
Write a standalone Markdown document with every diagnosis, next steps and original
finding. Application couplings are informational, not architecture violations,
and are included only with --couplings.

  php bin/console sillbeam:report
  php bin/console sillbeam:report --output=docs/architecture-report.md
  php bin/console sillbeam:report --couplings --output=architecture-report.md

The default is architecture-report.md in the project root. Relative --output paths
use that root; absolute local paths are accepted. The parent directory must exist.
Replacement is atomic. Violations and unavailable analysis still produce a report
when writing is possible. Exit codes: 0 valid, 1 violations, 2 unavailable, invalid
usage or write failure. --quiet suppresses status output, not file writing.
Use Sillbeam\Core\Console\Application in bin/console to report without a compiled
container. No source files or architecture configuration are changed.
HELP);
        $this->addOption('output', null, InputOption::VALUE_REQUIRED, 'Report path, relative to the project root or absolute.', 'architecture-report.md');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ConsoleStyle($output);
        $report = ArchitectureReport::collect($this->report);
        $temporary = null;
        try {
            $path = $input->getOption('output');
            if (!\is_string($path) || '' === trim($path) || str_contains($path, "\0") || str_contains($path, '://')) {
                throw new \RuntimeException('Output must be a local file path.');
            }
            $path = FilesystemPath::normalize($path);
            if (!str_starts_with($path, '/') && !preg_match('/^[a-zA-Z]:\//', $path)) {
                $path = $this->projectDir.'/'.$path;
            }
            $directory = \dirname($path);
            if (!is_dir($directory)) {
                throw new \RuntimeException('Output directory does not exist: '.$directory);
            }
            $version = InstalledVersions::isInstalled('websign-dev/sillbeam-core') ? (InstalledVersions::getPrettyVersion('websign-dev/sillbeam-core') ?? 'unknown') : 'source';
            $markdown = (new ReportRenderer($report))->markdown($version, new \DateTimeImmutable(), (bool) $input->getOption('couplings'));
            $temporary = @tempnam($directory, '.sillbeam-report-');
            if (false === $temporary || realpath(\dirname($temporary)) !== realpath($directory)) {
                throw new \RuntimeException('Cannot create a temporary report in '.$directory);
            }
            if (\strlen($markdown) !== @file_put_contents($temporary, $markdown)) {
                throw new \RuntimeException('Cannot write the temporary report in '.$directory);
            }
            if (!@rename($temporary, $path)) {
                throw new \RuntimeException('Cannot replace output file: '.$path);
            }
            $io->success('Architecture report written to '.$path.' ('.$report->data['status'].').');

            return $report->exitCode();
        } catch (\Throwable $error) {
            $io->error('Cannot write architecture report: '.$error->getMessage());

            return 2;
        } finally {
            if (\is_string($temporary) && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
