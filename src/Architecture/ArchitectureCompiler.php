<?php

declare(strict_types=1);

namespace Nexum\Architecture;

use Nexum\Module\ModuleDefinition;
use Nexum\Project\ProjectDefinition;

final class ArchitectureCompiler
{
    /** @param array<string, mixed> $config */
    public function compile(DiscoveryResult $discovery, array $config): CompiledArchitecture
    {
        $modules = $this->applyModuleOverrides($discovery->modules, $discovery->projects, $config['modules'] ?? []);
        $this->validateIdentities($modules, $discovery->projects);
        $this->validateDependencies($modules);
        $this->validateCycles($modules);

        if ([] === $discovery->projects) {
            $active = [];
            $visited = [];
            foreach ($modules as $module) {
                if (!$module->isPrivate() && $module->enabled) {
                    $active = [...$active, ...$this->expand($module->class, $modules, true, null, $visited)];
                }
            }

            return new CompiledArchitecture($modules, [], array_values(array_unique($active)), []);
        }

        $projects = $this->configureProjects($discovery->projects, $config['projects'] ?? []);
        $projectModules = [];
        foreach ($projects as $class => $project) {
            $active = [];
            $visited = [];
            foreach ($project->declaredModules as $moduleClass) {
                $active = [...$active, ...$this->expand($moduleClass, $modules, true, $class, $visited)];
            }
            foreach ($modules as $module) {
                if ($class === $module->projectClass && $module->enabled) {
                    $active = [...$active, ...$this->expand($module->class, $modules, true, $class, $visited)];
                }
            }
            $active = array_values(array_unique($active));
            $projectModules[$class] = $active;
            $projects[$class] = new ProjectDefinition(
                $project->id,
                $project->class,
                $project->description,
                $project->declaredModules,
                $active,
                $project->directory,
                $project->descriptorFile,
                $project->namespace,
                $project->hosts,
                $project->controllerDirectories,
                $project->entityDirectories,
            );
        }
        $this->validateHosts($projects);

        return new CompiledArchitecture($modules, $projects, [], $projectModules);
    }

    /**
     * @param array<class-string, ModuleDefinition>  $modules
     * @param array<class-string, ProjectDefinition> $projects
     * @param array<string, mixed>                   $overrides
     *
     * @return array<class-string, ModuleDefinition>
     */
    private function applyModuleOverrides(array $modules, array $projects, array $overrides): array
    {
        $keys = [];
        foreach ($modules as $class => $module) {
            if (null === $module->projectClass) {
                $keys[$module->id] = $class;
                $keys['shared:'.$module->id] = $class;
                continue;
            }
            $project = $projects[$module->projectClass] ?? throw new \LogicException(\sprintf('Private module "%s" has no owning project.', $class));
            $keys['project:'.$project->id.':'.$module->id] = $class;
        }
        foreach (array_keys($overrides) as $key) {
            if (!isset($keys[$key])) {
                throw new \LogicException(\sprintf('Module override "%s" does not match a discovered module.', $key));
            }
        }

        foreach ($modules as $class => $module) {
            $projectId = null === $module->projectClass ? null : $projects[$module->projectClass]->id;
            $logicalKey = $module->key($projectId);
            $override = $overrides[$logicalKey] ?? (null === $module->projectClass ? ($overrides[$module->id] ?? []) : []);
            $modules[$class] = new ModuleDefinition(
                $module->id,
                $module->class,
                $module->description,
                $module->dependencies,
                (bool) ($override['enabled'] ?? true),
                $module->directory,
                $module->descriptorFile,
                $module->namespace,
                $module->entityDirectories,
                $module->controllerDirectories,
                $module->projectClass,
            );
        }

        return $modules;
    }

    /**
     * @param array<class-string, ModuleDefinition>  $modules
     * @param array<class-string, ProjectDefinition> $projects
     */
    private function validateIdentities(array $modules, array $projects): void
    {
        $projectIds = [];
        foreach ($projects as $project) {
            if (isset($projectIds[$project->id])) {
                throw new \LogicException(\sprintf('Duplicate Nexum project id "%s".', $project->id));
            }
            $projectIds[$project->id] = true;
        }

        $keys = [];
        foreach ($modules as $module) {
            $projectId = null === $module->projectClass ? null : ($projects[$module->projectClass]->id ?? null);
            if (null !== $module->projectClass && null === $projectId) {
                throw new \LogicException(\sprintf('Private module "%s" has no owning project.', $module->class));
            }
            $key = $module->key($projectId);
            if (isset($keys[$key])) {
                throw new \LogicException(\sprintf('Duplicate Nexum module key "%s".', $key));
            }
            $keys[$key] = true;
        }
    }

