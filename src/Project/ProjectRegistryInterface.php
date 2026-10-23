<?php

declare(strict_types=1);

namespace Sillbeam\Core\Project;

interface ProjectRegistryInterface
{
    /** @return array<class-string, ProjectDefinition> */
    public function all(): array;

    /** @param class-string|string $idOrClass */
    public function get(string $idOrClass): ProjectDefinition;

    public function forHost(string $host): ProjectDefinition;
}
