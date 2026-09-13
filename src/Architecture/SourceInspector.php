<?php

declare(strict_types=1);

namespace Nexum\Architecture;

final class SourceInspector
{
    /** @return class-string */
    public function classForFile(DiscoveryOptions $options, string $file): string
    {
        $relative = substr($file, \strlen($options->root) + 1, -4);
        $class = $options->namespace.str_replace('/', '\\', $relative);
        if (!class_exists($class, false)) {
            require_once $file;
        }
        if (!class_exists($class, false)) {
            throw new \LogicException(\sprintf('Descriptor "%s" must declare autoloadable class "%s".', $file, $class));
        }

        return $class;
    }

    /**
     * @param list<string> $excludedRoots
     *
     * @return list<string>
     */
    public function namedDirectories(string $root, string $name, array $excludedRoots = []): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $directories = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir() && $name === $entry->getFilename() && !$this->isWithin($entry->getPathname(), $excludedRoots)) {
                $directories[] = $entry->getPathname();
            }
        }
        sort($directories);

        return $directories;
    }

    /** @return list<string> */
    public function filesEndingWith(string $root, string $suffix): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if ($entry->isFile() && str_ends_with($entry->getFilename(), $suffix)) {
                $files[] = $entry->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    public function namespaceForDirectory(DiscoveryOptions $options, string $directory): string
    {
        $relative = trim(substr($directory, \strlen($options->root)), '/');

        return rtrim($options->namespace.str_replace('/', '\\', $relative), '\\');
    }

    /** @param list<string> $roots */
    private function isWithin(string $path, array $roots): bool
    {
        foreach ($roots as $root) {
            if ($path === $root || str_starts_with($path.'/', rtrim($root, '/').'/')) {
                return true;
            }
        }

        return false;
    }
}
