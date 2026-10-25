<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;

/** @internal Standalone Markdown using the same diagnostic recommendations as the CLI. */
final readonly class ReportRenderer
{
    public function __construct(private ArchitectureReport $report)
    {
    }

    public function markdown(string $version, \DateTimeImmutable $checkedAt, bool $includeCouplings = false): string
    {
        $data = $this->report->data;
        $view = [] === $this->report->presentation ? ArchitecturePresentation::fromData($data) : $this->report->presentation;
        $catalog = new MarkdownCatalog($data);
        usort($data['modules'], static fn (array $a, array $b): int => strcmp($catalog->modules[$a['class']], $catalog->modules[$b['class']]));
        usort($data['projects'], static fn (array $a, array $b): int => strcmp($catalog->projects[$a['class']], $catalog->projects[$b['class']]));
        $errors = \count(array_filter($data['findings'], static fn (array $finding): bool => 'error' === $finding['severity']));
        $lines = [
            '# Sillbeam architecture report', '',
            'Sillbeam version: '.self::escape($version), '',
            'Checked at: '.$checkedAt->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::ATOM), '',
            'Status: '.self::escape($data['status']), '',
            'Analysis complete: '.($data['complete'] ? 'yes' : 'no'), '',
            '## Architecture Summary', '',
            'Findings: '.\count($data['findings']).' · Diagnoses: '.\count($view['issues']), '',
            'Violations: '.$errors.' · Blockers: '.(\count($data['findings']) - $errors), '',
            'Modules checked: '.\count($data['modules']).' · Projects checked: '.\count($data['projects']), '',
        ];
        if (!$data['complete']) {
            $lines[] = '**Analysis incomplete.** Counts reflect available findings and catalog entries only; undiscovered problems may remain.';
            $lines[] = '';
        }
        $lines[] = '## Architecture Overview';
        $lines[] = '';
        if (!$data['valid']) {
            $lines[] = 'Available declarations only: activation and graph validity are unknown. Declared edges below are evidence, not an approved runtime graph.';
            $lines[] = '';
        }
        if ([] === $data['modules'] && [] === $data['projects']) {
            $lines[] = $data['complete'] ? 'No module or Project catalog entries are available.' : 'No reliable catalog is available from this incomplete analysis.';
            $lines[] = '';
        }
        $lines = [...$lines, ...$this->overview($data, $catalog), '## Diagnoses & Resolutions', ''];
        if ([] === $view['issues']) {
            $lines[] = 'No architecture findings.';
            $lines[] = '';
        } else {
            $lines[] = 'Numbers identify groups in this check run only. Every original finding belongs to one diagnosis below.';
            $lines[] = '';
        }
        foreach ($view['issues'] as $index => $issue) {
            $lines = [...$lines, '### '.($index + 1).'. '.self::escape($issue['title']), '', self::escape(self::relation($issue, $catalog)), '', '**Problem**', '', self::escape(self::description($issue, $issue['original_problem'] ?? $issue['problem'], $catalog)), ''];
            $lines[] = '**Why it matters**';
            $lines[] = '';
            $lines[] = self::escape(self::description($issue, $issue['original_impact'] ?? $issue['impact'], $catalog));
            $lines[] = '';
            $lines[] = '**Next steps**';
            $lines[] = '';
            foreach (array_unique($issue['steps']) as $step) {
                $lines[] = '- '.self::escape($step);
            }
            $lines[] = '';
            if ([] !== $issue['references']) {
                $lines[] = '**Locations**';
                $lines[] = '';
                foreach ($issue['references'] as $reference) {
                    $lines[] = '- '.self::escape($reference['readable'] ?? $reference['file'].(\is_int($reference['line']) ? ':'.$reference['line'] : ''));
                }
                $lines[] = '';
            }
        }
        $lines = [...$lines, '## Technical Evidence', '', ...$this->identities($data, $catalog)];
        foreach ($view['issues'] as $index => $issue) {
            $lines[] = '### Diagnosis '.($index + 1).': '.self::escape($issue['title']);
            $lines[] = '';
            $lines[] = self::escape($issue['detail']);
            $lines[] = '';
            foreach ($issue['originals'] as $number => $original) {
                $finding = $original['finding'];
                $lines[] = '**Original finding '.($number + 1).'** — Rule: '.self::escape($finding['rule']).' ('.self::escape($finding['severity']).')';
                $lines[] = '';
                $lines[] = self::escape($finding['message']);
                $lines[] = '';
                foreach ($finding['context'] as $key => $value) {
                    $lines[] = '- '.self::escape((string) $key).': '.self::escape(null === $value ? 'null' : (string) $value);
                }
                if ([] !== $finding['context']) {
                    $lines[] = '';
                }
            }
        }
        if ($includeCouplings && [] !== $view['couplings']) {
            $lines = [...$lines, '## Application couplings (informational)', '', 'Observed references to application code outside modules; these are not architecture violations and do not change the exit code.', ''];
            foreach ($view['couplings'] as $coupling) {
                $source = $catalog->modules[$coupling['source'] ?? ''] ?? null;
                $relation = null === $source ? $coupling['relation'] : $source.' → '.$coupling['references'][0]['class'];
                $lines[] = '### '.self::escape($relation);
                $lines[] = '';
                foreach ($coupling['references'] as $reference) {
                    $lines[] = '- '.self::escape($reference['class']).' — At: '.self::escape($reference['file'].':'.$reference['line']).'; target: '.self::escape($reference['target_file']);
                }
                $lines[] = '';
            }
        }
        $lines[] = 'Only Sillbeam architecture rules were checked. This report does not assess overall code quality, security or Symfony service-container health.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $issue */
    private static function description(array $issue, string $text, MarkdownCatalog $catalog): string
    {
        $paths = [];
        foreach ($issue['references'] as $reference) {
            if (isset($reference['readable'])) {
                $path = $reference['readable'];
                $suffix = ':'.($reference['line'] ?? '');
                if (\is_int($reference['line']) && str_ends_with($path, $suffix)) {
                    $path = substr($path, 0, -\strlen($suffix));
                }
                $paths[$reference['file']] = $path;
            }
        }

        return strtr($catalog->prose($text), $paths);
    }

    /** @param array<string, mixed> $issue */
    private static function relation(array $issue, MarkdownCatalog $catalog): string
    {
        $label = static fn (string $class): string => $catalog->modules[$class] ?? $catalog->projects[$class] ?? $catalog->classes[$class] ?? $class;
        if ([] !== ($issue['relation_modules'] ?? [])) {
            return implode(' → ', array_map($label, $issue['relation_modules']));
        }
        $context = $issue['originals'][0]['finding']['context'] ?? [];
        $source = $context['module'] ?? $context['project'] ?? null;
        $target = $context['target'] ?? $context['class'] ?? null;
        if (\is_string($source) || \is_string($target)) {
            return (\is_string($source) ? $label($source) : 'Application').(\is_string($target) ? ' → '.$label($target) : '');
        }

        return $catalog->prose($issue['relation']);
    }

    /** @param array<string, mixed> $data
     * @return list<string>
     */
    private function overview(array $data, MarkdownCatalog $catalog): array
    {
        $lines = [];
        $details = [];
        if ([] !== $data['modules']) {
            $lines = ['### Modules', ''];
            $rows = $blocks = [];
            foreach ($data['modules'] as $module) {
                $name = $catalog->modules[$module['class']];
                $dependencies = array_values(array_map(static fn (string $class): string => $catalog->modules[$class], $module['declared_dependencies']));
                $cell = self::listSummary($dependencies, 'declared');
                if (self::longList($dependencies)) {
                    $details = [...$details, '#### Dependencies of '.self::escape($name), ''];
                    foreach ($dependencies as $dependency) {
                        $details[] = '- '.self::escape($dependency);
                    }
                    $details[] = '';
                }
                $scope = 'private' === $module['scope'] ? 'Private' : 'Shared';
                $state = !$data['valid'] || !$data['complete'] ? 'Unknown' : (true === ($module['active'] ?? null) ? 'Active' : (false === ($module['active'] ?? null) && \in_array($module['inactive_reason'] ?? null, ['disabled', 'unassigned'], true) ? ucfirst($module['inactive_reason']) : 'Unknown'));
                if (mb_strlen($name) <= 48) {
                    $rows[] = '| '.self::escape($name).' | '.$scope.' | '.$state.' | '.self::escape($cell).' |';
                } else {
                    $blocks = [...$blocks, '#### '.self::escape($name), '', '- Scope: '.$scope, '- State: '.$state, '- Dependencies: '.self::escape($cell), ''];
                }
            }
            if ([] !== $rows) {
                $lines = [...$lines, '| Module | Scope | State | Dependencies |', '| --- | --- | --- | --- |', ...$rows, ''];
            }
            $lines = [...$lines, ...$blocks, ...$details];
        }
        if ([] !== $data['projects']) {
            $lines = [...$lines, '### Projects', ''];
            $rows = $blocks = [];
            $details = [];
            foreach ($data['projects'] as $project) {
                $name = $catalog->projects[$project['class']];
                $modules = array_values(array_map(static fn (string $class): string => $catalog->modules[$class], $project['declaredModules']));
                $hosts = $project['hosts'];
                if (mb_strlen($name) <= 48) {
                    $rows[] = '| '.self::escape($name).' | '.self::escape(self::listSummary($hosts, 'hosts')).' | '.self::escape(self::listSummary($modules, 'declared')).' |';
                } else {
                    $blocks = [...$blocks, '#### '.self::escape($name), '', '- Configured hosts: '.self::escape(self::listSummary($hosts, 'hosts')), '- Declared modules: '.self::escape(self::listSummary($modules, 'declared')), ''];
                }
                foreach (['Hosts' => $hosts, 'Declared modules' => $modules] as $title => $items) {
                    if (self::longList($items)) {
                        $details = [...$details, '#### '.$title.' of '.self::escape($name), ''];
                        foreach ($items as $item) {
                            $details[] = '- '.self::escape($item);
                        }
                        $details[] = '';
                    }
                }
            }
            if ([] !== $rows) {
                $lines = [...$lines, '| Project | Configured hosts | Declared modules |', '| --- | --- | --- |', ...$rows, ''];
            }
            $lines = [...$lines, ...$blocks, ...$details];
        }

        return $lines;
    }

    /** @param list<string> $items */
    private static function longList(array $items): bool
    {
        return \count($items) > 3 || mb_strlen(implode(', ', $items)) > 60;
    }

    /** @param list<string> $items */
    private static function listSummary(array $items, string $unit): string
    {
        return [] === $items ? '—' : (self::longList($items) ? \count($items).' '.$unit : implode(', ', $items));
    }

    /** @param array<string, mixed> $data
     * @return list<string>
     */
    private function identities(array $data, MarkdownCatalog $catalog): array
    {
        $lines = [];
        if ([] !== $data['modules'] || [] !== $data['projects'] || [] !== $catalog->unresolvedModules) {
            $lines = ['### Catalog identities', '', 'These identities map the overview labels to original descriptor classes and files.', ''];
        }
        foreach ($data['modules'] as $module) {
            $lines = [...$lines, '**Module: '.self::escape($catalog->modules[$module['class']]).'**', '', '- Class: '.self::escape($module['class']), '- Descriptor: '.self::escape($module['descriptor'])];
            if (null !== ($module['project'] ?? null)) {
                $lines[] = '- Owning Project: '.self::escape($module['project']);
            }
            $lines[] = '';
        }
        foreach ($data['projects'] as $project) {
            $lines = [...$lines, '**Project: '.self::escape($catalog->projects[$project['class']]).'**', '', '- Class: '.self::escape($project['class']), '- Descriptor: '.self::escape($project['descriptorFile']), ''];
        }
        foreach ($catalog->unresolvedModules as $class) {
            $lines = [...$lines, '**Unresolved module: '.self::escape($catalog->modules[$class]).'**', '', '- Reported class: '.self::escape($class), ''];
        }
        foreach ($catalog->classes as $class => $label) {
            $lines = [...$lines, '**Referenced class: '.self::escape($label).'**', '', '- Reported class: '.self::escape($class), ''];
        }
        if ([] === $lines && [] === $data['findings']) {
            $lines = ['No additional technical evidence.', ''];
        }

        return $lines;
    }

    private static function escape(string $text): string
    {
        if (1 !== preg_match('//u', $text)) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/[\p{Cc}]|(?:(?!\x{200D})\p{Cf})/u', '', $text) ?? '';
        // Escape ampersands first so source entities cannot become active Markdown/HTML.
        $text = str_replace('&', '&amp;', $text);

        $escaped = (string) preg_replace('/([\\\\`*_{}\[\]()<>#+|!~-])/', '\\\\$1', $text);

        return preg_replace_callback('/^([0-9]{1,9})\\.(?= )/', static fn (array $match): string => $match[1].'\\.', $escaped) ?? '';
    }
}
