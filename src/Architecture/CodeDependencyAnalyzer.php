<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Sillbeam\Core\Module\ModuleDefinition;

final readonly class CodeDependencyAnalyzer
{
    public function __construct(private SourceInspector $inspector = new SourceInspector())
    {
    }

    /** @return array<class-string, list<array{module: class-string, class: string, file: string, line: int}>> */
    public function analyze(DiscoveryResult $discovery, CompiledArchitecture $architecture, DiscoveryOptions $options): array
    {
        $findings = new ArchitectureFindings();
        $references = $this->collect($discovery, $architecture, $options, $findings);
        (new ArchitectureAnalysisResult($findings->all(), references: $references))->throwIfInvalid();

        return $references;
    }

    /**
     * @internal collect without publishing an invalid graph
     *
     * @return array<class-string, list<array{module: class-string, class: string, file: string, line: int}>>
     */
    public function collect(DiscoveryResult $discovery, CompiledArchitecture $architecture, DiscoveryOptions $options, ArchitectureFindings $findings): array
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
                    throw ArchitectureValidationException::fatal('source.duplicate_class', 'Duplicate class in Sillbeam source index: '.$class, ['class' => $class, 'file' => $file->path, 'module' => $owner->class]);
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
                $key = serialize([...$evidence, 'class' => strtolower($reference['class'])]);
                if (isset($seen[$key])) {
                    continue;
                }
                $references[$source->class][] = $evidence;
                $seen[$key] = true;
                if (!isset($active[$source->class])) {
                    continue;
                }
                $prefix = \sprintf('%s:%d: Module "%s" uses "%s" from module "%s": ', $file->path, $reference['line'], $source->id, $reference['class'], $target->id);
                $context = ['module' => $source->class, 'project' => $source->projectClass, 'target' => $target->class, 'class' => $reference['class'], 'file' => $file->path, 'line' => $reference['line']];
                if ($target->isPrivate() && (!$source->isPrivate() || $source->projectClass !== $target->projectClass)) {
                    $findings->add(new ArchitectureFinding('code.private', $prefix.'access to this private module is forbidden.', $context));
                } elseif (!isset($active[$target->class])) {
                    $findings->add(new ArchitectureFinding('code.inactive', $prefix.'references an inactive module.', $context));
                } elseif (!\in_array($target->class, $source->dependencies, true)) {
                    $findings->add(new ArchitectureFinding('code.undeclared_dependency', $prefix.'does not declare a direct dependency.', $context));
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
