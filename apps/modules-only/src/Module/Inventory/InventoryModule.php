<?php

declare(strict_types=1);

namespace ModulesOnlyApp\Module\Inventory;

use ModulesOnlyApp\Module\Foundation\FoundationModule;
use Nexum\Module\Attribute\NexumModule;

#[NexumModule(dependencies: [FoundationModule::class])]
final class InventoryModule
{
}
