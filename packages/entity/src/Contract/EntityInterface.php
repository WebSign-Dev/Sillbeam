<?php

declare(strict_types=1);

namespace Nexum\Entity\Contract;

use Nexum\Core\Contract\NexumComponentInterface;

/** Marker used by Nexum services to accept framework-managed entities only. */
interface EntityInterface extends NexumComponentInterface
{
}
