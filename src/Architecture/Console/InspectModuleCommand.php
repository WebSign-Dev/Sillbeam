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
Default output merges direct relations into DEPENDS ON and USED BY with evidence
labels. Declared only does not mean unnecessary; incomplete observations remain
unverified. -v adds separate technical lists, reference locations, configuration,
activation evidence, Project states and original findings/context.
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
                $this->findings($style, $report->data['findings'], new ModuleInspectionPresentation($report));
                if ($output->isVerbose()) {
                    $this->originals($style, $report->data['findings']);
                }
            }

            return 2;
        }
        $module = $view['module'];
        $presentation = new ModuleInspectionPresentation($report);
        $outgoing = $presentation->relationships($view);
        $incoming = $presentation->relationships($view, true);
        $style->text('Module: '.$presentation->label($module['class']).' — '.ucfirst($view['state']), style: 'options=bold');
        $style->text('Scope: '.ucfirst($module['scope']).('private' === $module['scope'] ? ' · Owner Project: '.$presentation->project($module['project']) : ''));
        if (null !== $view['project']) {
            $style->text('Project context: '.$presentation->project($view['project']['class']));
            if ($view['active'] !== $module['active']) {
                $style->note('Global activation: '.(null === $module['active'] ? 'unknown' : ($module['active'] ? 'active' : 'inactive')).'; differs from the selected Project.');
            }
        }
        $style->text('Architecture: '.ucfirst($report->data['status']).' (whole analysis)');
        $style->text('Known direct relationships: '.\count($outgoing).' depends on · '.\count($incoming).' used by');
        if (null === $view['active']) {
            $style->note('Activation is unknown: no validated graph is available.');
        }
        if (!$report->data['complete'] || !$view['configuration']['references_complete'] || !$view['references_complete']) {
            $style->note('Reference evidence is incomplete; missing observations do not prove absence.');
        }
        if (!$report->data['complete']) {
            $style->note('Module catalog/analysis may be incomplete; counts cover available evidence.');
        }
        $this->relationships($style, 'DEPENDS ON', $outgoing);
        $this->relationships($style, 'USED BY', $incoming);
        $style->rule();
        $style->text('ARCHITECTURE ISSUES', style: 'options=bold');
        $this->findings($style, $view['findings'], $presentation);
        if ([] === $view['findings']) {
            $message = 'No related architecture findings in the available evidence.';
            if ($report->data['valid'] && $report->data['complete'] && [] === $view['blockers'] && null !== $view['active'] && $view['configuration']['references_complete'] && $view['references_complete'] && !array_any(array_merge($outgoing, $incoming), static fn (array $row): bool => $row['problem'])) {
                $style->success($message);
            } else {
                $style->text($message);
            }
        }
        if ([] !== $view['blockers']) {
            $style->text('ANALYSIS BLOCKERS', style: 'options=bold');
            $this->findings($style, $view['blockers'], $presentation);
        }
        if ($output->isVerbose()) {
            $this->technical($style, $view);
        }
        if (0 !== $report->exitCode()) {
            $style->note('Whole analysis: '.$report->data['status'].'. Use sillbeam:architecture --all for the complete result.');
        }

        return $report->exitCode();
    }

    /** @param list<array{class: string, label: string, evidence: string, problem: bool}> $rows */
    private function relationships(ConsoleStyle $style, string $title, array $rows): void
    {
        $style->rule();
        $style->text($title, style: 'options=bold');
        if ([] === $rows) {
            $style->text('None recorded in the available evidence.');
        }
        foreach ($rows as $row) {
            $text = $row['label'].' — '.$row['evidence'];
            if ($row['problem']) {
                $style->error($text);
            } else {
                $style->text($text, '    ');
            }
        }
    }

    /** Technical evidence is additive; no effective state is derived from configuration inputs.
     * @param array<string, mixed> $view
     */
    private function technical(ConsoleStyle $style, array $view): void
    {
        $module = $view['module'];
        $config = $view['configuration'];
        $style->rule();
        $style->text('TECHNICAL DETAILS', style: 'options=bold');
        foreach ([
            'ID' => $module['id'], 'Descriptor class' => $module['class'], 'Descriptor file' => $module['descriptor'],
            'Scope' => $module['scope'], 'Owner Project' => $view['owner']['id'] ?? ('shared' === $module['scope'] ? 'none (shared)' : 'unknown'),
            'Configuration key' => $config['key'] ?? 'unknown',
            'Override' => null === $config['key'] ? 'unknown' : (null === $config['source'] ? 'none' : $config['source'].' (configured key)'),
            'Enabled' => self::boolean($module['enabled']), 'Active' => self::boolean($view['active']),
            'State' => $view['state'], 'Global active' => self::boolean($module['active']),
            'Activation reasons' => [] === ($module['activation_reasons'] ?? []) ? 'none recorded' : implode(', ', $module['activation_reasons']),
        ] as $label => $value) {
            $style->text($label.': '.$value);
        }
        if (null !== $config['enabled_override']) {
            $style->text('Configured enabled override: '.self::boolean($config['enabled_override']).' (input, not proof of activation)');
        }
        if (null !== $view['owner']) {
            $style->text('Owner class: '.$view['owner']['class']);
        }
        if (null !== $view['project']) {
            $style->text('Inspection Project: '.$view['project']['id']);
            $style->text('Project class: '.$view['project']['class']);
        }
        foreach ($view['project_states'] as $state) {
            $style->text('Project '.$state['id'].': '.$state['state'].' — '.$state['class']);
        }
        $this->section($style, 'DECLARED DEPENDENCIES', $module['declared_dependencies'], 'None recorded.');
        $this->section($style, 'OBSERVED DEPENDENCIES', $module['observed_dependencies'], 'None recorded; absence requires complete evidence.');
        foreach ($module['references'] as $reference) {
            $style->text($reference['class'].' at '.$reference['file'].':'.$reference['line'], '    ');
        }
        $this->section($style, 'INCOMING DECLARED DEPENDENCIES', $view['incoming'], 'None recorded.');
        $this->section($style, 'INCOMING OBSERVED REFERENCES', array_values(array_unique(array_column($view['incoming_references'], 'source'))), 'None recorded; absence requires complete evidence.');
        foreach ($view['incoming_references'] as $incoming) {
            $reference = $incoming['reference'];
            $style->text($incoming['source'].' → '.$reference['class'].' at '.$reference['file'].':'.$reference['line'], '    ');
        }
        $this->originals($style, array_values(array_merge($view['findings'], $view['blockers'])));
    }

    /** @param list<array<string, mixed>> $findings */
    private function originals(ConsoleStyle $style, array $findings): void
    {
        $style->rule();
        $style->text('ORIGINAL FINDINGS', style: 'options=bold');
        if ([] === $findings) {
            $style->text('None related to this inspection.');
        }
        foreach ($findings as $finding) {
            $style->text('['.$finding['rule'].'] '.$finding['severity'].': '.$finding['message']);
            $style->text('Context: '.json_encode($finding['context'], \JSON_UNESCAPED_SLASHES | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_PARTIAL_OUTPUT_ON_ERROR));
        }
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
    private function findings(ConsoleStyle $style, array $findings, ModuleInspectionPresentation $presentation): void
    {
        foreach ($findings as $finding) {
            $message = $presentation->problem($finding);
            $style->error('['.$finding['rule'].'] '.$message);
            if (null !== ($location = $presentation->location($finding))) {
                $style->text($location, '    ');
            }
            if (null !== ($caution = $presentation->caution($finding))) {
                $style->note($caution);
            }
        }
    }

    private static function boolean(?bool $value): string
    {
        return null === $value ? 'unknown' : ($value ? 'true' : 'false');
    }
}
