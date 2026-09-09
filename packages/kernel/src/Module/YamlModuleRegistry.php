<?php

declare(strict_types=1);

namespace Nexum\Kernel\Module;

use Symfony\Component\Yaml\Yaml;

final class YamlModuleRegistry implements ModuleRegistryInterface
{
    /** @var array<string, ModuleDefinition>|null */
    private ?array $modules = null;

    public function __construct(private readonly string $configurationFile)
    {
    }

    public function all(): array
    {
        return $this->modules ??= $this->load();
    }

    public function enabled(): array
    {
        return array_filter(
            $this->all(),
            static fn (ModuleDefinition $module): bool => $module->enabled,
        );
    }

    public function has(string $id): bool
    {
        return isset($this->all()[$id]);
    }

    public function get(string $id): ModuleDefinition
    {
        return $this->all()[$id] ?? throw new \OutOfBoundsException(\sprintf('Unknown Nexum module "%s".', $id));
    }

    public function refresh(): void
    {
        $this->modules = null;
    }

    /** @return array<string, ModuleDefinition> */
    private function load(): array
    {
        if (!is_file($this->configurationFile)) {
            return [];
        }

        $configuration = Yaml::parseFile($this->configurationFile);

        if (!\is_array($configuration)) {
            throw new \RuntimeException(\sprintf('Module configuration "%s" must contain a YAML map.', $this->configurationFile));
        }

        if (1 !== ($configuration['version'] ?? 1)) {
            throw new \RuntimeException('Unsupported Nexum module configuration version.');
        }

        $entries = $configuration['modules'] ?? [];

        if (!\is_array($entries)) {
            throw new \RuntimeException('The Nexum "modules" configuration must be a map.');
        }

        $modules = [];

        foreach ($entries as $id => $entry) {
            if (!\is_string($id) || !\is_array($entry) || !\is_string($entry['class'] ?? null)) {
                throw new \RuntimeException(\sprintf('Invalid Nexum module entry "%s".', (string) $id));
            }

            $class = $entry['class'];

            if (!is_subclass_of($class, ModuleInterface::class)) {
                throw new \RuntimeException(\sprintf('Module class "%s" must implement %s.', $class, ModuleInterface::class));
            }

            $definition = $class::definition();

            if ($definition->id !== $id) {
                throw new \RuntimeException(\sprintf('Configured module id "%s" does not match attribute id "%s".', $id, $definition->id));
            }

            $modules[$id] = new ModuleDefinition(
                id: $definition->id,
                class: $definition->class,
                description: $definition->description,
                dependencies: $definition->dependencies,
                enabled: (bool) ($entry['enabled'] ?? true),
                directory: $definition->directory,
            );
        }

        foreach ($modules as $module) {
            foreach ($module->dependencies as $dependency) {
                if (!isset($modules[$dependency])) {
                    throw new \RuntimeException(\sprintf('Module "%s" depends on unknown module "%s".', $module->id, $dependency));
                }

                if ($module->enabled && !$modules[$dependency]->enabled) {
                    throw new \RuntimeException(\sprintf('Enabled module "%s" depends on disabled module "%s".', $module->id, $dependency));
                }
            }
        }

        return $modules;
    }
}
