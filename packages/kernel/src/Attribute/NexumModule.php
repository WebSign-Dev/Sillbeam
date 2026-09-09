<?php

declare(strict_types=1);

namespace Nexum\Kernel\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class NexumModule
{
    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        public string $id,
        public string $description = '',
        public array $dependencies = [],
    ) {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $id)) {
            throw new \InvalidArgumentException(\sprintf('Invalid Nexum module id "%s".', $id));
        }
    }
}