    /** @param array<class-string, ModuleDefinition> $modules */
    private function validateDependencies(array $modules): void
    {
        foreach ($modules as $module) {
            foreach ($module->dependencies as $dependencyClass) {
                $dependency = $modules[$dependencyClass] ?? throw new \LogicException(\sprintf('Module "%s" depends on unknown module class "%s".', $module->class, $dependencyClass));
                if (!$module->isPrivate() && $dependency->isPrivate()) {
                    throw new \LogicException(\sprintf('Shared module "%s" cannot depend on private module "%s".', $module->class, $dependency->class));
                }
                if ($module->isPrivate() && $dependency->isPrivate() && $module->projectClass !== $dependency->projectClass) {
                    throw new \LogicException(\sprintf('Private module "%s" cannot depend on private module "%s" from another project.', $module->class, $dependency->class));
                }
            }
        }
    }

    /** @param array<class-string, ModuleDefinition> $modules */
    private function validateCycles(array $modules): void
    {
        $visiting = [];
        $visited = [];
        $visit = static function (string $class) use (&$visit, &$visiting, &$visited, $modules): void {
            if (isset($visiting[$class])) {
                throw new \LogicException(\sprintf('Circular Nexum module dependency detected at "%s".', $class));
            }
            if (isset($visited[$class])) {
                return;
            }
            $visiting[$class] = true;
            foreach ($modules[$class]->dependencies as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$class]);
            $visited[$class] = true;
        };
        foreach (array_keys($modules) as $class) {
            $visit($class);
        }
    }

    /**
     * @param class-string                          $class
     * @param array<class-string, ModuleDefinition> $modules
     * @param array<class-string, true>             $visited
     *
     * @return list<class-string>
     */
    private function expand(string $class, array $modules, bool $requireEnabled, ?string $projectClass = null, array &$visited = []): array
    {
        $module = $modules[$class] ?? throw new \LogicException(\sprintf('Project references unknown module class "%s".', $class));
        if ($requireEnabled && !$module->enabled) {
            throw new \LogicException(\sprintf('Active graph requires disabled module "%s".', $module->id));
        }
        if ($module->isPrivate() && $module->projectClass !== $projectClass) {
            throw new \LogicException(\sprintf('Project cannot use private module "%s" from another project.', $module->class));
        }
        if (isset($visited[$class])) {
            return [];
        }
        $visited[$class] = true;
        $expanded = [];
        foreach ($module->dependencies as $dependency) {
            $expanded = [...$expanded, ...$this->expand($dependency, $modules, $requireEnabled, $projectClass, $visited)];
        }
        $expanded[] = $class;

        return $expanded;
    }

    /**
     * @param array<class-string, ProjectDefinition> $projects
     * @param array<string, mixed>                   $config
     *
     * @return array<class-string, ProjectDefinition>
     */
    private function configureProjects(array $projects, array $config): array
    {
        $knownIds = array_map(static fn (ProjectDefinition $project): string => $project->id, $projects);
        foreach (array_keys($config) as $id) {
            if (!\in_array($id, $knownIds, true)) {
                throw new \LogicException(\sprintf('Project configuration "%s" does not match a discovered project.', $id));
            }
        }
        foreach ($projects as $class => $project) {
            $hosts = array_map(static fn (mixed $host): string => strtolower(trim((string) $host)), $config[$project->id]['hosts'] ?? []);
            foreach ($hosts as $host) {
                if ('' === $host || str_contains($host, '://') || str_contains($host, '*') || str_contains($host, '/') || str_contains($host, ':')) {
                    throw new \LogicException(\sprintf('Project "%s" contains invalid exact host "%s".', $project->id, $host));
                }
            }
            $projects[$class] = new ProjectDefinition(
                $project->id,
                $project->class,
                $project->description,
                $project->declaredModules,
                [],
                $project->directory,
                $project->descriptorFile,
                $project->namespace,
                array_values(array_unique($hosts)),
                $project->controllerDirectories,
                $project->entityDirectories,
            );
        }

        return $projects;
    }

    /** @param array<class-string, ProjectDefinition> $projects */
    private function validateHosts(array $projects): void
    {
        $hosts = [];
        foreach ($projects as $project) {
            if (\count($projects) > 1 && [] === $project->hosts) {
                throw new \LogicException(\sprintf('Project "%s" requires at least one host when multiple projects exist.', $project->id));
            }
            foreach ($project->hosts as $host) {
                if (isset($hosts[$host])) {
                    throw new \LogicException(\sprintf('Host "%s" is assigned to multiple Nexum projects.', $host));
                }
                $hosts[$host] = $project->id;
            }
        }
    }
}
