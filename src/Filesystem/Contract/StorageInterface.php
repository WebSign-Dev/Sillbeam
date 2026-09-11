<?php

declare(strict_types=1);

namespace Nexum\Filesystem\Contract;

interface StorageInterface
{
    public function write(string $path, string $contents): void;

    public function read(string $path): string;

    public function delete(string $path): void;

    public function exists(string $path): bool;
}
