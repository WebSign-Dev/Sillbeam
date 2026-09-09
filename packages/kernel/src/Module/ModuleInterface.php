<?php

declare(strict_types=1);

namespace Nexum\Kernel\Module;

use Nexum\Core\Contract\NexumComponentInterface;

interface ModuleInterface extends NexumComponentInterface
{
    public static function definition(): ModuleDefinition;
}
