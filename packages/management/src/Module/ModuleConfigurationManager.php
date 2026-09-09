<?php

declare(strict_types=1);

namespace Nexum\Management\Module;

use Nexum\Kernel\Module\ModuleRegistryInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Yaml\Yaml;

final readonly class ModuleConfigurationManager
{
    public function __construct(
        private ModuleRegistryInterface $modules,
        private LockFactory $locks,
        private string $configurationFile,
    ) {
    }

    public function apply(string $id, ModuleAction $action): void
    {
        if (!$this->modules->has($id)) {
            throw new \OutOfBoundsException(\sprintf('Unknown Nexum module "%s".', $id));
        }
        $lock = $this->locks->createLock('nexum-module-config', 30.0);
        if (!$lock->acquire(true)) {
            throw new \RuntimeException('Unable to acquire the module configuration lock.');
        }
        try {
            $configuration = $this->read();
            $entries = $configuration['modules'] ?? [];
            if (!\is_array($entries) || !isset($entries[$id]) || !\is_array($entries[$id])) {
                throw new \RuntimeException(\sprintf('Module "%s" is missing from its configuration.', $id));
            }
            if (ModuleAction::Enable === $action) {
                foreach ($this->modules->get($id)->dependencies as $dependency) {
                    if (!($entries[$dependency]['enabled'] ?? true)) {
                        throw new \DomainException(\sprintf('Module "%s" requires enabled module "%s".', $id, $dependency));
                    }
                }
                $entries[$id]['enabled'] = true;
            } else {
                foreach ($this->modules->all() as $module) {
                    if ($module->enabled && \in_array($id, $module->dependencies, true)) {
                        throw new \DomainException(\sprintf('Module "%s" is required by enabled module "%s".', $id, $module->id));
                    }
                }
                if (ModuleAction::Disable === $action) {
                    $entries[$id]['enabled'] = false;
                } else {
                    unset($entries[$id]);
                }
            }
            $configuration['modules'] = $entries;
            $this->write($configuration);
            $this->modules->refresh();
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, mixed> */
    private function read(): array
    {
        $data = Yaml::parseFile($this->configurationFile);

        return \is_array($data) ? $data : throw new \RuntimeException('The module configuration must contain a YAML map.');
    }

    /** @param array<string, mixed> $configuration */
    private function write(array $configuration): void
    {
        $directory = \dirname($this->configurationFile);
        $temporary = tempnam($directory, '.nexum-modules-');
        if (false === $temporary) {
            throw new \RuntimeException('Unable to create a temporary module configuration.');
        }
        try {
            $yaml = Yaml::dump($configuration, 6, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            if (false === file_put_contents($temporary, $yaml, \LOCK_EX) || !rename($temporary, $this->configurationFile)) {
                throw new \RuntimeException('Unable to atomically update the module configuration.');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
