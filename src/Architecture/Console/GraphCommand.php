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

/** @internal Raw Mermaid export of existing architecture evidence, without container boot. */
#[AsCommand(name: 'sillbeam:graph', description: 'Export module architecture evidence as Mermaid source.')]
final class GraphCommand extends Command
{
    /** @param ArchitectureReport|\Closure(): ArchitectureReport $report */
    public function __construct(private readonly ArchitectureReport|\Closure $report)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (mermaid only).', 'mermaid');
        $this->setHelp(<<<'HELP'
Export existing module identities and direct declared dependencies as Mermaid source:

  php bin/console sillbeam:graph --format=mermaid > architecture.mmd

Includes disabled modules, recorded cycle members, observed undeclared references,
private Project ownership and validated Project memberships. This is an evidence
export, not a new runtime graph. Invalid/incomplete analysis has explicit warnings
and unknown activation; missing references do not prove dependency absence.
Only Mermaid is supported. No renderer or runtime is needed. STDOUT contains raw
source without ANSI or status banners; usage errors go to STDERR. --quiet suppresses
output while retaining the exit status. Exit 0: valid; 1: violations; 2: unavailable
analysis or invalid usage. The Core Console adapter works before container boot.
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

        return 2;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('mermaid' !== $input->getOption('format')) {
            return self::usageError(new \InvalidArgumentException('Unsupported --format. Use mermaid.'), $output);
        }
        $report = ArchitectureReport::collect($this->report);
        $output->write((new MermaidGraph())->render($report), false, OutputInterface::OUTPUT_RAW);

        return $report->exitCode();
    }
}
