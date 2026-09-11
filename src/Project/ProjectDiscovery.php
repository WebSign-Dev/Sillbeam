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
        foreach (glob($options->root.'/Project/*/*Project.php') ?: [] as $file) {
            $class = $this->inspector->classForFile($options, $file);
            $reflection = new \ReflectionClass($class);
            $attribute = $reflection->getAttributes(NexumProject::class)[0] ?? null;
            if (null === $attribute) {
                throw new \LogicException(\sprintf('Project descriptor "%s" must declare #[NexumProject].', $class));
            }
            $metadata = $attribute->newInstance();
            $directory = \dirname($file);
            $projects[$class] = new ProjectDefinition(
                id: $metadata->id ?? IdDeriver::fromClass($class, 'Project'),
                class: $class,
                description: $metadata->description,
                declaredModules: $metadata->modules,
                modules: [],
                directory: $directory,
                descriptorFile: $file,
                namespace: $this->inspector->namespaceForDirectory($options, $directory),
                hosts: [],
                controllerDirectories: $this->inspector->namedDirectories($directory, 'Controller'),
                entityDirectories: $this->inspector->namedDirectories($directory, 'Entity'),
            );
        }

        return $projects;
    }
}
