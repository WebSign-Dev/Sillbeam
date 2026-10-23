<?php

declare(strict_types=1);

namespace Sillbeam\Core\Project;

interface ProjectContextInterface
{
    public function current(): ?ProjectDefinition;

    public function id(): ?string;

    public function is(string $id): bool;

    /** @param class-string $module */
    public function hasModule(string $module): bool;
}
