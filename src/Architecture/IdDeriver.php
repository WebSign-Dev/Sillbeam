<?php

declare(strict_types=1);

namespace Nexum\Architecture;

final class IdDeriver
{
    public static function fromClass(string $class, string $suffix): string
    {
        $shortName = substr($class, (int) strrpos($class, '\\') + 1);
        if (str_ends_with($shortName, $suffix)) {
            $shortName = substr($shortName, 0, -\strlen($suffix));
        }

        $id = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $shortName));
        if ('' === $id || !preg_match('/^[a-z][a-z0-9-]*$/', $id)) {
            throw new \LogicException(\sprintf('Cannot derive a valid Nexum id from "%s".', $class));
        }

        return $id;
    }
}
