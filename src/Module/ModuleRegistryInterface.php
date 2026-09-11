<?php

declare(strict_types=1);

namespace Nexum\Module;

interface ModuleRegistryInterface
{
    /** @return array<class-string, ModuleDefinition> */
    public function all(): array;

    /** @param class-string $class */
    public function get(string $class): ModuleDefinition;

    /**
     * @param class-string $project
     *
     * @return array<class-string, ModuleDefinition>
     */
    public function forProject(string $project): array;

    /** @return array<class-string, ModuleDefinition> */
    public function forApplication(): array;

    /** @return array<class-string, ModuleDefinition> */
    public function active(): array;
}
