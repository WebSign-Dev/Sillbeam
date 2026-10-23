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
        $level = ob_get_level();
        ob_start();
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            if ($severity & (\E_DEPRECATED | \E_USER_DEPRECATED | \E_NOTICE | \E_USER_NOTICE)) {
                return true;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            if (!\in_array($format, ['text', 'json'], true)) {
                throw new \InvalidArgumentException('Supported formats: text, json.');
            }
            $report = $this->report instanceof \Closure ? ($this->report)() : $this->report;
        } catch (\Throwable $error) {
            $report = ArchitectureReport::unavailable($error);
        } finally {
            restore_error_handler();
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }

        return self::render($report, 'json' === $format ? 'json' : 'text', $input, $output);
    }

    public static function render(ArchitectureReport $report, string $format, InputInterface $input, OutputInterface $output): int
    {
        if ('json' === $format) {
            $output->writeln($report->toJson(), OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);

            return $report->exitCode();
        }
        $io = new SymfonyStyle($input, $output);
        $io->title('Sillbeam architecture: '.$report->data['status']);
        foreach ($report->data['findings'] as $finding) {
            $context = $finding['context'];
            $location = isset($context['file']) ? ' ('.$context['file'].(isset($context['line']) ? ':'.$context['line'] : '').')' : '';
            $io->writeln('['.$finding['severity'].'] '.$finding['rule'].': '.$finding['message'].$location, OutputInterface::OUTPUT_RAW);
            if (isset($context['module'])) {
                $io->writeln('Module: '.$context['module'], OutputInterface::OUTPUT_RAW);
            }
            if (isset($context['suggestion'])) {
                $io->writeln('Hint: '.$context['suggestion'], OutputInterface::OUTPUT_RAW);
            }
        }
        $io->table(['Module', 'Scope', 'State', 'Declared / observed', 'Directory'], array_map(static fn (array $module): array => [
            $module['id'], $module['scope'], $module['active'] ? 'active' : $module['inactive_reason'],
            \count($module['declared_dependencies']).' / '.\count($module['observed_dependencies']), $module['directory'],
        ], $report->data['modules']));
        foreach ($report->data['projects'] as $project) {
            $io->writeln($project['id'].': '.implode(', ', $project['hosts']).' ('.(null === $project['modules'] ? 'unknown' : \count($project['modules'])).' modules)');
        }

        return $report->exitCode();
    }
}
