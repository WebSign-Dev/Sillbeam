<?php

declare(strict_types=1);

namespace Nexum\Kernel\Module;

final readonly class ModuleDefinition
{
    /**
     * @param class-string<ModuleInterface> $class
     * @param list<string>                  $dependencies
     */
    public function __construct(
        public string $id,
        public string $class,
        public string $description,
        public array $dependencies,
        public bool $enabled,
        public string $directory,
    ) {
    }
}
