<?php

declare(strict_types=1);

namespace Sillbeam\Architecture;

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
        $pruned = [];
        $parser = new SourceFileParser();
        foreach ($options->roots() as $root) {
            if (!is_dir($root->path)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root->path, \FilesystemIterator::SKIP_DOTS),
                static function (\SplFileInfo $file) use ($options, &$pruned): bool {
                    if (!$options->excluded($file->getPathname())) {
                        return true;
                    }
                    $pruned[$file->getPathname()] = $file->getPathname().($file->isDir() ? '/' : '');

                    return false;
                },
            ));
            foreach ($iterator as $entry) {
                if (!$entry->isFile() || 'php' !== $entry->getExtension() || isset($files[$entry->getPathname()])) {
                    continue;
                }
                $path = $entry->getPathname();
                try {
                    $files[$path] = $parser->parse($path);
                } catch (\PhpParser\Error $error) {
                    throw new \LogicException(\sprintf('Cannot parse Sillbeam source "%s": %s', $path, $error->getMessage()), 0, $error);
                }
            }
        }
        ksort($files);
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
                        throw new \LogicException(\sprintf('Descriptor "%s" must declare PSR-4 class "%s".', $file->path, $expected));
                    }
                    if (isset($descriptors[$file->path])) {
                        throw new \LogicException('Multiple Sillbeam descriptors in '.$file->path);
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
        if (!is_dir($root)) {
            return [];
        }
        $directories = [];
        $excluded = static function (\SplFileInfo $entry) use ($excludedRoots, $options): bool {
            foreach ($excludedRoots as $excludedRoot) {
                if ($entry->getPathname() === $excludedRoot || str_starts_with($entry->getPathname(), $excludedRoot.'/')) {
                    return false;
                }
            }

            return null === $options || !$options->excluded($entry->getPathname());
        };
        $iterator = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), $excluded), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $entry) {
            if ($entry->isDir() && PathMatcher::matches(substr($entry->getPathname(), \strlen($root) + 1), $patterns)) {
                $directories[] = $entry->getPathname();
            }
        }
        sort($directories);

        return $directories;
    }

    /** @param class-string $class
     * @return \ReflectionClass<object>
     */
    public function reflection(string $class, string $file): \ReflectionClass
    {
        if (!class_exists($class)) {
            require_once $file;
        }
        $reflection = new \ReflectionClass($class);
        if ($reflection->getFileName() !== $file) {
            throw new \LogicException(\sprintf('Descriptor class "%s" does not belong to "%s".', $class, $file));
        }

        return $reflection;
    }
}
