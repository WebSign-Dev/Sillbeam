<?php

declare(strict_types=1);

namespace Nexum\Architecture;

final readonly class DiscoveryRoot
{
    /** @param list<string> $excludes */
    public function __construct(public string $path, public string $namespace, public array $excludes = [])
    {
    }

    public function contains(string $path): bool
    {
        return $path === $this->path || str_starts_with($path, $this->path.'/');
    }
}
