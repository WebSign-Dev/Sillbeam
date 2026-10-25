<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;

/** @internal Human-readable views of the existing report data. */
final readonly class ReportRenderer
{
    public function __construct(private ArchitectureReport $report)
    {
    }

    public function markdown(string $version, \DateTimeImmutable $checkedAt): string
    {
        $data = $this->report->data;
        $lines = [
            '# Sillbeam architecture report', '',
            'Sillbeam version: '.self::escape($version), '',
            'Checked at: '.$checkedAt->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::ATOM), '',
            'Status: '.$data['status'], '',
            'Modules checked: '.\count($data['modules']), '',
            'Projects checked: '.\count($data['projects']), '',
            'Violations: '.$this->violations(), '',
            'Blockers: '.$this->blockers(), '',
        ];
        if (!$data['complete']) {
            $lines[] = 'Analysis incomplete. Counts reflect available findings and catalog entries only.';
            $lines[] = '';
        }
        foreach ($this->groups() as $group => $findings) {
            $lines[] = '## '.self::escape($group);
            $lines[] = '';
            foreach ($findings as $finding) {
                $lines[] = '### '.self::escape(self::title($finding['rule']));
                $lines[] = '';
                $lines[] = 'Rule: '.self::escape($finding['rule']).' ('.$finding['severity'].')';
                $lines[] = '';
                $lines[] = self::escape($finding['message']);
                $lines[] = '';
                if ('' !== ($location = self::location($finding['context']))) {
                    $lines[] = 'At: '.self::escape($location);
                    $lines[] = '';
                }
                if (\is_string($finding['context']['suggestion'] ?? null)) {
                    $lines[] = '**How to fix:** '.self::escape($finding['context']['suggestion']);
                    $lines[] = '';
                }
            }
        }
        $couplings = $this->couplings();
        if ([] !== $couplings) {
            $lines[] = '## Application couplings (informational)';
            $lines[] = '';
            foreach ($couplings as $module => $targets) {
                $lines[] = '### '.self::escape($module);
                $lines[] = '';
                foreach ($targets as $class => $references) {
                    $lines[] = '- '.self::escape($class).' — target: '.self::escape($references[0]['target_file']);
                    foreach ($references as $reference) {
                        $lines[] = '  - At: '.self::escape($reference['file'].':'.$reference['line']);
                    }
                }
                $lines[] = '';
            }
        }
        $lines[] = 'Only Sillbeam architecture rules were checked. This report does not assess overall code quality, security or Symfony service-container health.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /** @return array<string, array<string, list<array{class: string, file: string, line: int, target_file: string}>>> */
    private function couplings(): array
    {
        $couplings = [];
        foreach ($this->report->data['modules'] as $module) {
            foreach ($module['application_references'] ?? [] as $reference) {
                $couplings[$module['class']][$reference['class']][] = $reference;
            }
        }

        return $couplings;
    }

    private function violations(): int
    {
        return \count(array_filter($this->report->data['findings'], static fn (array $finding): bool => 'error' === $finding['severity']));
    }

    private function blockers(): int
    {
        return \count($this->report->data['findings']) - $this->violations();
    }

    /** @return array<string, list<array{rule: string, message: string, severity: string, context: array<string, string|int|null>}>> */
    private function groups(): array
    {
        $groups = [];
        foreach ($this->report->data['findings'] as $finding) {
            $context = $finding['context'];
            $group = isset($context['module']) ? 'Module '.$context['module'] : (isset($context['project']) ? 'Project '.$context['project'] : 'Application');
            $groups[$group][] = $finding;
        }
        ksort($groups);

        return $groups;
    }

    /** @param array<string, string|int|null> $context */
    private static function location(array $context): string
    {
        return isset($context['file']) ? $context['file'].(isset($context['line']) ? ':'.$context['line'] : '') : '';
    }

    private static function escape(string $text): string
    {
        return str_replace(["\r", "\n"], [' ', ' '], (string) preg_replace('/([\\\\`*_{}\[\]()<>#+|])/', '\\\\$1', $text));
    }

    private static function title(string $rule): string
    {
        return match ($rule) {
            'dependency.unknown' => 'Unknown module dependency',
            'dependency.cycle' => 'Circular module dependencies',
            'dependency.disabled', 'code.inactive' => 'Required module is inactive',
            'dependency.private', 'code.private', 'project.private_module' => 'Private module used outside its Project',
            'code.unknown_class' => 'Referenced class cannot be resolved',
            'code.undeclared_dependency' => 'Missing direct module dependency',
            'module.unknown_override', 'project.unknown_configuration' => 'Configuration refers to an unknown area',
            'module.duplicate_key', 'project.duplicate_id' => 'Duplicate architecture ID',
            'project.unknown_module' => 'Project refers to an unknown module',
            'project.host_required' => 'Project needs a host',
            'project.host_invalid' => 'Invalid Project host',
            'project.host_duplicate' => 'Host assigned to multiple Projects',
            'module.owner_missing', 'discovery.owner_missing' => 'Module has no owning Project',
            'source.parse' => 'PHP source cannot be parsed',
            'source.read' => 'PHP source cannot be read',
            'source.duplicate_class' => 'Duplicate PHP class',
            'discovery.descriptor_class', 'discovery.descriptor_file' => 'Descriptor does not match its PSR-4 location',
            'discovery.descriptor_metadata', 'discovery.descriptor_id' => 'Invalid descriptor metadata',
            'discovery.module_overlap', 'discovery.project_overlap', 'discovery.project_inside_module' => 'Architecture areas overlap',
            'discovery.module_boundary', 'discovery.project_boundary' => 'Descriptor outside its discovery root',
            'discovery.multiple_descriptors', 'discovery.multiple_attributes' => 'Ambiguous descriptor',
            'report.execution' => 'Architecture check could not run',
            default => ucfirst(str_replace(['.', '_'], ' ', $rule)),
        };
    }
}
