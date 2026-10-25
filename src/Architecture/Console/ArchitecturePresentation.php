<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\Diagnostic\DiagnosticCollection;
use Sillbeam\Core\Architecture\Diagnostic\DiagnosticContext;

/** @internal Scalar human view of actual diagnostic evidence, separate from schema-1 JSON. */
final class ArchitecturePresentation
{
    /** @return array<string, mixed> */
    public static function fromAnalysis(ArchitectureAnalysisResult $analysis, ?string $projectDir = null): array
    {
        $context = DiagnosticContext::fromAnalysis($analysis);
        $collection = DiagnosticCollection::fromAnalysis($analysis);
        $root = $projectDir ?? $analysis->discovery?->root;
        if (null !== $root && 'src' === basename($root)) {
            $root = \dirname($root);
        }
        $labels = [];
        $paths = [];
        foreach ($context->projects as $project) {
            $labels[$project->class] = self::shortClass($project->class, 'Project');
            $paths[$project->descriptorFile] = self::relative($project->descriptorFile, $root, $context);
        }
        foreach ($context->modules as $module) {
            $name = self::shortClass($module->class, 'Module');
            $labels[$module->class] = null === $module->projectClass ? $name : ($labels[$module->projectClass] ?? self::shortClass($module->projectClass, 'Project')).' / '.$name;
            $paths[$module->descriptorFile] = self::relative($module->descriptorFile, $root, $context);
        }
        foreach ($analysis->findings as $finding) {
            if (\is_string($finding->context['file'] ?? null)) {
                $paths[$finding->context['file']] = self::relative($finding->context['file'], $root, $context);
            }
        }
        $readable = static fn (string $text): string => strtr(self::friendly($text, $labels), $paths);
        $issues = [];
        foreach ($collection->diagnoses as $diagnosis) {
            $first = $diagnosis->findings[0];
            $rule = $first->rule;
            [$title, $category] = self::heading($rule, \count($diagnosis->findings));
            $source = $first->context['module'] ?? $first->context['project'] ?? null;
            $target = $first->context['target'] ?? $first->context['class'] ?? null;
            $relation = [] !== $diagnosis->modules && 'declared_cycle' === $diagnosis->basis
                ? implode(' → ', array_map(static fn (string $class): string => $labels[$class] ?? $class, $diagnosis->modules))
                : (\is_string($source) ? ($labels[$source] ?? self::shortClass($source, 'Module')) : 'Application').(\is_string($target) ? ' → '.($labels[$target] ?? $target) : '');
            $summary = [];
            $impacts = [];
            $references = [];
            $originals = [];
            $rules = [];
            $caution = false;
            foreach ($diagnosis->details as $detail) {
                $finding = $detail->finding;
                $summary[] = $readable($detail->problem);
                $impacts[] = $readable($detail->impact);
                $rules[] = $finding->rule;
                $caution = $caution || 'fatal' === $finding->severity || \in_array($finding->rule, ['dependency.cycle', 'dependency.private', 'code.private', 'project.private_module', 'code.undeclared_dependency'], true);
                $file = $finding->context['file'] ?? null;
                $line = $finding->context['line'] ?? null;
                if (\is_string($file)) {
                    $location = self::relative($file, $root, $context).(\is_int($line) ? ':'.$line : '');
                    $reference = ['file' => $file, 'line' => \is_int($line) ? $line : null, 'readable' => $location];
                    $references[serialize([$file, $line])] = $reference;
                }
                $originals[] = [
                    'finding' => get_object_vars($finding),
                    'readable' => $readable($finding->message).(\is_string($file) ? ' At: '.self::relative($file, $root, $context).(\is_int($line) ? ':'.$line : '') : ''),
                    'technical' => '['.$finding->rule.'] '.$finding->severity.': '.$finding->message.' Context: '.json_encode($finding->context, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_INVALID_UTF8_SUBSTITUTE),
                ];
            }
            foreach ($diagnosis->descriptors as $file) {
                if (!array_any($references, static fn (array $reference): bool => $file === $reference['file'])) {
                    $references[serialize([$file, null])] = ['file' => $file, 'line' => null, 'readable' => self::relative($file, $root, $context)];
                }
            }
            $steps = [];
            foreach ($diagnosis->resolutions as $resolution) {
                foreach ($resolution->steps as $step) {
                    $steps[] = ('option' === $resolution->kind ? 'Domain option: ' : '').$readable($step);
                }
            }
            $impact = implode(' ', array_unique($impacts));
            $issues[] = [
                'title' => $title, 'category' => $category, 'relation' => $relation,
                'problem' => implode(' ', array_unique($summary)), 'impact' => $impact,
                'summary' => implode(' ', array_unique($summary)).($caution ? '' : ' '.$impact),
                'caution' => $caution ? $impact : null,
                'steps' => array_values(array_unique($steps)), 'findings' => \count($diagnosis->findings),
                'references' => array_values($references), 'rule' => implode(', ', array_unique($rules)),
                'detail' => 'Evidence: '.$diagnosis->basis.'. '.implode(' ', array_unique(array_merge(...array_map(static fn ($resolution): array => $resolution->configuration, $diagnosis->resolutions)))),
                'originals' => $originals,
            ];
        }
        $couplings = [];
        foreach ($analysis->applicationReferences as $source => $references) {
            $targets = [];
            foreach ($references as $reference) {
                $targets[$reference['class']][] = $reference;
            }
            ksort($targets);
            foreach ($targets as $class => $evidence) {
                $name = $labels[$source] ?? self::shortClass($source, 'Module');
                $couplings[] = ['relation' => $name.' → '.$class, 'summary' => $name.' references application code outside its module.', 'references' => $evidence];
            }
        }

        return [
            'modules' => \count($context->modules), 'projects' => \count($context->projects),
            'findings' => $collection->findingCount(), 'issues' => $issues, 'couplings' => $couplings,
            'status' => !$analysis->isComplete() ? 'unavailable' : ($analysis->isValid() ? 'valid' : 'violations'),
        ];
    }

