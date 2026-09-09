<?php

declare(strict_types=1);

namespace Nexum\Filesystem\Adapter;

use Nexum\Filesystem\Contract\StorageInterface;
use Nexum\Filesystem\Exception\StorageException;
use Nexum\Filesystem\ValueObject\SafePath;
use Symfony\Component\Filesystem\Filesystem;

final readonly class LocalStorage implements StorageInterface
{
    public function __construct(private string $root, private Filesystem $filesystem, private int $maxReadBytes = 10_485_760)
    {
        if ('' === trim($root)) {
            throw new \InvalidArgumentException('The storage root must not be empty.');
        }
    }

    public function write(string $path, string $contents): void
    {
        $target = $this->target($path);
        try {
            $this->filesystem->mkdir(\dirname($target), 0700);
            $this->filesystem->dumpFile($target, $contents);
            @chmod($target, 0600);
        } catch (\Throwable $exception) {
            throw new StorageException('Unable to write the storage object.', previous: $exception);
        }
    }

    public function read(string $path): string
    {
        $target = $this->target($path);
        $size = @filesize($target);
        if (false === $size || $size > $this->maxReadBytes) {
            throw new StorageException('The storage object is missing or exceeds the configured read limit.');
        }
        $contents = @file_get_contents($target);

        return false === $contents ? throw new StorageException('Unable to read the storage object.') : $contents;
    }

    public function delete(string $path): void
    {
        $this->filesystem->remove($this->target($path));
    }

    public function exists(string $path): bool
    {
        return is_file($this->target($path));
    }

    private function target(string $path): string
    {
        return rtrim($this->root, '/').'/'.new SafePath($path);
    }
}
