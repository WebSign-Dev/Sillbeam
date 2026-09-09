<?php

declare(strict_types=1);

namespace Nexum\Security\Mfa\DependencyInjection;

use Nexum\Security\Mfa\Entity\MfaChallenge;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class NexumSecurityMfaExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $entityFile = (new \ReflectionClass(MfaChallenge::class))->getFileName();
        if (false === $entityFile) {
            throw new \LogicException('Unable to locate the Nexum MFA mapping directory.');
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'NexumSecurityMfa' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => \dirname($entityFile),
                        'prefix' => 'Nexum\\Security\\Mfa\\Entity',
                        'alias' => 'NexumSecurityMfa',
                    ],
                ],
            ],
        ]);
        $container->prependExtensionConfig('framework', [
            'rate_limiter' => [
                'nexum_security_mfa_verify' => [
                    'policy' => 'sliding_window',
                    'limit' => 10,
                    'interval' => '15 minutes',
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('nexum.security_mfa.issuer', $config['issuer']);
        $container->setParameter('nexum.security_mfa.challenge_ttl', $config['challenge_ttl']);
        $container->setParameter('nexum.security_mfa.max_attempts', $config['max_attempts']);

        (new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config')))
            ->load('services.yaml');
    }
}
