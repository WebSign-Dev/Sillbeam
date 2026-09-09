<?php

declare(strict_types=1);

namespace Nexum\Security\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class NexumSecurityExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('nexum.security.access_token_ttl', $config['access_token_ttl']);
        $container->setParameter('nexum.security.role_permissions', $config['role_permissions']);

        (new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config')))
            ->load('services.yaml');
    }
}