    /** Legacy/custom report providers may supply schema data without an analysis snapshot.
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function fromData(array $data): array
    {
        $findings = [];
        foreach ($data['findings'] as $finding) {
            $context = $finding['context'];
            $findings[] = new ArchitectureFinding($finding['rule'], $finding['message'], $context, $finding['severity']);
        }
        $view = self::fromAnalysis(new ArchitectureAnalysisResult($findings));
        $view['modules'] = \count($data['modules']);
        $view['projects'] = \count($data['projects']);
        $view['status'] = $data['status'];
        foreach ($data['modules'] as $module) {
            $targets = [];
            foreach ($module['application_references'] ?? [] as $reference) {
                $targets[$reference['class']][] = $reference;
            }
            foreach ($targets as $class => $references) {
                $view['couplings'][] = ['relation' => $module['id'].' → '.$class, 'summary' => $module['id'].' references application code outside its module.', 'references' => $references];
            }
        }

        return $view;
    }

    private static function shortClass(string $class, string $suffix): string
    {
        $name = basename(str_replace('\\', '/', $class));

        return str_ends_with($name, $suffix) && \strlen($name) > \strlen($suffix) ? substr($name, 0, -\strlen($suffix)) : $name;
    }

    private static function relative(string $file, ?string $root, DiagnosticContext $context): string
    {
        foreach ($context->modules as $module) {
            if (!$module->isPrivate() && str_starts_with($file, $module->directory.'/')) {
                return basename($module->directory).'/'.substr($file, \strlen($module->directory) + 1);
            }
        }
        if (null !== $root && str_starts_with($file, rtrim($root, '/').'/')) {
            return substr($file, \strlen(rtrim($root, '/')) + 1);
        }
        // No project root is known for custom reports: retain a readable suffix, never invent a root.
        if (false !== ($index = strpos($file, '/src/'))) {
            return substr($file, $index + 1);
        }

        return ltrim($file, '/');
    }

    /** @param array<string, string> $labels */
    private static function friendly(string $text, array $labels): string
    {
        if ([] === $labels) {
            return $text;
        }
        uksort($labels, static fn (string $a, string $b): int => \strlen($b) <=> \strlen($a));
        $pattern = '~(?<![a-zA-Z0-9_\\\\])(?:'.implode('|', array_map(static fn (string $key): string => preg_quote($key, '~'), array_keys($labels))).')(?![a-zA-Z0-9_\\\\])~';

        return preg_replace_callback($pattern, static fn (array $match): string => $labels[$match[0]], $text) ?? $text;
    }

    /** @return array{string, string} */
    private static function heading(string $rule, int $count): array
    {
        return match ($rule) {
            'dependency.cycle' => ['Circular module dependency', 'Dependency graph'],
            'code.undeclared_dependency' => $count > 1 ? ['Module accesses are not declared', 'Missing declarations'] : ['Undeclared module access', 'Dependency declaration'],
            'code.unknown_class' => [$count > 1 ? 'Application classes cannot be resolved' : 'Application class cannot be resolved', 'Unresolved source'],
            'dependency.disabled' => ['A required module is disabled', 'Activation'],
            'code.inactive' => ['A required module is inactive', 'Activation'],
            'dependency.private', 'code.private', 'project.private_module' => ['Private module used outside its Project', 'Project boundary'],
            'dependency.unknown' => ['Unknown module dependency', 'Dependency declaration'],
            'project.unknown_module' => ['Project requires an unknown module', 'Activation'],
            'module.duplicate_key', 'project.duplicate_id' => ['Duplicate architecture identity', 'Identity'],
            'project.host_required' => ['Project needs an exact host', 'Project hosts'],
            'project.host_invalid' => ['Invalid Project host', 'Project hosts'],
            'project.host_duplicate' => ['Host assigned to multiple Projects', 'Project hosts'],
            'module.unknown_override', 'project.unknown_configuration' => ['Configuration refers to an unknown area', 'Configuration'],
            'source.parse' => ['PHP source cannot be parsed', 'Analysis incomplete'],
            'source.read' => ['PHP source cannot be read', 'Analysis incomplete'],
            'source.duplicate_class' => ['Duplicate PHP class declaration', 'Analysis incomplete'],
            'report.execution' => ['Architecture check could not run', 'Analysis incomplete'],
            default => [ucfirst(str_replace(['.', '_'], ' ', $rule)), 'Architecture'],
        };
    }
}
