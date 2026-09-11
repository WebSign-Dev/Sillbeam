<?php

declare(strict_types=1);

namespace SingleProjectApp\Project\Portal;

use Nexum\Project\Attribute\NexumProject;
use SingleProjectApp\Module\Profile\ProfileModule;

#[NexumProject(modules: [ProfileModule::class])]
final class PortalProject
{
}
