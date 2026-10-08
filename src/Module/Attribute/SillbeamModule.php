<?php

declare(strict_types=1);

namespace Sillbeam\Module\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class SillbeamModule
{
    /** @param list<class-string> $dependencies */
    public function __construct(
        public ?string $id = null,
        public string $description = '',
        public array $dependencies = [],
    ) {
        if (null !== $id && !preg_match('/^[a-z][a-z0-9-]*$/', $id)) {
            throw new \InvalidArgumentException(\sprintf('Invalid Sillbeam module id "%s".', $id));
        }
    }
}
