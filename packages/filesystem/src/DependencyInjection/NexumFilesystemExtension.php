<?php

declare(strict_types=1);

namespace Nexum\Filesystem\DependencyInjection;

use Nexum\Filesystem\Adapter\LocalStorage;
use Nexum\Filesystem\Contract\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class NexumFilesystemExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $root = $configs[0]['root'] ?? '%kernel.project_dir%/storage/files';
        $limit = $configs[0]['max_read_bytes'] ?? 10_485_760;
        $container->register(LocalStorage::class)->setArguments([(string) $root, new Reference('filesystem'), (int) $limit]);
        $container->setAlias(StorageInterface::class, LocalStorage::class);
    }
}
