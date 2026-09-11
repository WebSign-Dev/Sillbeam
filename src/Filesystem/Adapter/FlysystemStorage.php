<?php

declare(strict_types=1);

namespace Nexum\Filesystem\Adapter;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Nexum\Filesystem\Contract\StorageInterface;
use Nexum\Filesystem\Exception\StorageException;
use Nexum\Filesystem\ValueObject\SafePath;

final readonly class FlysystemStorage implements StorageInterface
{
    public function __construct(private FilesystemOperator $filesystem, private int $maxReadBytes = 10_485_760)
    {
    }

    public function write(string $path, string $contents): void
    {
        try {
            $this->filesystem->write((string) new SafePath($path), $contents);
        } catch (FilesystemException $exception) {
            throw new StorageException('Unable to write the storage object.', previous: $exception);
        }
    }

    public function read(string $path): string
    {
        $safe = (string) new SafePath($path);
        try {
            if ($this->filesystem->fileSize($safe) > $this->maxReadBytes) {
                throw new StorageException('The storage object exceeds the configured read limit.');
            }

            return $this->filesystem->read($safe);
        } catch (FilesystemException $exception) {
            throw new StorageException('Unable to read the storage object.', previous: $exception);
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->filesystem->delete((string) new SafePath($path));
        } catch (FilesystemException $exception) {
            throw new StorageException('Unable to delete the storage object.', previous: $exception);
        }
    }

    public function exists(string $path): bool
    {
        try {
            return $this->filesystem->fileExists((string) new SafePath($path));
        } catch (FilesystemException $exception) {
            throw new StorageException('Unable to inspect the storage object.', previous: $exception);
        }
    }
}
