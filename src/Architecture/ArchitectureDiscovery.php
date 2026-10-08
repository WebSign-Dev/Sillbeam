<?php

declare(strict_types=1);

namespace Sillbeam\Architecture;

use Sillbeam\Module\ModuleDiscovery;
use Sillbeam\Project\ProjectDefinition;
use Sillbeam\Project\ProjectDiscovery;

final readonly class ArchitectureDiscovery
{
    public function __construct(private SourceInspector $inspector = new SourceInspector())
    {
    }

    public function discover(DiscoveryOptions $options): DiscoveryResult
    {
        $projects = (new ProjectDiscovery($this->inspector))->discover($options);
        $modules = (new ModuleDiscovery($this->inspector))->discover($options, $projects);
        $moduleDirectories = array_values(array_map(static fn ($module): string => $module->directory, $modules));
        foreach ($projects as $class => $project) {
            $projects[$class] = new ProjectDefinition(
                $project->id, $project->class, $project->description, $project->declaredModules, [],
                $project->directory, $project->descriptorFile, $project->namespace, [],
                $this->inspector->directories($project->directory, $options->controllerPatterns, $moduleDirectories, $options),
                $this->inspector->directories($project->directory, $options->entityPatterns, $moduleDirectories, $options),
            );
        }
        $resources = array_map(static fn (DiscoveryRoot $root): string => $root->path, $options->roots());
        $resources = [...$resources, ...array_keys($this->inspector->files($options))];

        return new DiscoveryResult($options->root, $options->namespace, $modules, $projects, array_values(array_unique($resources)));
    }
}
