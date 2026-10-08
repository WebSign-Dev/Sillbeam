<?php

declare(strict_types=1);

namespace Sillbeam\Module;

use Sillbeam\Architecture\DiscoveryOptions;
use Sillbeam\Architecture\IdDeriver;
use Sillbeam\Architecture\SourceInspector;
use Sillbeam\Module\Attribute\SillbeamModule;
use Sillbeam\Project\ProjectDefinition;

final readonly class ModuleDiscovery
{
    public function __construct(private SourceInspector $inspector = new SourceInspector())
    {
    }

    /** @param array<class-string, ProjectDefinition> $projects
     * @return array<class-string, ModuleDefinition>
     */
    public function discover(DiscoveryOptions $options, array $projects): array
    {
        $modules = [];
        foreach ($this->inspector->descriptors($options, SillbeamModule::class) as $file => $class) {
            $directory = \dirname($file);
            foreach ($projects as $project) {
                if (str_starts_with($project->directory.'/', $directory.'/')) {
                    throw new \LogicException('Project cannot be inside module area: '.$project->class.' and '.$class);
                }
            }
            $owner = null;
            $inProjectRoot = false;
            foreach ($options->projectRoots as $root) {
                $inProjectRoot = $inProjectRoot || $root->contains($file);
            }
            foreach ($projects as $project) {
                if (str_starts_with($directory.'/', $project->directory.'/') && $directory !== $project->directory) {
                    $owner = $project->class;
                }
            }
            if ($inProjectRoot && null === $owner) {
                throw new \LogicException('Module "'.$class.'" has no owning project.');
            }
            $allowed = null !== $owner;
            foreach ($options->moduleRoots as $root) {
                $allowed = $allowed || $root->contains($file);
            }
            if (!$allowed) {
                throw new \LogicException('Module descriptor "'.$class.'" is outside the allowed module discovery boundaries.');
            }
            foreach ($modules as $known) {
                if ($directory === $known->directory || str_starts_with($directory.'/', $known->directory.'/') || str_starts_with($known->directory.'/', $directory.'/')) {
                    throw new \LogicException('Sillbeam descriptors have overlapping module areas: '.$class.' and '.$known->class);
                }
            }
            $metadata = $this->inspector->reflection($class, $file)->getAttributes(SillbeamModule::class)[0]->newInstance();
            $modules[$class] = new ModuleDefinition(
                id: $metadata->id ?? IdDeriver::fromClass($class, 'Module'),
                class: $class,
                description: $metadata->description,
                dependencies: $metadata->dependencies,
                enabled: true,
                directory: $directory,
                descriptorFile: $file,
                namespace: $options->namespaceForDirectory($directory),
                entityDirectories: $this->inspector->directories($directory, $options->entityPatterns, options: $options),
                controllerDirectories: $this->inspector->directories($directory, $options->controllerPatterns, options: $options),
                projectClass: $owner,
            );
        }

        return $modules;
    }
}
