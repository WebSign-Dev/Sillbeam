<?php

declare(strict_types=1);

namespace HybridApp\Project\Admin;

use HybridApp\Module\Backoffice\BackofficeModule;
use Nexum\Project\Attribute\NexumProject;

#[NexumProject(modules: [BackofficeModule::class])]
final class AdminProject
{
}
