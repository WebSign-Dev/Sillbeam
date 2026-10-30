<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal SVG or raw Mermaid export of existing report evidence, without container boot. */
#[AsCommand(name: 'sillbeam:graph', description: 'Render module architecture as SVG or export raw Mermaid source.')]
final class GraphCommand extends Command
{
    /** @param ArchitectureReport|\Closure(): ArchitectureReport $report */
    public function __construct(private readonly ArchitectureReport|\Closure $report)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: svg or mermaid.', 'svg');
        $this->addOption('output', null, InputOption::VALUE_REQUIRED, 'SVG output path (default: architecture.svg in the current working directory).');
        $this->addOption('module', null, InputOption::VALUE_REQUIRED, 'Module ID or descriptor class: direct incoming/outgoing relationships only.');
        $this->addOption('project', null, InputOption::VALUE_REQUIRED, 'Project ID or descriptor class: private owner/context selection.');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Intentionally replace an existing regular SVG file.');
        $this->setHelp(<<<'HELP'
Render module identities and direct dependency evidence from the existing report:

  php bin/console sillbeam:graph
  php bin/console sillbeam:graph --format=svg --output=architecture.svg --force
  php bin/console sillbeam:graph --module=Billing --project=shop
  php bin/console sillbeam:graph --format=mermaid > architecture.mmd

SVG is generated directly by Core in PHP. No Graphviz, Node.js, browser or extra
Composer package is required. Missing output directories are created automatically.
The default output is architecture.svg in the current working directory.
Existing files require --force; directory/symlink targets are rejected.
Serialize forced exports: concurrent changes to the destination entry may be replaced.
--output and --force apply only to SVG. Successful SVG output confirms the full path;
--quiet suppresses messages but still writes the file. Renderer errors use STDERR.
Mermaid writes raw source to STDOUT without decoration; --quiet suppresses source.
IDs/classes match module inspection case-insensitively; ambiguous IDs need --project
or an exact descriptor class. --module includes only direct incident relationships.
--project limits private ownership and shows activation in that Project; incident
cross-boundary relationships are retained. Shared modules remain visible even when
unassigned. No discovery, traversal or additional dependency analysis is performed.
Arrows point from dependent to required module. Observed undeclared edges are dashed;
recorded cycles, disabled and unknown states remain visible. This is an evidence
export, not an inferred runtime graph. Incomplete references never prove absence.
After successful export: exit 0 valid, 1 violations, 2 unavailable analysis.
Exit 3: invalid invocation/selection. Exit 4: renderer or output failure.
The Core Console adapter supports diagnostics before container compilation.
HELP);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::run($input, $output);
        } catch (\Symfony\Component\Console\Exception\ExceptionInterface $error) {
            return self::usageError($error, $output);
        }
    }

    /** Writes plain usage errors to STDERR when the output exposes it; never adds Mermaid source. */
    public static function usageError(\Throwable $error, OutputInterface $output): int
    {
        $errors = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $errors->writeln('sillbeam:graph: '.preg_replace('/[\x00-\x1F\x7F]/', ' ', $error->getMessage()), OutputInterface::OUTPUT_RAW);

        return 3;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        if (!\in_array($format, ['svg', 'mermaid'], true)) {
            return self::usageError(new \InvalidArgumentException('Unsupported --format. Use svg or mermaid.'), $output);
        }
        foreach (['module', 'project', 'output'] as $option) {
            $value = $input->getOption($option);
            if (null !== $value && (!\is_string($value) || '' === trim($value) || preg_match('/[\x00-\x1f\x7f]/', $value))) {
                return self::usageError(new \InvalidArgumentException('Supply a nonempty --'.$option.' value without control characters.'), $output);
            }
        }
        $force = (bool) $input->getOption('force');
        if ('mermaid' === $format && (null !== $input->getOption('output') || $force)) {
            return self::usageError(new \InvalidArgumentException('--output and --force apply only to SVG; redirect Mermaid STDOUT instead.'), $output);
        }
        $report = ArchitectureReport::collect($this->report);
        try {
            $view = GraphView::fromReport($report, $input->getOption('module'), $input->getOption('project'));
        } catch (\InvalidArgumentException $error) {
            return self::usageError($error, $output);
        }
        if ('mermaid' === $format) {
            $output->write((new MermaidGraph())->render($view), false, OutputInterface::OUTPUT_RAW);
        } else {
            try {
                $writer = new SvgOutput();
                $destination = $writer->destination($input->getOption('output') ?? 'architecture.svg', $force);
                $svg = (new SvgGraph())->render($view);
                $writer->write($destination, $svg, $force);
            } catch (\RuntimeException $error) {
                self::usageError($error, $output);

                return 4;
            }
            $output->writeln('Architecture SVG written to '.$destination, OutputInterface::OUTPUT_RAW);
        }

        return $report->exitCode();
    }
}
