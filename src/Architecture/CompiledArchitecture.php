<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Sillbeam\Core\Module\ModuleDefinition;
use Sillbeam\Core\Project\ProjectDefinition;

/**
 * Immutable validated module catalog and activation graphs returned by successful analysis.
 *
 * Application graphs apply without Projects; Project graphs contain dependency closures.
 * Constructing this data object directly does not validate its contents.
 *
 * @api
 */
final readonly class CompiledArchitecture
{
    /**
     * Stores catalogs and activation graphs; callers should obtain validated instances from analysis.
     *
     * @param array<class-string, ModuleDefinition>   $modules            module catalog keyed by descriptor class, including inactive entries
     * @param array<class-string, ProjectDefinition>  $projects           configured Project catalog keyed by descriptor class
     * @param list<class-string>                      $applicationModules active application closure; empty in Project mode
     * @param array<class-string, list<class-string>> $projectModules     active dependency closure per Project descriptor class
     */
    public function __construct(
        public array $modules,
        public array $projects,
        public array $applicationModules,
        public array $projectModules,
    ) {
    }

    public function projectMode(): bool
    {
        return [] !== $this->projects;
    }

    /**
     * Returns the unique union of application and Project activation graphs.
     *
     * @return list<class-string>
     */
    public function usedModules(): array
    {
        $used = $this->applicationModules;
        foreach ($this->projectModules as $modules) {
            $used = [...$used, ...$modules];
        }

        return array_values(array_unique($used));
    }

    /**
     * Explains application, Project selection/private ownership and dependency activation.
     *
     * @param class-string $class known module descriptor class
     *
     * @return list<string>
     */
    public function activationReasons(string $class): array
    {
        $reasons = [];
        if (\in_array($class, $this->applicationModules, true)) {
            $reasons[] = 'application';
        }
        foreach ($this->projects as $project) {
            if (\in_array($class, $project->declaredModules, true)) {
                $reasons[] = 'project:'.$project->id.':declared';
            }
            if ($this->modules[$class]->projectClass === $project->class && $this->modules[$class]->enabled) {
                $reasons[] = 'project:'.$project->id.':private';
            }
        }
        foreach ($this->usedModules() as $source) {
            if (\in_array($class, $this->modules[$source]->dependencies, true)) {
                $reasons[] = 'dependency:'.$source;
            }
        }

        return array_values(array_unique($reasons));
    }
}
