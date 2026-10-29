<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

/**
 * Normalized filesystem boundary associated with a PSR-4 namespace and local exclusions.
 *
 * @api
 */
final readonly class DiscoveryRoot
{
    public string $path;

    /**
     * Normalizes a boundary path without requiring that it exists.
     *
     * @param string       $path      directory boundary; trailing separators are normalized
     * @param string       $namespace PSR-4 prefix associated with the boundary
     * @param list<string> $excludes  root-relative exclusion patterns
     */
    public function __construct(string $path, public string $namespace, public array $excludes = [])
    {
        $this->path = FilesystemPath::normalize($path);
    }

    /**
     * Tests the root itself or a descendant using normalized directory boundaries.
     *
     * Does not resolve symbolic links or require filesystem existence.
     */
    public function contains(string $path): bool
    {
        $path = FilesystemPath::normalize($path);

        return $path === $this->path || str_starts_with($path, FilesystemPath::directoryPrefix($this->path));
    }
}
