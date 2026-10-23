<?php

declare(strict_types=1);

namespace Sillbeam\Core;

use Sillbeam\Core\DependencyInjection\Compiler\ArchitectureValidationPass;
use Sillbeam\Core\DependencyInjection\Compiler\DoctrineMappingPass;
use Sillbeam\Core\DependencyInjection\SillbeamCoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SillbeamCoreBundle extends Bundle
{
    public function getContainerExtension(): SillbeamCoreExtension
    {
        // The public configuration alias stays sillbeam despite the Core bundle name.
        if (!$this->extension instanceof SillbeamCoreExtension) {
            $this->extension = new SillbeamCoreExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ArchitectureValidationPass());
        $container->addCompilerPass(new DoctrineMappingPass());
    }
}
