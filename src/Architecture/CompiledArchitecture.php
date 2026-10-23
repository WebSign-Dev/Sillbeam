<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Sillbeam\Core\Module\ModuleDefinition;
use Sillbeam\Core\Project\ProjectDefinition;

final readonly class CompiledArchitecture
{
    /**
     * @param array<class-string, ModuleDefinition>   $modules
     * @param array<class-string, ProjectDefinition>  $projects
     * @param list<class-string>                      $applicationModules
     * @param array<class-string, list<class-string>> $projectModules
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

    /** @return list<class-string> */
    public function usedModules(): array
    {
        $used = $this->applicationModules;
        foreach ($this->projectModules as $modules) {
            $used = [...$used, ...$modules];
        }

        return array_values(array_unique($used));
    }

    /** @return list<string> */
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
