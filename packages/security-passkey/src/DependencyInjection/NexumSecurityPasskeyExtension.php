<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey\DependencyInjection;

use Nexum\Security\Passkey\Entity\PasskeyChallenge;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class NexumSecurityPasskeyExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $entityFile = (new \ReflectionClass(PasskeyChallenge::class))->getFileName();
        if (false === $entityFile) {
            throw new \LogicException('Unable to locate the Nexum Passkey mapping directory.');
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'NexumSecurityPasskey' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => \dirname($entityFile),
                        'prefix' => 'Nexum\\Security\\Passkey\\Entity',
                        'alias' => 'NexumSecurityPasskey',
                    ],
                ],
            ],
        ]);
        $container->prependExtensionConfig('framework', [
            'rate_limiter' => [
                'nexum_security_passkey_auth' => [
                    'policy' => 'sliding_window',
                    'limit' => 20,
                    'interval' => '15 minutes',
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('nexum.security_passkey.rp_id', $config['rp_id']);
        $container->setParameter('nexum.security_passkey.allowed_origins', $config['allowed_origins']);
        $container->setParameter('nexum.security_passkey.challenge_ttl', $config['challenge_ttl']);
        $container->setParameter('nexum.security_passkey.max_attempts', $config['max_attempts']);

        (new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config')))
            ->load('services.yaml');
    }
}
