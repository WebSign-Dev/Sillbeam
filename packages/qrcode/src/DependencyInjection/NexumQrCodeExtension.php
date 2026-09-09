<?php

declare(strict_types=1);

namespace Nexum\QrCode\DependencyInjection;

use Nexum\QrCode\Contract\QrCodeGeneratorInterface;
use Nexum\QrCode\Renderer\EndroidQrCodeGenerator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class NexumQrCodeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->autowire(EndroidQrCodeGenerator::class)->setPublic(false);
        $container->setAlias(QrCodeGeneratorInterface::class, EndroidQrCodeGenerator::class);
    }
}
