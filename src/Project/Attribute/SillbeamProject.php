<?php

declare(strict_types=1);

namespace Sillbeam\Project\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class SillbeamProject
{
    /** @param list<class-string> $modules */
    public function __construct(
        public ?string $id = null,
        public string $description = '',
        public array $modules = [],
    ) {
        if (null !== $id && !preg_match('/^[a-z][a-z0-9-]*$/D', $id)) {
            throw new \InvalidArgumentException(\sprintf('Invalid Sillbeam project id "%s".', $id));
        }
    }
}
