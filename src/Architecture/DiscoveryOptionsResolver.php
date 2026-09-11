<?php

declare(strict_types=1);

namespace Nexum\Architecture;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DiscoveryOptionsResolver
{
    /** @param array<array-key, array<string, mixed>> $configs */
    public function resolve(ContainerBuilder $container, array $configs): DiscoveryOptions
    {
        $raw = [];
        foreach ($configs as $config) {
            if (isset($config['discovery']) && \is_array($config['discovery'])) {
                $raw = array_replace($raw, $config['discovery']);
            }
        }

        $projectDir = $container->getParameter('kernel.project_dir');
        if (!\is_string($projectDir)) {
            throw new \LogicException('The kernel.project_dir parameter must be a string.');
        }
        $configuredRoot = $raw['root'] ?? $projectDir.'/src';
        if (!\is_string($configuredRoot)) {
            throw new \LogicException('nexum.discovery.root must be a string.');
        }
        $root = $this->resolvePath($configuredRoot, $projectDir);
        $configuredNamespace = $raw['namespace'] ?? null;
        if (null !== $configuredNamespace && !\is_string($configuredNamespace)) {
            throw new \LogicException('nexum.discovery.namespace must be a string.');
        }
        $namespace = null !== $configuredNamespace ? trim($configuredNamespace, '\\').'\\' : null;

        return new DiscoveryOptions($root, $namespace ?? $this->resolveNamespace($projectDir, $root));
    }

    private function resolvePath(string $path, string $projectDir): string
    {
        $path = str_replace('%kernel.project_dir%', $projectDir, $path);
        $resolved = realpath($path);

        return false === $resolved ? rtrim($path, '/') : rtrim($resolved, '/');
    }

    private function resolveNamespace(string $projectDir, string $root): string
    {
        $composerFile = $projectDir.'/composer.json';
        if (!is_file($composerFile)) {
            throw new \LogicException('Nexum discovery needs an application composer.json or an explicit discovery.namespace.');
        }

        $composer = json_decode((string) file_get_contents($composerFile), true, flags: \JSON_THROW_ON_ERROR);
        $mappings = [];
        foreach (['autoload', 'autoload-dev'] as $section) {
            foreach (($composer[$section]['psr-4'] ?? []) as $namespace => $paths) {
                foreach ((array) $paths as $path) {
                    $mappingPath = realpath($projectDir.'/'.rtrim((string) $path, '/'));
                    if (false === $mappingPath || ($root !== $mappingPath && !str_starts_with($root.'/', $mappingPath.'/'))) {
                        continue;
                    }
                    $mappings[] = ['namespace' => trim((string) $namespace, '\\').'\\', 'path' => $mappingPath];
                }
            }
        }

        usort($mappings, static fn (array $left, array $right): int => \strlen($right['path']) <=> \strlen($left['path']));
        if ([] === $mappings || (isset($mappings[1]) && \strlen($mappings[0]['path']) === \strlen($mappings[1]['path']))) {
            throw new \LogicException(\sprintf('Cannot determine an unambiguous PSR-4 namespace for Nexum discovery root "%s". Configure nexum.discovery.namespace.', $root));
        }

        $suffix = trim(substr($root, \strlen($mappings[0]['path'])), '/');

        return $mappings[0]['namespace'].('' === $suffix ? '' : str_replace('/', '\\', $suffix).'\\');
    }
}
