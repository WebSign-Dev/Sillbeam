<?php

declare(strict_types=1);

namespace Nexum\Api\Operation;

enum Operation: string
{
    case INDEX = 'list';
    case READ = 'read';
    case CREATE = 'create';
    case REPLACE = 'replace';
    case UPDATE = 'update';
    case DELETE = 'delete';

    /** @return list<self> */
    public static function all(): array
    {
        return [self::INDEX, self::READ, self::CREATE, self::REPLACE, self::UPDATE, self::DELETE];
    }

    public function permission(): string
    {
        return match ($this) {
            self::INDEX, self::READ => 'read',
            self::CREATE => 'create',
            self::REPLACE, self::UPDATE => 'update',
            self::DELETE => 'delete',
        };
    }
}
