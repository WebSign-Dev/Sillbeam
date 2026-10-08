<?php

declare(strict_types=1);

namespace ModulesOnlyApp\Module\Inventory;

use ModulesOnlyApp\Module\Foundation\FoundationModule;
use Sillbeam\Module\Attribute\SillbeamModule;

#[SillbeamModule(dependencies: [FoundationModule::class])]
final class InventoryModule
{
}
