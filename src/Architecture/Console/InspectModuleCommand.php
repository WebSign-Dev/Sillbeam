<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Console\Style\ConsoleStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal Text inspection of one module from the existing shared architecture report. */
#[AsCommand(name: 'sillbeam:inspect:module', description: 'Inspect one module, its activation and direct dependency evidence.')]
final class InspectModuleCommand extends Command
{
    /** @param ArchitectureReport|\Closure(): ArchitectureReport $report */
    public function __construct(private readonly ArchitectureReport|\Closure $report)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::REQUIRED, 'Module ID or fully qualified descriptor class (case-insensitive).');
        $this->addOption('project', null, InputOption::VALUE_REQUIRED, 'Project ID or descriptor class: select private ownership and inspect shared-module membership.');
        $this->setHelp(<<<'HELP'
Inspect a module using the same ArchitectureAnalyzer result as architecture reporting.

  php bin/console sillbeam:inspect:module Billing
  php bin/console sillbeam:inspect:module billing --project=shop
  php bin/console sillbeam:inspect:module 'App\Module\Billing\BillingModule' -v

IDs and descriptor classes are matched case-insensitively. Ambiguous IDs list all
candidates. --project filters private ownership and gives shared modules a Project
context, even when unassigned there. If shared/private IDs still collide, pass the
fully qualified descriptor class. No new discovery or dependency analysis is run.
Declared edges and observed references remain separate; -v shows reference locations.
Enabled is configuration availability, not activation. Actual activation is unknown
without a validated graph; diagnostic candidate membership is never treated as runtime
state. Results reflect the available architecture snapshot, not a live service audit.
Exit 0: valid inspection (also disabled/unassigned); 1: architecture violations;
2: unavailable analysis, missing/ambiguous selection or invalid usage. Text only;
no --format/JSON mode. --quiet suppresses output without changing the exit code.
The Core Console adapter enables inspection before container compilation. No source
or configuration files are changed; descriptor execution follows existing analysis.
HELP);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::run($input, $output);
        } catch (\Symfony\Component\Console\Exception\ExceptionInterface $error) {
            (new ConsoleStyle($output))->error($error->getMessage());

            return 2;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new ConsoleStyle($output);
        $identifier = $input->getArgument('module');
        $project = $input->getOption('project');
        if (!\is_string($identifier) || '' === trim($identifier) || (null !== $project && (!\is_string($project) || '' === trim($project)))) {
            $style->error('Supply a nonempty module ID/class and, when used, a nonempty --project ID/class.');

            return 2;
        }
        $report = ArchitectureReport::collect($this->report);
        $style->heading('Module inspection');
        try {
            $view = (new ModuleInspection($report))->select($identifier, $project);
        } catch (\InvalidArgumentException $error) {
            $style->error($error->getMessage());
            if (!$report->data['complete']) {
                $this->findings($style, $report->data['findings']);
            }

            return 2;
        }
        $module = $view['module'];
        $config = $view['configuration'];
        $style->text('MODULE', style: 'options=bold');
        foreach ([
            'ID' => $module['id'], 'Descriptor class' => $module['class'],
            'Descriptor file' => $module['descriptor'], 'Scope' => $module['scope'],
            'Owner Project' => $view['owner']['id'] ?? ('shared' === $module['scope'] ? 'none (shared)' : 'unknown'),
        ] as $label => $value) {
            $style->text($label.': '.$value);
        }
        if (null !== $view['owner']) {
            $style->text('Owner class: '.$view['owner']['class']);
        }
        if (null !== $view['project']) {
            $style->text('Inspection Project: '.$view['project']['id']);
            $style->text('Project class: '.$view['project']['class']);
        }
        $style->rule();
        $style->text('CONFIGURATION AND ACTIVATION', style: 'options=bold');
        $style->text('Configuration key: '.($config['key'] ?? 'unknown'));
        $style->text('Override: '.(null === $config['key'] ? 'unknown' : (null === $config['source'] ? 'none' : $config['source'].' (configured key)')));
        if (null !== $config['enabled_override']) {
            $style->text('Configured enabled override: '.self::boolean($config['enabled_override']).' (input, not proof of activation)');
        }
        $style->text('Enabled: '.self::boolean($module['enabled']));
        $style->text('Active: '.self::boolean($view['active']));
        $style->text('State: '.$view['state']);
        $style->text('Global active: '.self::boolean($module['active']));
        if (null === $view['active']) {
            $style->note('Actual activation is unknown: no validated graph is available.');
        }
        foreach ($view['project_states'] as $state) {
            $style->text('Project '.$state['id'].': '.$state['state'].' — '.$state['class']);
        }
        $this->section($style, 'DECLARED DEPENDENCIES', $module['declared_dependencies'], 'No declared dependencies.');
        $this->section($style, 'OBSERVED DEPENDENCIES', $module['observed_dependencies'], 'No observed dependencies in the available evidence.');
        if (!$config['references_complete']) {
            $style->note('Reference evidence is incomplete; an empty list is not proof of no dependencies.');
        }
        if ($output->isVerbose()) {
            foreach ($module['references'] as $reference) {
                $style->text($reference['class'].' at '.$reference['file'].':'.$reference['line'], '    ');
            }
        }
        $this->section($style, 'INCOMING DECLARED DEPENDENCIES', $view['incoming'], 'No incoming declared dependencies in the available catalog.');
        $this->section($style, 'INCOMING OBSERVED REFERENCES', array_values(array_unique(array_column($view['incoming_references'], 'source'))), 'No incoming observed references in the available evidence.');
        if (!$view['references_complete']) {
            $style->note('Incoming reference evidence is incomplete.');
        }
        if ($output->isVerbose()) {
            foreach ($view['incoming_references'] as $incoming) {
                $reference = $incoming['reference'];
                $style->text($incoming['source'].' at '.$reference['file'].':'.$reference['line'], '    ');
            }
        }
        $style->rule();
        $style->text('RELATED ARCHITECTURE FINDINGS', style: 'options=bold');
        $this->findings($style, $view['findings']);
        if ([] === $view['findings']) {
            $style->text('No related architecture findings in the available analysis.');
        }
        if ([] !== $view['blockers']) {
            $style->text('ANALYSIS BLOCKERS', style: 'options=bold');
            $this->findings($style, $view['blockers']);
        }
        $style->text('Whole analysis: '.$report->data['status'].'. Use sillbeam:architecture --all for the complete result.');

        return $report->exitCode();
    }

    /** @param list<string> $items */
    private function section(ConsoleStyle $style, string $title, array $items, string $empty): void
    {
        $style->rule();
        $style->text($title, style: 'options=bold');
        $items = array_values(array_unique($items));
        sort($items, \SORT_STRING);
        if ([] === $items) {
            $style->text($empty);
        }
        foreach ($items as $item) {
            $style->text($item, '    ');
        }
    }

    /** @param list<array<string, mixed>> $findings */
    private function findings(ConsoleStyle $style, array $findings): void
    {
        foreach ($findings as $finding) {
            $style->text('['.$finding['rule'].'] '.$finding['message']);
            $file = $finding['context']['file'] ?? null;
            if (\is_string($file)) {
                $style->text($file.(isset($finding['context']['line']) ? ':'.$finding['context']['line'] : ''), '    ');
            }
            if (isset($finding['context']['suggestion'])) {
                $style->text('Hint: '.$finding['context']['suggestion'], '    ');
            }
        }
    }

    private static function boolean(?bool $value): string
    {
        return null === $value ? 'unknown' : ($value ? 'true' : 'false');
    }
}
