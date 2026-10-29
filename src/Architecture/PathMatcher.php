<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

/**
 * Matches normalized paths against anchored glob-like patterns, including recursive double stars.
 *
 * @internal
 */
final class PathMatcher
{
    /** @param list<string> $patterns */
    public static function matches(string $path, array $patterns): bool
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        foreach ($patterns as $pattern) {
            $pattern = trim(str_replace('\\', '/', $pattern), '/');
            $regex = preg_quote($pattern, '#');
            $regex = str_replace(['\*\*/', '/\*\*', '\*\*', '\*', '\?'], ['(?:.*/)?', '(?:/.*)?', '.*', '[^/]*', '[^/]'], $regex);
            if (preg_match('#^'.$regex.'$#D', $path)) {
                return true;
            }
        }

        return false;
    }
}
