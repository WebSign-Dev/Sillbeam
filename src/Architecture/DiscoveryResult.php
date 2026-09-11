<?php

declare(strict_types=1);

namespace Nexum\Architecture;

use Nexum\Module\ModuleDefinition;
use Nexum\Project\ProjectDefinition;

final readonly class DiscoveryResult
{
    /**
     * @param array<class-string, ModuleDefinition>  $modules
     * @param array<class-string, ProjectDefinition> $projects
     * @param list<string>                           $resources
     */
    public function __construct(
        public string $root,
        public string $namespace,
        public array $modules,
        public array $projects,
        public array $resources,
    ) {
    }
}
