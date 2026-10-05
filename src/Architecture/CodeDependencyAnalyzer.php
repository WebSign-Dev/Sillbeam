<?php

declare(strict_types=1);

namespace Nexum\Architecture;

use Nexum\Module\ModuleDefinition;

final readonly class CodeDependencyAnalyzer
{
    public function __construct(private SourceInspector $inspector = new SourceInspector())
    {
    }

    /** @return array<class-string, list<array{module: class-string, class: string, file: string, line: int}>> */
    public function analyze(DiscoveryResult $discovery, CompiledArchitecture $architecture, DiscoveryOptions $options): array
    {
        $files = $this->inspector->files($options);
        $owners = [];
        $symbols = [];
        $references = array_fill_keys(array_keys($discovery->modules), []);
        $active = array_fill_keys($architecture->usedModules(), true);
        foreach ($files as $file) {
            $owner = $this->owner($file->path, $discovery->modules);
            if (null === $owner) {
                continue;
            }
            $owners[$file->path] = $owner;
            foreach ($file->classes as $class) {
                $key = strtolower($class);
                if (isset($symbols[$key])) {
                    throw new \LogicException('Duplicate class in Nexum source index: '.$class);
                }
                $symbols[$key] = $owner;
            }
        }
        foreach ($files as $file) {
            $source = $owners[$file->path] ?? null;
            if (null === $source) {
                continue;
            }
            $seen = [];
            foreach ($file->references as $reference) {
                $target = $symbols[strtolower($reference['class'])] ?? null;
                if (null === $target || $source->class === $target->class) {
                    continue;
                }
                $evidence = ['module' => $target->class, 'class' => $reference['class'], 'file' => $file->path, 'line' => $reference['line']];
                $key = serialize($evidence);
                if (!isset($seen[$key])) {
                    $references[$source->class][] = $evidence;
                    $seen[$key] = true;
                }
                if (!isset($active[$source->class])) {
                    continue;
                }
                $prefix = \sprintf('%s:%d: Module "%s" uses "%s" from module "%s": ', $file->path, $reference['line'], $source->id, $reference['class'], $target->id);
                if ($target->isPrivate() && (!$source->isPrivate() || $source->projectClass !== $target->projectClass)) {
                    throw new \LogicException($prefix.'access to this private module is forbidden.');
                }
                if (!isset($active[$target->class])) {
                    throw new \LogicException($prefix.'references an inactive module.');
                }
                if (!\in_array($target->class, $source->dependencies, true)) {
                    throw new \LogicException($prefix.'does not declare a direct dependency.');
                }
            }
        }

        return $references;
    }

    /** @param array<class-string, ModuleDefinition> $modules */
    private function owner(string $file, array $modules): ?ModuleDefinition
    {
        foreach ($modules as $module) {
            if (str_starts_with($file, $module->directory.'/')) {
                return $module;
            }
        }

        return null;
    }
}
