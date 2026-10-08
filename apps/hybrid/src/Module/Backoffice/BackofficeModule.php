<?php

declare(strict_types=1);

namespace HybridApp\Module\Backoffice;

use HybridApp\Module\Common\CommonModule;
use Sillbeam\Module\Attribute\SillbeamModule;

#[SillbeamModule(dependencies: [CommonModule::class])]
final class BackofficeModule
{
}
