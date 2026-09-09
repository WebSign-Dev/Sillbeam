<?php

declare(strict_types=1);

namespace Nexum\Kernel\Module;

use Nexum\Core\Contract\NexumComponentInterface;

interface ModuleRegistryInterface extends NexumComponentInterface
{
    /** @return array<string, ModuleDefinition> */
    public function all(): array;

    /** @return array<string, ModuleDefinition> */
    public function enabled(): array;

    public function has(string $id): bool;

    public function get(string $id): ModuleDefinition;
}
