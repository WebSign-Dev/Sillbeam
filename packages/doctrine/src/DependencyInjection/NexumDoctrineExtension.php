<?php

declare(strict_types=1);

namespace Nexum\Doctrine\DependencyInjection;

use Nexum\Doctrine\Filter\SoftDeleteFilter;
use Nexum\Entity\Entity\AbstractEntity;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class NexumDoctrineExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $entityFile = (new \ReflectionClass(AbstractEntity::class))->getFileName();

        if (false === $entityFile) {
            throw new \LogicException('Unable to locate the Nexum entity mapping directory.');
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'NexumEntity' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => \dirname($entityFile),
                        'prefix' => 'Nexum\\Entity\\Entity',
                        'alias' => 'NexumEntity',
                    ],
                ],
                'filters' => [
                    'nexum_soft_delete' => [
                        'class' => SoftDeleteFilter::class,
                        'enabled' => true,
                    ],
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        (new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config')))
            ->load('services.yaml');
    }
}
