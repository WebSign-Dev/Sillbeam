<?php

declare(strict_types=1);

namespace ProjectApp\Project\Storefront;

use Nexum\Project\Attribute\NexumProject;
use ProjectApp\Module\Catalog\CatalogModule;

#[NexumProject(modules: [CatalogModule::class])]
final class StorefrontProject
{
}
