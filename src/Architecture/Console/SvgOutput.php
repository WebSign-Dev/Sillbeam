<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

/** @internal Publishes complete rendered SVG using same-directory atomic filesystem operations. */
final class SvgOutput
{
    /**
     * Resolves the real parent and rejects unsafe targets before expensive rendering.
     * Missing parent directories are created recursively. Symbolic links are rejected at validation, even with --force.
     *
     * @throws \RuntimeException for unavailable parents or protected targets
     */
    public function destination(string $path, bool $force): string
    {
        if ('' === trim($path) || preg_match('/[\x00-\x1f\x7f]/', $path)) {
            throw new \RuntimeException('Supply a nonempty SVG output path without control characters.');
        }
        $directory = \dirname($path);
        if (!@is_dir($directory) && !@mkdir($directory, 0777, true) && !@is_dir($directory)) {
            throw new \RuntimeException('Cannot create SVG output directory: '.$directory);
        }
        $parent = @realpath($directory);
        if (false === $parent || !@is_dir($parent) || !@is_writable($parent)) {
            throw new \RuntimeException('SVG output parent directory must be writable: '.\dirname($path));
        }
        $destination = $parent.\DIRECTORY_SEPARATOR.basename($path);
        $this->check($destination, $force);

        return $destination;
    }

    /**
     * Stages only successful renderer output. No-clobber publication uses an atomic
     * hard link; explicit replacement uses rename. Fails safely if unsupported.
     * Forced rename can replace a concurrently changed directory entry; it never
     * follows a symbolic link to overwrite the referenced file.
     *
     * @throws \RuntimeException when staging or publication fails
     */
    public function write(string $destination, string $svg, bool $force): void
    {
        $temporary = @tempnam(\dirname($destination), '.sillbeam-svg-');
        if (false === $temporary) {
            throw new \RuntimeException('Cannot create temporary SVG output.');
        }
        try {
            if (\strlen($svg) !== @file_put_contents($temporary, $svg)) {
                throw new \RuntimeException('Cannot write complete SVG output.');
            }
            $this->check($destination, $force);
            $published = $force ? @rename($temporary, $destination) : @link($temporary, $destination);
            if (!$published) {
                throw new \RuntimeException('Cannot publish SVG atomically. The destination may have appeared concurrently; use --force only for intentional replacement.');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function check(string $destination, bool $force): void
    {
        clearstatcache(true, $destination);
        if (@is_link($destination) || (@file_exists($destination) && !@is_file($destination))) {
            throw new \RuntimeException('SVG destination must be a regular file, never a directory or symbolic link: '.$destination);
        }
        if (!$force && @file_exists($destination)) {
            throw new \RuntimeException('SVG destination already exists. Use --force to replace it intentionally: '.$destination);
        }
    }
}
