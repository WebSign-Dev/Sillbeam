<?php

declare(strict_types=1);

namespace Sillbeam\Architecture;

use Sillbeam\Module\ModuleDefinition;
use Sillbeam\Project\ProjectDefinition;

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
}
