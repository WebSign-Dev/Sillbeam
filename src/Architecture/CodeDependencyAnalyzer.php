<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Composer\Autoload\ClassLoader;
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
        $knownClasses = [];
        $references = array_fill_keys(array_keys($discovery->modules), []);
        $active = array_fill_keys($architecture->usedModules(), true);
        foreach ($files as $file) {
            foreach ($file->classes as $class) {
                $knownClasses[strtolower($class)] = true;
            }
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
        $resolved = [];
        foreach ($files as $file) {
            $source = $owners[$file->path] ?? null;
            $context = null === $source ? null : ['module' => $source->class, 'project' => $source->projectClass];
            $directory = $source?->directory;
            if (null === $context) {
                foreach ($discovery->projects as $project) {
                    if (str_starts_with($file->path, $project->directory.'/')) {
                        $context = ['project' => $project->class];
                        $directory = $project->directory;
                        break;
                    }
                }
            }
            if (null === $context || null === $directory || PathMatcher::matches(substr($file->path, \strlen($directory) + 1), ['Tests/**', '**/Tests/**', 'Fixtures/**', '**/Fixtures/**'])) {
                continue;
            }
            $missing = [];
            foreach ($file->references as $reference) {
                // PHP permits absent attribute classes until their metadata is instantiated.
                if ('attribute' === ($reference['kind'] ?? null)) {
                    continue;
                }
                $key = strtolower($reference['class']);
                $resolved[$key] ??= isset($knownClasses[$key]) || $this->resolvesClass($reference['class']);
                $locationKey = $key.':'.$reference['line'];
                if (!$resolved[$key] && !isset($missing[$locationKey])) {
                    $missing[$locationKey] = true;
                    $findings->add(new ArchitectureFinding('code.unknown_class', 'Cannot resolve referenced class "'.$reference['class'].'".', [
                        ...$context, 'class' => $reference['class'], 'file' => $file->path, 'line' => $reference['line'],
                    ]));
                }
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

    private function resolvesClass(string $class): bool
    {
        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return true;
        }
        // Resolve Composer paths without loading application/vendor code or booting services.
        foreach (spl_autoload_functions() as $autoload) {
            if (\is_array($autoload) && $autoload[0] instanceof ClassLoader) {
                $file = $autoload[0]->findFile($class);
                if (false !== $file && is_file($file)) {
                    return true;
                }
            }
        }

        return false;
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
