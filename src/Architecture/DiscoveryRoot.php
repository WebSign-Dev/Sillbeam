<?php

declare(strict_types=1);

namespace Sillbeam\Architecture;

final readonly class DiscoveryRoot
{
    public string $path;

    /** @param list<string> $excludes */
    public function __construct(string $path, public string $namespace, public array $excludes = [])
    {
        $this->path = FilesystemPath::normalize($path);
    }

    public function contains(string $path): bool
    {
        $path = FilesystemPath::normalize($path);

        return $path === $this->path || str_starts_with($path, FilesystemPath::directoryPrefix($this->path));
    }
}
