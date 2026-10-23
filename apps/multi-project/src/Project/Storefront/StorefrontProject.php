<?php

declare(strict_types=1);

namespace ProjectApp\Project\Storefront;

use ProjectApp\Module\Catalog\CatalogModule;
use Sillbeam\Core\Project\Attribute\SillbeamProject;

#[SillbeamProject(modules: [CatalogModule::class])]
final class StorefrontProject
{
}
