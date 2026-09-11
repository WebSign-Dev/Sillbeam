<?php

declare(strict_types=1);

namespace Nexum\DependencyInjection\Compiler;

use Nexum\Module\ModuleRegistryInterface;
use Nexum\Project\ProjectRegistryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ArchitectureValidationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('nexum.module_registry') || !$container->hasDefinition('nexum.project_registry')) {
            throw new \LogicException('Nexum architecture registries were not compiled.');
        }
        if (!$container->hasAlias(ModuleRegistryInterface::class) || !$container->hasAlias(ProjectRegistryInterface::class)) {
            throw new \LogicException('Nexum architecture registry aliases are missing.');
        }
    }
}
