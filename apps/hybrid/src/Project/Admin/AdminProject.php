<?php

declare(strict_types=1);

namespace HybridApp\Project\Admin;

use HybridApp\Module\Backoffice\BackofficeModule;
use Sillbeam\Core\Project\Attribute\SillbeamProject;

#[SillbeamProject(modules: [BackofficeModule::class])]
final class AdminProject
{
}
