<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Diagnostic;

use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\CompiledArchitecture;
use Sillbeam\Core\Architecture\DiscoveryResult;
use Sillbeam\Core\Module\ModuleDefinition;
use Sillbeam\Core\Project\ProjectDefinition;

/** @internal Diagnostic evidence only. Never a Runtime registry/activation/routing input. */
final readonly class DiagnosticContext
{
    /** @param array<class-string, ModuleDefinition> $modules Original declarations, not enabled runtime modules
     * @param array<class-string, ProjectDefinition>                                                         $projects              original declarations, not compiled graphs
     * @param array<class-string, 'active'|'disabled'|'unassigned'|'unknown'>                                $states
     * @param array<class-string, list<string>>                                                              $projectHosts
     * @param array<class-string, list<class-string>>                                                        $projectModules        diagnostic activation membership only
     * @param array<class-string, list<array{module: class-string, class: string, file: string, line: int}>> $references
     * @param array<class-string, list<array{class: string, file: string, line: int, target_file: string}>>  $applicationReferences
     */
    private function __construct(
        public array $modules,
        public array $projects,
        public bool $complete,
        private array $states,
        private array $projectModules,
        private array $projectHosts,
        public array $references,
        public array $applicationReferences,
    ) {
    }

    /** Copies only diagnostic data; it never retains the compiler candidate object.
     * @param array<class-string, list<array{module: class-string, class: string, file: string, line: int}>> $references
     * @param array<class-string, list<array{class: string, file: string, line: int, target_file: string}>>  $applicationReferences
     */
    public static function fromEvidence(?DiscoveryResult $discovery, ?CompiledArchitecture $candidate, bool $complete, array $references = [], array $applicationReferences = []): self
    {
        $modules = $discovery->modules ?? [];
        $projects = $discovery->projects ?? [];
        ksort($modules, \SORT_STRING);
        ksort($projects, \SORT_STRING);
        $complete = $complete && null !== $discovery;
        $states = [];
        $active = array_fill_keys($candidate?->usedModules() ?? [], true);
        foreach ($modules as $class => $module) {
            $enabled = $candidate?->modules[$class]->enabled ?? null;
            $states[$class] = !$complete || null === $enabled ? 'unknown' : (!$enabled ? 'disabled' : (isset($active[$class]) ? 'active' : 'unassigned'));
        }

        $hosts = [];
        if ($complete && null !== $candidate) {
            foreach ($candidate->projects as $class => $project) {
                $hosts[$class] = $project->hosts;
            }
        }

        return new self($modules, $projects, $complete, $states, $complete ? ($candidate->projectModules ?? []) : [], $hosts, $references, $applicationReferences);
    }

    public static function fromAnalysis(ArchitectureAnalysisResult $analysis): self
    {
        return $analysis->diagnosticContext ?? self::fromEvidence($analysis->discovery, $analysis->architecture, $analysis->isComplete(), $analysis->references, $analysis->applicationReferences);
    }

    /** @return 'active'|'disabled'|'unassigned'|'unknown' */
    public function state(string $module, ?string $project = null): string
    {
        $state = $this->states[$module] ?? 'unknown';
        if (null === $project || 'unknown' === $state || 'disabled' === $state) {
            return $state;
        }
        if (!isset($this->projectModules[$project])) {
            return 'unknown';
        }

        return \in_array($module, $this->projectModules[$project], true) ? 'active' : 'unassigned';
    }

    /** Canonical override key only when both scope and logical identity are unambiguous. */
    public function moduleConfigurationKey(string $class): ?string
    {
        $module = $this->modules[$class] ?? null;
        if (null === $module) {
            return null;
        }
        $projectId = null;
        if (null !== $module->projectClass) {
            $projectId = $this->projectConfigurationId($module->projectClass);
            if (null === $projectId) {
                return null;
            }
        }
        foreach ($this->modules as $other) {
            if ($other->class !== $class && $other->id === $module->id && $other->projectClass === $module->projectClass) {
                return null;
            }
        }

        return $module->key($projectId);
    }

    public function projectConfigurationId(string $class): ?string
    {
        $project = $this->projects[$class] ?? null;
        if (null === $project) {
            return null;
        }
        foreach ($this->projects as $other) {
            if ($other->class !== $class && $other->id === $project->id) {
                return null;
            }
        }

        return $project->id;
    }

    /** @return list<ProjectDefinition> */
    public function projectsForHost(string $host): array
    {
        $projects = [];
        foreach ($this->projects as $class => $project) {
            if (\in_array($host, $this->projectHosts[$class] ?? [], true)) {
                $projects[] = $project;
            }
        }

        return $projects;
    }

    public function graph(): DeclaredDependencyGraph
    {
        return new DeclaredDependencyGraph($this->modules);
    }

    /** @return array{source: class-string, target: class-string, basis: 'declared_dependency'|'observed_reference'}|null */
    public function relationship(string $source, string $target): ?array
    {
        $module = $this->modules[$source] ?? null;
        if (null === $module) {
            return null;
        }
        foreach ($module->dependencies as $dependency) {
            if ($dependency === $target) {
                return ['source' => $module->class, 'target' => $dependency, 'basis' => 'declared_dependency'];
            }
        }
        foreach ($this->references[$source] ?? [] as $reference) {
            if ($reference['module'] === $target) {
                return ['source' => $module->class, 'target' => $reference['module'], 'basis' => 'observed_reference'];
            }
        }

        return null;
    }

    public function assessDependency(string $source, string $target): DependencyAssessment
    {
        $from = $this->modules[$source] ?? null;
        $to = $this->modules[$target] ?? null;
        $graph = $this->graph();
        $path = $graph->path($target, $source);
        $unresolved = $graph->hasUnresolvedTargets($target);
        $known = null !== $from && null !== $to;
        $privacy = !$this->complete || !$known ? null : (!$to->isPrivate() || $from->projectClass === $to->projectClass);
        $declared = null !== $from && \in_array($target, $from->dependencies, true);
        $return = null !== $path ? true : ($this->complete && $known && !$unresolved ? false : null);
        $sourceState = $this->state($source);
        $targetState = $this->state($target);
        $blockers = [];
        if (!$this->complete) {
            $blockers[] = 'incomplete';
        }
        if ($unresolved) {
            $blockers[] = 'unresolved_dependencies';
        }
        if (null === $from) {
            $blockers[] = 'unknown_source';
        }
        if (null === $to) {
            $blockers[] = 'unknown_target';
        }
        if (true === $return) {
            $blockers[] = 'cycle';
        }
        if (false === $privacy) {
            $blockers[] = 'private_boundary';
        }
        if ('disabled' === $targetState) {
            $blockers[] = 'target_disabled';
        }
        if ('unknown' === $sourceState || 'unknown' === $targetState) {
            $blockers[] = 'unknown_activation';
        }
        $projectStates = [];
        foreach ($this->projects as $class => $project) {
            if ('active' === $this->state($source, $class)) {
                $projectStates[$class] = $this->state($target, $class);
            }
        }
        sort($blockers, \SORT_STRING);

        return new DependencyAssessment($source, $target, $from?->descriptorFile, $path, $return, $privacy, $declared ? true : ($this->complete && null !== $from ? false : null), $sourceState, $targetState, $projectStates, $blockers);
    }
}
