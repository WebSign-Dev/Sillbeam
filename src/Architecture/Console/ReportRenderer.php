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
        $errors = \count(array_filter($data['findings'], static fn (array $finding): bool => 'error' === $finding['severity']));
        $lines = [
            '# Sillbeam architecture report', '',
            'Sillbeam version: '.self::escape($version), '',
            'Checked at: '.$checkedAt->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::ATOM), '',
            'Status: '.self::escape($data['status']), '',
            'Analysis complete: '.($data['complete'] ? 'yes' : 'no'), '',
            '## Summary', '',
            'Findings: '.\count($data['findings']).' · Diagnoses: '.\count($view['issues']), '',
            'Violations: '.$errors.' · Blockers: '.(\count($data['findings']) - $errors), '',
            'Modules checked: '.\count($data['modules']).' · Projects checked: '.\count($data['projects']), '',
        ];
        if (!$data['complete']) {
            $lines[] = '**Analysis incomplete.** Counts reflect available findings and catalog entries only; undiscovered problems may remain.';
            $lines[] = '';
        }
        $lines[] = '## Architecture overview';
        $lines[] = '';
        if (!$data['valid']) {
            $lines[] = 'Available declarations only: activation and graph validity are unknown. Declared edges below are evidence, not an approved runtime graph.';
            $lines[] = '';
        }
        if ([] === $data['modules'] && [] === $data['projects']) {
            $lines[] = $data['complete'] ? 'No module or Project catalog entries are available.' : 'No reliable catalog is available from this incomplete analysis.';
            $lines[] = '';
        }
        if ([] !== $data['modules']) {
            $lines = [...$lines, '### Modules', '', '| Module / descriptor | Scope / owner | State | Declared dependencies |', '| --- | --- | --- | --- |'];
            foreach ($data['modules'] as $module) {
                $state = !$data['valid'] ? 'unknown' : (true === ($module['active'] ?? null) ? 'active' : ($module['inactive_reason'] ?? 'unknown'));
                $lines[] = '| '.self::escape($module['class']).'<br>'.self::escape($module['descriptor']).' | '.self::escape($module['scope'].(null !== ($module['project'] ?? null) ? ' / '.$module['project'] : '')).' | '.self::escape($state).' | '.self::escape(implode(', ', $module['declared_dependencies'])).' |';
            }
            $lines[] = '';
        }
        if ([] !== $data['projects']) {
            $lines = [...$lines, '### Projects', '', '| Project / descriptor | Configured hosts | Declared modules |', '| --- | --- | --- |'];
            foreach ($data['projects'] as $project) {
                $lines[] = '| '.self::escape($project['class']).'<br>'.self::escape($project['descriptorFile']).' | '.self::escape(implode(', ', $project['hosts'])).' | '.self::escape(implode(', ', $project['declaredModules'])).' |';
            }
            $lines[] = '';
        }
        $lines[] = '## Diagnoses';
        $lines[] = '';
        if ([] === $view['issues']) {
            $lines[] = 'No architecture findings.';
            $lines[] = '';
        } else {
            $lines[] = 'Numbers identify groups in this check run only. Every original finding belongs to one diagnosis below.';
            $lines[] = '';
        }
        foreach ($view['issues'] as $index => $issue) {
            $lines = [...$lines, '### '.($index + 1).'. '.self::escape($issue['title']), '', self::escape($issue['relation']), '', '**Problem**', '', self::escape($issue['problem']), ''];
            $lines[] = '**Why it matters**';
            $lines[] = '';
            $lines[] = self::escape($issue['impact']);
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
                    $lines[] = '- '.self::escape($reference['file'].(\is_int($reference['line']) ? ':'.$reference['line'] : ''));
                }
                $lines[] = '';
            }
            $lines[] = '**Technical evidence**';
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
                $lines[] = '### '.self::escape($coupling['relation']);
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
