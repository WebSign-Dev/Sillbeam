<?php

declare(strict_types=1);

namespace Sillbeam;

use Sillbeam\DependencyInjection\Compiler\ArchitectureValidationPass;
use Sillbeam\DependencyInjection\Compiler\DoctrineMappingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SillbeamBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ArchitectureValidationPass());
        $container->addCompilerPass(new DoctrineMappingPass());
    }
}
