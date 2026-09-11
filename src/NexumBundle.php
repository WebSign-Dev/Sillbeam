<?php

declare(strict_types=1);

namespace Nexum;

use Nexum\DependencyInjection\Compiler\ArchitectureValidationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NexumBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ArchitectureValidationPass());
    }
}
