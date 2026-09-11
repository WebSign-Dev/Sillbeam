<?php

declare(strict_types=1);

namespace Nexum\Filesystem\ValueObject;

final readonly class SafePath implements \Stringable
{
    public string $value;

    public function __construct(string $path)
    {
        $path = str_replace('\\', '/', trim($path));
        if ('' === $path || str_starts_with($path, '/') || str_contains($path, "\0")) {
            throw new \InvalidArgumentException('Storage paths must be non-empty relative paths.');
        }
        $parts = explode('/', $path);
        if (array_any($parts, static fn (string $part): bool => '' === $part || '.' === $part || '..' === $part)) {
            throw new \InvalidArgumentException('Storage paths must not contain empty or traversal segments.');
        }
        $this->value = implode('/', $parts);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
