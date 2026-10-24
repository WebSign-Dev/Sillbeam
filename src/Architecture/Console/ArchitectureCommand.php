<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sillbeam:architecture', description: 'Analyze architecture and report findings, modules and projects.')]
final class ArchitectureCommand extends Command
{
    /** @param ArchitectureReport|\Closure(): ArchitectureReport $report */
    public function __construct(private readonly ArchitectureReport|\Closure $report)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text or json.', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        $report = ArchitectureReport::collect(function () use ($format): ArchitectureReport {
            if (!\in_array($format, ['text', 'json'], true)) {
                throw new \InvalidArgumentException('Supported formats: text, json.');
            }

            return $this->report instanceof \Closure ? ($this->report)() : $this->report;
        });

        return self::render($report, 'json' === $format ? 'json' : 'text', $input, $output);
    }

    public static function render(ArchitectureReport $report, string $format, InputInterface $input, OutputInterface $output): int
    {
        if ('json' === $format) {
            $output->writeln($report->toJson(), OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);

            return $report->exitCode();
        }
        (new ReportRenderer($report))->text(new SymfonyStyle($input, $output), $output->isVerbose());

        return $report->exitCode();
    }
}
