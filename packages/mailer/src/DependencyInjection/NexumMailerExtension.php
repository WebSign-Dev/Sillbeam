<?php

declare(strict_types=1);

namespace Nexum\Mailer\DependencyInjection;

use Nexum\Mailer\Contract\MailerInterface;
use Nexum\Mailer\SymfonyMailer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class NexumMailerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $from = $configs[0]['default_from'] ?? null;
        $container->register(SymfonyMailer::class)->setArguments([new Reference('mailer'), \is_string($from) ? $from : null]);
        $container->setAlias(MailerInterface::class, SymfonyMailer::class);
    }
}
