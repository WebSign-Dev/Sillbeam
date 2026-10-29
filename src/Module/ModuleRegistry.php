<?php

declare(strict_types=1);

namespace Sillbeam\Core\Module;

/**
 * Runtime implementation hydrated from the compiled container; consumers inject ModuleRegistryInterface.
 *
 * @internal
 */
final readonly class ModuleRegistry implements ModuleRegistryInterface
{
    /**
     * @param array<class-string, array<string, mixed>> $modules
     * @param list<class-string>                        $applicationModules
     * @param array<class-string, list<class-string>>   $projectModules
     */
    public function __construct(
        array $modules,
        private array $applicationModules,
        private array $projectModules,
    ) {
        $definitions = [];
        foreach ($modules as $class => $module) {
            $definitions[$class] = new ModuleDefinition(...$module);
        }
        $this->modules = $definitions;
    }

    /** @var array<class-string, ModuleDefinition> */
    private array $modules;

    public function all(): array
    {
        return $this->modules;
    }

    public function get(string $class): ModuleDefinition
    {
        return $this->modules[$class] ?? throw new \OutOfBoundsException(\sprintf('Unknown Sillbeam module class "%s".', $class));
    }

    /** @return array<class-string, ModuleDefinition> */
    public function forProject(string $project): array
    {
        if (!isset($this->projectModules[$project])) {
            throw new \OutOfBoundsException(\sprintf('Unknown Sillbeam project class "%s".', $project));
        }

        return $this->select($this->projectModules[$project]);
    }

    public function forApplication(): array
    {
        return $this->select($this->applicationModules);
    }

    public function active(): array
    {
        $classes = $this->applicationModules;
        foreach ($this->projectModules as $projectModules) {
            $classes = [...$classes, ...$projectModules];
        }

        return $this->select(array_values(array_unique($classes)));
    }

    /**
     * @param list<class-string> $classes
     *
     * @return array<class-string, ModuleDefinition>
     */
    private function select(array $classes): array
    {
        $selected = [];
        foreach ($classes as $class) {
            $selected[$class] = $this->modules[$class];
        }

        return $selected;
    }
}
