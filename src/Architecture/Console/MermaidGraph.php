<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;

/**
 * Exports catalog and direct relationship evidence; never builds a runtime graph.
 *
 * Descriptor classes identify nodes. Sorted catalogs receive separate ASCII IDs,
 * so colliding logical IDs and arbitrary label text cannot change graph syntax.
 * Cycles are marked from existing finding participants, without inferring paths.
 *
 * @internal
 */
final class MermaidGraph
{
    /** Returns raw, deterministic Mermaid source with a trailing newline and no terminal markup. */
    public function render(ArchitectureReport $report): string
    {
        $modules = $report->data['modules'];
        $projects = $report->data['projects'];
        usort($modules, static fn (array $a, array $b): int => strcmp($a['class'], $b['class']));
        usort($projects, static fn (array $a, array $b): int => strcmp($a['class'], $b['class']));
        $moduleIds = $projectIds = [];
        foreach ($modules as $index => $module) {
            $moduleIds[$module['class']] = \sprintf('m%04d', $index + 1);
        }
        foreach ($projects as $index => $project) {
            $projectIds[$project['class']] = \sprintf('p%04d', $index + 1);
        }
        $cycles = [];
        foreach ($report->data['findings'] as $finding) {
            if ('dependency.cycle' === $finding['rule']) {
                foreach (explode(', ', (string) ($finding['context']['cycle'] ?? '')) as $class) {
                    $cycles[$class] = true;
                }
            }
        }
        $complete = $report->data['complete'] && ($report->presentation['module_inspection']['references_complete'] ?? false);
        $lines = ['flowchart LR'];
        $lines[] = '    analysis_status["Analysis: '.$this->label($report->data['status']).'; '.($report->data['valid'] ? 'catalog evidence; runtime activation only where known' : 'diagnostic evidence; NOT a validated runtime graph').'; '.($report->data['complete'] ? 'analysis complete' : 'analysis incomplete').'; '.($complete ? 'Reference evidence complete' : 'Reference evidence incomplete; missing edges do not prove absence').'"]';
        $lines[] = '    legend["declared: direct descriptor dependency; observed undeclared: recorded reference without declaration; active membership: validated Project selection; cycle: recorded participants only"]';
        if ([] === $modules) {
            $lines[] = '    catalog_notice["Module catalog unavailable or empty; no runtime membership inferred"]';
        }
        $lines[] = '    subgraph shared_scope["Shared modules"]';
        foreach ($modules as $module) {
            if ('shared' === $module['scope']) {
                $lines[] = $this->node($module, $moduleIds[$module['class']], isset($cycles[$module['class']]));
            }
        }
        $lines[] = '    end';
        foreach ($projects as $index => $project) {
            $lines[] = '    '.$projectIds[$project['class']].'["Project: '.$this->label($project['id']).' | '.$this->label($project['class']).'; membership='.(null === $project['modules'] ? 'unknown' : 'validated').'"]';
            $lines[] = '    subgraph project_scope_'.\sprintf('%04d', $index + 1).'["Private modules | '.$this->label($project['id']).' | '.$this->label($project['class']).'"]';
            foreach ($modules as $module) {
                if ('private' === $module['scope'] && $module['project'] === $project['class']) {
                    $lines[] = $this->node($module, $moduleIds[$module['class']], isset($cycles[$module['class']]));
                }
            }
            $lines[] = '    end';
        }
        $orphans = array_filter($modules, static fn (array $module): bool => 'private' === $module['scope'] && !isset($projectIds[$module['project']]));
        if ([] !== $orphans) {
            $lines[] = '    subgraph unknown_owner["Private modules | owner not in available Project catalog"]';
            foreach ($orphans as $module) {
                $lines[] = $this->node($module, $moduleIds[$module['class']], isset($cycles[$module['class']]));
            }
            $lines[] = '    end';
        }
        $unresolved = [];
        foreach ($modules as $module) {
            foreach (array_merge($module['declared_dependencies'], $module['observed_dependencies']) as $target) {
                if (!isset($moduleIds[$target])) {
                    $unresolved[$target] = true;
                }
            }
        }
        ksort($unresolved, \SORT_STRING);
        $targetIds = $moduleIds;
        foreach (array_keys($unresolved) as $index => $class) {
            $id = \sprintf('u%04d', $index + 1);
            $targetIds[$class] = $id;
            $lines[] = '    '.$id.'["unresolved module target | '.$this->label($class).'; activation unknown"]';
            $lines[] = '    class '.$id.' unknown;';
        }
        $edges = [];
        foreach ($modules as $module) {
            foreach (array_unique($module['declared_dependencies']) as $target) {
                $edges[] = '    '.$moduleIds[$module['class']].' -->|declared| '.$targetIds[$target];
            }
            foreach (array_diff(array_unique($module['observed_dependencies']), $module['declared_dependencies']) as $target) {
                $edges[] = '    '.$moduleIds[$module['class']].' -.->|observed undeclared| '.$targetIds[$target];
            }
        }
        foreach ($projects as $project) {
            foreach ($project['modules'] ?? [] as $class) {
                if (isset($moduleIds[$class])) {
                    $edges[] = '    '.$projectIds[$project['class']].' -.->|active membership| '.$moduleIds[$class];
                }
            }
        }
        $edges = array_values(array_unique($edges));
        sort($edges, \SORT_STRING);
        array_push($lines, ...$edges);
        $styles = ['disabled' => [], 'unknown' => [], 'cycle' => []];
        foreach ($modules as $module) {
            $id = $moduleIds[$module['class']];
            if (false === $module['enabled']) {
                $styles['disabled'][] = $id;
            }
            if (null === $module['active'] || null === $module['enabled']) {
                $styles['unknown'][] = $id;
            }
            if (isset($cycles[$module['class']])) {
                $styles['cycle'][] = $id;
            }
        }
        foreach ($styles as $style => $ids) {
            if ([] !== $ids) {
                $lines[] = '    class '.implode(',', $ids).' '.$style.';';
            }
        }
        $findings = $report->data['findings'];
        usort($findings, static fn (array $a, array $b): int => [$a['rule'], $a['message']] <=> [$b['rule'], $b['message']]);
        foreach ($findings as $index => $finding) {
            $lines[] = '    finding_'.\sprintf('%04d', $index + 1).'["'.$this->label($finding['rule'].': '.$finding['message']).'"]';
        }
        $lines[] = '    classDef disabled fill:#eee,color:#555,stroke:#777;';
        $lines[] = '    classDef unknown stroke-dasharray:5 5;';
        $lines[] = '    classDef cycle stroke:#c22,stroke-width:3px;';

        return implode("\n", $lines)."\n";
    }

