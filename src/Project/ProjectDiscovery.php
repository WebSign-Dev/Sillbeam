<?php

declare(strict_types=1);

namespace Nexum\Project;

use Nexum\Architecture\DiscoveryOptions;
use Nexum\Architecture\IdDeriver;
use Nexum\Architecture\SourceInspector;
use Nexum\Project\Attribute\NexumProject;

final readonly class ProjectDiscovery
{
    public function __construct(private SourceInspector $inspector = new SourceInspector())
    {
    }

    /** @return array<class-string, ProjectDefinition> */
    public function discover(DiscoveryOptions $options): array
    {
        $projects = [];
        foreach ($this->inspector->descriptors($options, NexumProject::class) as $file => $class) {
            $allowed = false;
            foreach ($options->projectRoots as $root) {
                $allowed = $allowed || $root->contains($file);
            }
            if (!$allowed) {
                throw new \LogicException('Project descriptor "'.$class.'" is outside the allowed project discovery boundaries.');
            }
            $metadata = $this->inspector->reflection($class, $file)->getAttributes(NexumProject::class)[0]->newInstance();
            $directory = \dirname($file);
            foreach ($projects as $known) {
                if ($directory === $known->directory || str_starts_with($directory.'/', $known->directory.'/') || str_starts_with($known->directory.'/', $directory.'/')) {
                    throw new \LogicException('Nexum descriptors have overlapping project areas: '.$class.' and '.$known->class);
                }
            }
            $projects[$class] = new ProjectDefinition(
                id: $metadata->id ?? IdDeriver::fromClass($class, 'Project'),
                class: $class,
                description: $metadata->description,
                declaredModules: $metadata->modules,
                modules: [],
                directory: $directory,
                descriptorFile: $file,
                namespace: $options->namespaceForDirectory($directory),
                hosts: [],
                controllerDirectories: [],
                entityDirectories: [],
            );
        }

        return $projects;
    }
}
