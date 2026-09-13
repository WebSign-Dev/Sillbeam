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
        foreach ($this->inspector->filesEndingWith($options->root, 'Project.php') as $file) {
            $relative = str_replace('\\', '/', substr($file, \strlen($options->root) + 1));
            $allowed = 1 === preg_match('#^Project/[^/]+/[^/]+Project\.php$#', $relative);
            $class = $this->inspector->classForFile($options, $file);
            $reflection = new \ReflectionClass($class);
            $attribute = $reflection->getAttributes(NexumProject::class)[0] ?? null;
            if (null === $attribute) {
                if ($allowed) {
                    throw new \LogicException(\sprintf('Project descriptor "%s" must declare #[NexumProject].', $class));
                }

                continue;
            }
            if (!$allowed) {
                throw new \LogicException(\sprintf('Project descriptor "%s" is outside the allowed src/Project/<Project>/*Project.php structure.', $class));
            }
            $metadata = $attribute->newInstance();
            $directory = \dirname($file);
            $excludedRoots = [$directory.'/Module', $directory.'/Project'];
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
                controllerDirectories: $this->inspector->namedDirectories($directory, 'Controller', $excludedRoots),
                entityDirectories: $this->inspector->namedDirectories($directory, 'Entity', $excludedRoots),
            );
        }

        return $projects;
    }
}
