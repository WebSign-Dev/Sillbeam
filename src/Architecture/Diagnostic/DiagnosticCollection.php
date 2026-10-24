<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Diagnostic;

use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureFinding;

/** @internal Complete attribution with deterministic run-local IDs, not persistent issue numbers. */
final readonly class DiagnosticCollection
{
    /** @param list<ArchitectureFinding> $findings
     * @param list<Diagnosis>    $diagnoses
     * @param array<int, string> $assignments finding position => diagnosis ID, exactly once
     */
    private function __construct(public array $findings, public array $diagnoses, public array $assignments)
    {
    }

    public static function fromAnalysis(ArchitectureAnalysisResult $analysis): self
    {
        return self::fromContext($analysis->findings, DiagnosticContext::fromAnalysis($analysis));
    }

    /** @param list<ArchitectureFinding> $findings */
    public static function fromContext(array $findings, DiagnosticContext $context): self
    {
        $cycles = $context->graph()->cycles();
        $groups = [];
        $canResolve = $context->complete;
        foreach ($findings as $index => $finding) {
            $canResolve = $canResolve && 'fatal' !== $finding->severity;
            [$key, $basis, $modules] = self::relationship($finding, $index, $context, $cycles);
            $groups[$key] ??= ['basis' => $basis, 'modules' => $modules, 'indexes' => [], 'findings' => []];
            $groups[$key]['indexes'][] = $index;
            $groups[$key]['findings'][] = $finding;
        }
        ksort($groups, \SORT_STRING);
        $diagnoses = [];
        $assignments = [];
        foreach ($groups as $group) {
            $id = 'D'.(\count($diagnoses) + 1);
            $descriptors = [];
            foreach ($group['modules'] as $class) {
                if (isset($context->modules[$class])) {
                    $descriptors[$class] = $context->modules[$class]->descriptorFile;
                }
            }
            $resolutions = [];
            if ($canResolve && \in_array($group['basis'], ['declared_dependency', 'observed_reference'], true)) {
                $resolution = $context->assessDependency($group['modules'][0], $group['modules'][1])->resolution();
                if (null !== $resolution) {
                    $resolutions[] = $resolution;
                }
            }
            foreach ($group['indexes'] as $index) {
                $assignments[$index] = $id;
            }
            $explanation = match ($group['basis']) {
                'declared_cycle' => 'Findings refer to a verified directed cycle in module declarations.',
                'declared_dependency' => 'Findings refer to the same directed declared module dependency.',
                'observed_reference' => 'Findings refer to the same directed module relationship evidenced by code references.',
                default => 'Original finding retained individually; no shared cause or module relationship was established.',
            };
            $diagnoses[] = new Diagnosis($id, $group['basis'], $explanation, $group['indexes'], $group['findings'], $group['modules'], $descriptors, $resolutions);
        }
        ksort($assignments, \SORT_NUMERIC);

        return new self($findings, $diagnoses, $assignments);
    }

    /** @param list<list<class-string>> $cycles
     * @return array{string, 'finding'|'declared_cycle'|'declared_dependency'|'observed_reference', list<class-string>}
     */
    private static function relationship(ArchitectureFinding $finding, int $index, DiagnosticContext $context, array $cycles): array
    {
        // Legacy Finding metadata lists sorted participants; verify a real ordered witness instead.
        if ('dependency.cycle' === $finding->rule && \is_string($finding->context['cycle'] ?? null)) {
            $participants = explode(', ', $finding->context['cycle']);
            sort($participants, \SORT_STRING);
            foreach ($cycles as $cycle) {
                $members = array_values(array_unique($cycle));
                sort($members, \SORT_STRING);
                if ($participants === $members && \in_array($finding->context['module'] ?? null, $cycle, true)) {
                    return ['cycle:'.serialize($cycle), 'declared_cycle', $cycle];
                }
            }
        }
        $source = $finding->context['module'] ?? null;
        $target = $finding->context['target'] ?? null;
        if (\is_string($source) && \is_string($target) && null !== ($relationship = $context->relationship($source, $target))) {
            return ['relationship:'.serialize([$source, $target]), $relationship['basis'], [$relationship['source'], $relationship['target']]];
        }

        return ['finding:'.$index, 'finding', []];
    }
}
