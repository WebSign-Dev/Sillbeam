<?php

declare(strict_types=1);

namespace Nexum\User\DependencyInjection;

use Nexum\Security\Contract\SubjectUserInterface;
use Nexum\User\Entity\ApiAccessToken;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class NexumUserExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $container->getExtensionConfig($this->getAlias()));
        $entityFile = (new \ReflectionClass(ApiAccessToken::class))->getFileName();

        if (false === $entityFile) {
            throw new \LogicException('Unable to locate the Nexum user mapping directory.');
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'resolve_target_entities' => [
                    SubjectUserInterface::class => $config['entity_class'],
                ],
                'mappings' => [
                    'NexumUser' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => \dirname($entityFile),
                        'prefix' => 'Nexum\\User\\Entity',
                        'alias' => 'NexumUser',
                    ],
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('nexum.user.entity_class', $config['entity_class']);

        (new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config')))
            ->load('services.yaml');
    }
}
