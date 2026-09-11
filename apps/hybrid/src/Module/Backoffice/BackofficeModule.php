<?php

declare(strict_types=1);

namespace HybridApp\Module\Backoffice;

use HybridApp\Module\Common\CommonModule;
use Nexum\Module\Attribute\NexumModule;

#[NexumModule(dependencies: [CommonModule::class])]
final class BackofficeModule
{
}