    /** @param array<string, mixed> $module */
    private function node(array $module, string $id, bool $cycle): string
    {
        $enabled = $this->boolean($module['enabled']);
        $active = $this->boolean($module['active']);
        $state = null === $module['enabled'] || null === $module['active'] ? 'unknown' : (!$module['enabled'] ? 'disabled' : ($module['active'] ? 'active' : 'unassigned'));

        return '        '.$id.'["'.$this->label($module['id']).' | '.$this->label($module['class']).' | '.$this->label($module['scope']).(null === $module['project'] ? '' : ' | owner: '.$this->label($module['project'])).'; enabled='.$enabled.'; active='.$active.'; state='.$state.($cycle ? '; recorded cycle member' : '').'"]';
    }

    private function boolean(?bool $value): string
    {
        return null === $value ? 'unknown' : ($value ? 'true' : 'false');
    }

    /**
     * Encodes external ASCII punctuation as Mermaid decimal entities, including
     * entity introducers, quotes, HTML, directives and Markdown delimiters.
     * Controls/bidi/line separators become spaces; invalid UTF-8 gets a safe label.
     */
    private function label(string $value): string
    {
        $value = preg_replace('/[\p{C}\p{Zl}\p{Zp}]/u', ' ', $value) ?? 'invalid UTF-8 label';

        return preg_replace_callback('/[^a-zA-Z0-9 _\-\x80-\xFF]/', static fn (array $match): string => '#'.\ord($match[0]).';', $value) ?? '';
    }
}
