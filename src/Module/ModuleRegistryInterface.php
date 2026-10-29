<?php

declare(strict_types=1);

namespace Sillbeam\Core\Module;

/**
 * Reads the compiled module catalog and activation graphs without scanning source files.
 *
 * @api
 */
interface ModuleRegistryInterface
{
    /**
     * Returns the complete discovered catalog, including disabled and inactive modules.
     *
     * @return array<class-string, ModuleDefinition>
     */
    public function all(): array;

    /**
     * Looks up a descriptor class, independently of activation.
     *
     * @param class-string $class
     *
     * @throws \OutOfBoundsException if the descriptor class is unknown
     */
    public function get(string $class): ModuleDefinition;

    /**
     * Returns the compiled dependency closure for a Project descriptor class.
     *
     * Includes selected shared modules and enabled private modules. Accepts classes, not IDs.
     *
     * @param class-string $project
     *
     * @return array<class-string, ModuleDefinition>
     *
     * @throws \OutOfBoundsException if the Project class is unknown
     */
    public function forProject(string $project): array;

    /**
     * Returns the active application graph; empty when Projects are configured.
     *
     * @return array<class-string, ModuleDefinition>
     */
    public function forApplication(): array;

    /**
     * Returns the union of application and Project graphs, keyed uniquely by descriptor class.
     *
     * @return array<class-string, ModuleDefinition>
     */
    public function active(): array;
}
