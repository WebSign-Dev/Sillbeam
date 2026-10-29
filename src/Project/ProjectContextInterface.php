<?php

declare(strict_types=1);

namespace Sillbeam\Core\Project;

/**
 * Resolves Project membership from the current request on each call.
 *
 * No Projects or no current request means no current Project. An unassigned host
 * with Projects configured is an error, rather than an absent context.
 *
 * @api
 */
interface ProjectContextInterface
{
    /**
     * Returns the current request's Project, or null without Projects or a current request.
     *
     * @throws \OutOfBoundsException if Projects exist but the current host is unassigned
     */
    public function current(): ?ProjectDefinition;

    /**
     * Returns the current Project ID, or null when there is no current Project.
     *
     * @throws \OutOfBoundsException if the current request host is unassigned
     */
    public function id(): ?string;

    /**
     * Tests the exact current Project ID; false when there is no current Project.
     *
     * @throws \OutOfBoundsException if the current request host is unassigned
     */
    public function is(string $id): bool;

    /**
     * Tests descriptor-class membership in the current Project graph.
     *
     * Falls back to the application graph when there is no current Project;
     * unknown module classes return false. No source files are scanned.
     *
     * @param class-string $module
     *
     * @throws \OutOfBoundsException if the current request host is unassigned
     */
    public function hasModule(string $module): bool;
}
