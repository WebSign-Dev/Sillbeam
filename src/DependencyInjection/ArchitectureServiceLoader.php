<?php

declare(strict_types=1);

namespace Nexum\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ArchitectureServiceLoader
{
    public function load(ContainerBuilder $container, string $directory, string $namespace, string $descriptorFile, bool $excludeNestedModules = false): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry->isFile() || 'php' !== $entry->getExtension()) {
                continue;
            }
            $file = $entry->getPathname();
            $relative = substr($file, \strlen($directory) + 1);
            if ($file === $descriptorFile
                || preg_match('#(^|/)(Entity|Dto)/#', $relative)
                || ($excludeNestedModules && str_starts_with($relative, 'Module/'))) {
                continue;
            }
            $class = rtrim($namespace, '\\').'\\'.str_replace('/', '\\', substr($relative, 0, -4));
            if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false) && !enum_exists($class, false)) {
                require_once $file;
            }
            if (!class_exists($class)) {
                continue;
            }
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait() || $reflection->isEnum()) {
                continue;
            }
            $container->autowire($class)->setAutoconfigured(true);
        }
    }
}
