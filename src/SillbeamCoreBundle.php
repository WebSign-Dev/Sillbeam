<?php

declare(strict_types=1);

namespace Sillbeam\Core;

use Sillbeam\Core\DependencyInjection\Compiler\ArchitectureValidationPass;
use Sillbeam\Core\DependencyInjection\Compiler\DoctrineMappingPass;
use Sillbeam\Core\DependencyInjection\SillbeamCoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers the Sillbeam extension and architecture/Doctrine compiler passes in Symfony.
 *
 * @api
 */
final class SillbeamCoreBundle extends Bundle
{
    /**
     * Lazily creates and caches the extension using the stable sillbeam configuration alias.
     */
    public function getContainerExtension(): SillbeamCoreExtension
    {
        // The public configuration alias stays sillbeam despite the Core bundle name.
        if (!$this->extension instanceof SillbeamCoreExtension) {
            $this->extension = new SillbeamCoreExtension();
        }

        return $this->extension;
    }

    /**
     * Registers validation and optional Doctrine mapping passes on the container.
     *
     * This mutates the container build pipeline; architecture analysis runs in the extension.
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ArchitectureValidationPass());
        $container->addCompilerPass(new DoctrineMappingPass());
    }
}
