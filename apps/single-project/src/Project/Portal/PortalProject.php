<?php

declare(strict_types=1);

namespace SingleProjectApp\Project\Portal;

use Sillbeam\Core\Project\Attribute\SillbeamProject;
use SingleProjectApp\Module\Profile\ProfileModule;

#[SillbeamProject(modules: [ProfileModule::class])]
final class PortalProject
{
}
