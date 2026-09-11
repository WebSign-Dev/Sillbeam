<?php

declare(strict_types=1);

namespace Nexum\Project;

final readonly class ProjectRegistry implements ProjectRegistryInterface
{
    /** @var array<class-string, ProjectDefinition> */
    private array $projects;

    /** @param array<class-string, array<string, mixed>> $projects */
    public function __construct(array $projects)
    {
        $definitions = [];
        foreach ($projects as $class => $project) {
            $definitions[$class] = new ProjectDefinition(...$project);
        }
        $this->projects = $definitions;
    }

    public function all(): array
    {
        return $this->projects;
    }

    public function get(string $idOrClass): ProjectDefinition
    {
        if (isset($this->projects[$idOrClass])) {
            return $this->projects[$idOrClass];
        }
        foreach ($this->projects as $project) {
            if ($project->id === $idOrClass) {
                return $project;
            }
        }

        throw new \OutOfBoundsException(\sprintf('Unknown Nexum project "%s".', $idOrClass));
    }

    public function forHost(string $host): ProjectDefinition
    {
        $host = strtolower($host);
        if (1 === \count($this->projects)) {
            $projects = $this->projects;
            $project = reset($projects);
            if ([] === $project->hosts || \in_array($host, $project->hosts, true)) {
                return $project;
            }
        } else {
            foreach ($this->projects as $project) {
                if (\in_array($host, $project->hosts, true)) {
                    return $project;
                }
            }
        }

        throw new \OutOfBoundsException(\sprintf('No Nexum project is assigned to host "%s".', $host));
    }
}
