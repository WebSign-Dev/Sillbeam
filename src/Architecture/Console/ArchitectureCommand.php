<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Console\Style\ConsoleStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sillbeam:architecture', description: 'Explain architecture findings and evidence-based next steps.')]
final class ArchitectureCommand extends Command
{
    /** @param ArchitectureReport|\Closure(): ArchitectureReport $report */
    public function __construct(private readonly ArchitectureReport|\Closure $report)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text or complete schema-version-1 json.', 'text');
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Expand every diagnosis and its original findings.');
        $this->addOption('issue', null, InputOption::VALUE_REQUIRED, 'Expand diagnosis N, including every original finding; numbers apply only to this run.');
        $this->addOption('couplings', null, InputOption::VALUE_NONE, 'Show informational application couplings separately (text only).');
        $this->setHelp(<<<'HELP'
With no findings, show a short success status. With one diagnosis, show its full
explanation. With several diagnoses, show the complete index and expand the first.

  php bin/console sillbeam:architecture
  php bin/console sillbeam:architecture --issue=2
  php bin/console sillbeam:architecture --all
  php bin/console sillbeam:architecture -v --couplings
  php bin/console sillbeam:architecture --format=json --no-ansi

Use -v for rule codes, full original paths and technical evidence. Default locations
are compact and relative. --issue=N selects a diagnosis group, not a raw finding;
its number is valid only for the current analysis run. --all and --issue are mutually
exclusive. --format=json always contains every finding and cannot be combined with
--all, --issue or --couplings. -v does not add anything to JSON. --quiet suppresses
output in either format. No source files are changed. Exit codes: 0 valid, 1 violations,
2 unavailable or invalid usage. Terminal width is detected automatically and capped
at 90 columns. ANSI follows Symfony's stream detection or --ansi/--no-ansi.
HELP);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::run($input, $output);
        } catch (\Symfony\Component\Console\Exception\ExceptionInterface $error) {
            // Binding failures occur before execute(), also in a regular Symfony Application.
            return self::usageError($error, 'json' === $input->getParameterOption('--format'), $input, $output);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        try {
            self::validate($input);
        } catch (\InvalidArgumentException $error) {
            return self::usageError($error, 'json' === $format, $input, $output);
        }
        $report = ArchitectureReport::collect($this->report);
        try {
            return self::render($report, $format, $input, $output);
        } catch (\InvalidArgumentException $error) {
            return self::usageError($error, 'json' === $format, $input, $output);
        }
    }

    private static function validate(InputInterface $input): void
    {
        $format = $input->getOption('format');
        if (!\in_array($format, ['text', 'json'], true)) {
            throw new \InvalidArgumentException('Supported formats: text, json.');
        }
        if ($input->hasParameterOption('--ansi') && $input->hasParameterOption('--no-ansi')) {
            throw new \InvalidArgumentException('Choose either --ansi or --no-ansi.');
        }
        $issue = $input->getOption('issue');
        if (null !== $issue && false === filter_var($issue, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            throw new \InvalidArgumentException('--issue must be a positive integer.');
        }
        if (null !== $issue && $input->getOption('all')) {
            throw new \InvalidArgumentException('Choose either --issue=N or --all.');
        }
        if ('json' === $format && (null !== $issue || $input->getOption('all') || $input->getOption('couplings'))) {
            throw new \InvalidArgumentException('--format=json contains the complete report; do not combine it with --issue, --all or --couplings.');
        }
    }

    public static function usageError(\Throwable $error, bool $json, InputInterface $input, OutputInterface $output): int
    {
        if ($json) {
            return self::render(ArchitectureReport::unavailable($error), 'json', $input, $output);
        }
        (new ConsoleStyle($output))->error($error->getMessage());

        return 2;
    }

    public static function render(ArchitectureReport $report, string $format, InputInterface $input, OutputInterface $output): int
    {
        if ('json' === $format) {
            if (!$output->isQuiet()) {
                $output->writeln($report->toJson(), OutputInterface::OUTPUT_RAW);
            }

            return $report->exitCode();
        }
        $all = $input->hasOption('all') && $input->getOption('all');
        $issue = $input->hasOption('issue') ? $input->getOption('issue') : null;
        $couplings = $input->hasOption('couplings') && $input->getOption('couplings');
        (new ArchitectureRenderer($output, verbose: $output->isVerbose(), all: $all, issueNumber: null === $issue ? null : (int) $issue, showCouplings: $couplings))->render([] === $report->presentation ? ArchitecturePresentation::fromData($report->data) : $report->presentation);

        return $report->exitCode();
    }
}
