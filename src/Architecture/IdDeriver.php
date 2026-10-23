<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

final class IdDeriver
{
    public static function fromClass(string $class, string $suffix, ?string $file = null): string
    {
        $shortName = substr($class, (int) strrpos($class, '\\') + 1);
        if (str_ends_with($shortName, $suffix)) {
            $shortName = substr($shortName, 0, -\strlen($suffix));
        }

        $id = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $shortName));
        if ('' === $id || !preg_match('/^[a-z][a-z0-9-]*$/', $id)) {
            throw ArchitectureValidationException::fatal('discovery.descriptor_id', \sprintf('Cannot derive a valid Sillbeam id from "%s".', $class), ['class' => $class, 'file' => $file]);
        }

        return $id;
    }
}
