<?php

declare(strict_types=1);

namespace Sillbeam\Core\Project;

use Sillbeam\Core\Module\ModuleRegistryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class ProjectContext implements ProjectContextInterface
{
    public function __construct(
        private RequestStack $requests,
        private ProjectRegistryInterface $projects,
        private ModuleRegistryInterface $modules,
    ) {
    }

    public function current(): ?ProjectDefinition
    {
        if ([] === $this->projects->all() || null === ($request = $this->requests->getCurrentRequest())) {
            return null;
        }

        return $this->projects->forHost($request->getHost());
    }

    public function id(): ?string
    {
        return $this->current()?->id;
    }

    public function is(string $id): bool
    {
        return $this->id() === $id;
    }

    public function hasModule(string $module): bool
    {
        $project = $this->current();
        if (null === $project) {
            return isset($this->modules->forApplication()[$module]);
        }

        return isset($this->modules->forProject($project->class)[$module]);
    }
}
