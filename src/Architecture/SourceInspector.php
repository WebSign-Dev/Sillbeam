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

    /** @return list<string> */
    public function namedDirectories(string $root, string $name): array
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
            if ($entry->isDir() && $name === $entry->getFilename()) {
                $directories[] = $entry->getPathname();
            }
        }
        sort($directories);

        return $directories;
    }

    public function namespaceForDirectory(DiscoveryOptions $options, string $directory): string
    {
        $relative = trim(substr($directory, \strlen($options->root)), '/');

        return rtrim($options->namespace.str_replace('/', '\\', $relative), '\\');
    }
}
