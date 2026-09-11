<?php

declare(strict_types=1);

namespace Nexum\Module;

use Nexum\Architecture\DiscoveryOptions;
use Nexum\Architecture\IdDeriver;
use Nexum\Architecture\SourceInspector;
use Nexum\Module\Attribute\NexumModule;
use Nexum\Project\ProjectDefinition;

final readonly class ModuleDiscovery
{
    public function __construct(private SourceInspector $inspector = new SourceInspector())
    {
    }

    /**
     * @param array<class-string, ProjectDefinition> $projects
     *
     * @return array<class-string, ModuleDefinition>
     */
    public function discover(DiscoveryOptions $options, array $projects): array
    {
        $files = glob($options->root.'/Module/*/*Module.php') ?: [];
        foreach (glob($options->root.'/Project/*/Module/*/*Module.php') ?: [] as $file) {
            $files[] = $file;
        }
        sort($files);

        $modules = [];
        foreach ($files as $file) {
            $class = $this->inspector->classForFile($options, $file);
            $reflection = new \ReflectionClass($class);
            $attribute = $reflection->getAttributes(NexumModule::class)[0] ?? null;
            if (null === $attribute) {
                throw new \LogicException(\sprintf('Module descriptor "%s" must declare #[NexumModule].', $class));
            }
            $metadata = $attribute->newInstance();
            $directory = \dirname($file);
            $projectClass = $this->owningProject($directory, $projects);
            $modules[$class] = new ModuleDefinition(
                id: $metadata->id ?? IdDeriver::fromClass($class, 'Module'),
                class: $class,
                description: $metadata->description,
                dependencies: $metadata->dependencies,
                enabled: true,
                directory: $directory,
                descriptorFile: $file,
                namespace: $this->inspector->namespaceForDirectory($options, $directory),
                entityDirectories: $this->inspector->namedDirectories($directory, 'Entity'),
                controllerDirectories: $this->inspector->namedDirectories($directory, 'Controller'),
                projectClass: $projectClass,
            );
        }

        return $modules;
    }

    /** @param array<class-string, ProjectDefinition> $projects */
    private function owningProject(string $directory, array $projects): ?string
    {
        foreach ($projects as $project) {
            if (str_starts_with($directory.'/', $project->directory.'/Module/')) {
                return $project->class;
            }
        }

        return null;
    }
}
