<?php

declare(strict_types=1);

namespace Nexum\Architecture;

use Nexum\Module\ModuleDiscovery;
use Nexum\Project\ProjectDiscovery;

final readonly class ArchitectureDiscovery
{
    public function __construct(
        private ProjectDiscovery $projects = new ProjectDiscovery(),
        private ModuleDiscovery $modules = new ModuleDiscovery(),
    ) {
    }

    public function discover(DiscoveryOptions $options): DiscoveryResult
    {
        $projects = $this->projects->discover($options);
        $modules = $this->modules->discover($options, $projects);
        $resources = [$options->root];
        foreach ($projects as $project) {
            $resources[] = $project->descriptorFile;
        }
        foreach ($modules as $module) {
            $resources[] = $module->descriptorFile;
        }

        return new DiscoveryResult($options->root, $options->namespace, $modules, $projects, array_values(array_unique($resources)));
    }
}
