<?php

declare(strict_types=1);

namespace HybridApp\Project\PublicSite;

use HybridApp\Module\Common\CommonModule;
use Sillbeam\Core\Project\Attribute\SillbeamProject;

#[SillbeamProject(id: 'public', modules: [CommonModule::class])]
final class PublicProject
{
}
