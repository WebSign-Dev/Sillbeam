<?php

declare(strict_types=1);

namespace HybridApp\Project\PublicSite;

use HybridApp\Module\Common\CommonModule;
use Nexum\Project\Attribute\NexumProject;

#[NexumProject(id: 'public', modules: [CommonModule::class])]
final class PublicProject
{
}
