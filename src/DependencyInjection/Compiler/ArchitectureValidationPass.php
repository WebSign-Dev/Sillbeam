<?php

declare(strict_types=1);

namespace Sillbeam\DependencyInjection\Compiler;

use Sillbeam\Module\ModuleRegistryInterface;
use Sillbeam\Project\ProjectRegistryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ArchitectureValidationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('sillbeam.module_registry') || !$container->hasDefinition('sillbeam.project_registry')) {
            throw new \LogicException('Sillbeam architecture registries were not compiled.');
        }
        if (!$container->hasAlias(ModuleRegistryInterface::class) || !$container->hasAlias(ProjectRegistryInterface::class)) {
            throw new \LogicException('Sillbeam architecture registry aliases are missing.');
        }
    }
}
