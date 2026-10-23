<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

final class SourceInspector
{
    /** @var array<string, array<string, SourceFile>> */
    private array $indexes = [];

    /** @var array<string, list<string>> */
    private array $prunedPaths = [];

    /** @return array<string, SourceFile> */
    public function files(DiscoveryOptions $options): array
    {
        $key = serialize($options);
        if (isset($this->indexes[$key])) {
            return $this->indexes[$key];
        }
        $files = [];
        $paths = [];
        $pruned = [];
        $parser = new SourceFileParser();
        foreach ($options->roots() as $root) {
            if (!is_dir($root->path)) {
                continue;
            }
            // An overlapping root must not reenter a directory pruned by another scan.
            $boundary = $root->path;
            foreach ($options->roots() as $candidate) {
                if ($candidate->contains($root->path) && \strlen($candidate->path) < \strlen($boundary)) {
                    $boundary = $candidate->path;
                }
            }
            for ($path = $root->path;; $path = \dirname($path)) {
                if ($options->excluded($path)) {
                    $pruned[$path] = $path.'/';
                    continue 2;
                }
                if ($path === $boundary) {
                    break;
                }
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root->path, \FilesystemIterator::SKIP_DOTS),
                static function (\SplFileInfo $file) use ($options, &$pruned): bool {
                    if (!$options->excluded(FilesystemPath::normalize($file->getPathname()))) {
                        return true;
                    }
                    $pruned[FilesystemPath::normalize($file->getPathname())] = FilesystemPath::normalize($file->getPathname()).($file->isDir() ? '/' : '');

                    return false;
                },
            ));
            foreach ($iterator as $entry) {
                if (!$entry->isFile() || 'php' !== $entry->getExtension() || isset($paths[FilesystemPath::normalize($entry->getPathname())])) {
                    continue;
                }
                $paths[FilesystemPath::normalize($entry->getPathname())] = true;
            }
        }
        // Enumerate before parsing so fatal diagnostics also ignore filesystem/root order.
        ksort($paths);
        $failures = new ArchitectureFindings();
        $cause = null;
        foreach (array_keys($paths) as $path) {
            try {
                $files[$path] = $parser->parse($path);
            } catch (\PhpParser\Error $error) {
                $failures->add(new ArchitectureFinding('source.parse', \sprintf('Cannot parse Sillbeam source "%s": %s', $path, $error->getMessage()), ['file' => $path, 'line' => $error->getStartLine()], 'fatal'));
                $cause ??= $error;
            } catch (ArchitectureValidationException $error) {
                foreach ($error->result->findings as $finding) {
                    $failures->add($finding);
                }
                $cause ??= $error->getPrevious();
            }
        }
        (new ArchitectureAnalysisResult($failures->all(), cause: $cause))->throwIfInvalid();
        $this->prunedPaths[$key] = array_values($pruned);

        return $this->indexes[$key] = $files;
    }

    /** @return list<string> */
    public function excludedPaths(DiscoveryOptions $options): array
    {
        $this->files($options);

        return $this->prunedPaths[serialize($options)];
    }

    /** @return array<string, class-string> */
    public function descriptors(DiscoveryOptions $options, string $attribute): array
    {
        $descriptors = [];
        foreach ($this->files($options) as $file) {
            foreach ($file->descriptors as $class => $type) {
                if ($type === $attribute) {
                    $expected = $options->namespaceForDirectory(\dirname($file->path)).'\\'.basename($file->path, '.php');
                    if ($class !== $expected) {
                        throw ArchitectureValidationException::fatal('discovery.descriptor_class', \sprintf('Descriptor "%s" must declare PSR-4 class "%s".', $file->path, $expected), ['file' => $file->path, 'class' => $class]);
                    }
                    if (isset($descriptors[$file->path])) {
                        throw ArchitectureValidationException::fatal('discovery.multiple_descriptors', 'Multiple Sillbeam descriptors in '.$file->path, ['file' => $file->path]);
                    }
                    $descriptors[$file->path] = $class;
                }
            }
        }

        return $descriptors;
    }

    /**
     * @param list<string> $patterns
     * @param list<string> $excludedRoots
     *
     * @return list<string>
     */
    public function directories(string $root, array $patterns, array $excludedRoots = [], ?DiscoveryOptions $options = null): array
    {
        $root = FilesystemPath::normalize($root);
        $excludedRoots = array_map(FilesystemPath::normalize(...), $excludedRoots);
        if (!is_dir($root)) {
            return [];
        }
        $directories = [];
        $excluded = static function (\SplFileInfo $entry) use ($excludedRoots, $options): bool {
            foreach ($excludedRoots as $excludedRoot) {
                if (FilesystemPath::normalize($entry->getPathname()) === $excludedRoot || str_starts_with(FilesystemPath::normalize($entry->getPathname()), FilesystemPath::directoryPrefix($excludedRoot))) {
                    return false;
                }
            }

            return null === $options || !$options->excluded(FilesystemPath::normalize($entry->getPathname()));
        };
        $iterator = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), $excluded), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $entry) {
            if ($entry->isDir() && PathMatcher::matches(substr(FilesystemPath::normalize($entry->getPathname()), \strlen(FilesystemPath::directoryPrefix($root))), $patterns)) {
                $directories[] = FilesystemPath::normalize($entry->getPathname());
            }
        }
        sort($directories);

        return $directories;
    }

    /**
     * @template T of object
     *
     * @param class-string    $class
     * @param class-string<T> $attribute
     *
     * @return T
     */
    public function metadata(string $class, string $file, string $attribute): object
    {
        // Loading descriptors executes application code; its programming errors must propagate.
        $reflection = $this->reflection($class, $file);
        try {
            $attributes = $reflection->getAttributes($attribute);
            if ([] === $attributes) {
                throw ArchitectureValidationException::fatal('discovery.descriptor_metadata', 'Missing descriptor attribute on '.$class, ['class' => $class, 'file' => $file]);
            }

            return $attributes[0]->newInstance();
        } catch (\Error|\ReflectionException|\InvalidArgumentException $error) {
            throw ArchitectureValidationException::fatal('discovery.descriptor_metadata', \sprintf('Cannot read Sillbeam descriptor "%s": %s', $class, $error->getMessage()), ['class' => $class, 'file' => $file], $error);
        }
    }

    /** @param class-string $class
     * @return \ReflectionClass<object>
     */
    public function reflection(string $class, string $file): \ReflectionClass
    {
        $file = FilesystemPath::normalize($file);
        if (!class_exists($class)) {
            require_once $file;
        }
        $reflection = new \ReflectionClass($class);
        if (FilesystemPath::normalize((string) $reflection->getFileName()) !== $file) {
            throw ArchitectureValidationException::fatal('discovery.descriptor_file', \sprintf('Descriptor class "%s" does not belong to "%s".', $class, $file), ['file' => $file, 'class' => $class]);
        }

        return $reflection;
    }
}
