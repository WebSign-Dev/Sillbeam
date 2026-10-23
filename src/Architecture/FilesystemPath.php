<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

/** Shared representation for filesystem paths; namespace separators are unrelated. */
final class FilesystemPath
{
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return '/' === $path || preg_match('/^[a-zA-Z]:\/$/D', $path) ? $path : rtrim($path, '/');
    }

    public static function directoryPrefix(string $path): string
    {
        return rtrim(self::normalize($path), '/').'/';
    }
}
