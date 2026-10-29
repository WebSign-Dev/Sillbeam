<?php

declare(strict_types=1);

namespace Sillbeam\Core\Project;

/**
 * Reads the compiled Project catalog and resolves exact request hosts.
 *
 * @api
 */
interface ProjectRegistryInterface
{
    /**
     * Returns all compiled Projects keyed by their descriptor class.
     *
     * @return array<class-string, ProjectDefinition>
     */
    public function all(): array;

    /**
     * Looks up a Project by its exact ID or descriptor class.
     *
     * @param class-string|string $idOrClass
     *
     * @throws \OutOfBoundsException if neither a class nor an ID matches
     */
    public function get(string $idOrClass): ProjectDefinition;

    /**
     * Resolves a hostname case-insensitively against exact configured hostnames.
     *
     * A single Project with no hosts accepts any hostname. Multiple Projects require
     * explicit assignments. Supply a hostname without scheme, port or path.
     *
     * @throws \OutOfBoundsException if no Project is assigned to the host
     */
    public function forHost(string $host): ProjectDefinition;
}
