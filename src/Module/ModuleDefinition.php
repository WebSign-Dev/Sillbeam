<?php

declare(strict_types=1);

namespace Nexum\Module;

final readonly class ModuleDefinition
{
    /**
     * @param class-string       $class
     * @param list<class-string> $dependencies
     * @param list<string>       $entityDirectories
     * @param list<string>       $controllerDirectories
     */
    public function __construct(
        public string $id,
        public string $class,
        public string $description,
        public array $dependencies,
        public bool $enabled,
        public string $directory,
        public string $descriptorFile,
        public string $namespace,
        public array $entityDirectories,
        public array $controllerDirectories,
        public ?string $projectClass = null,
    ) {
    }

    public function key(?string $projectId = null): string
    {
        return null === $this->projectClass ? 'shared:'.$this->id : 'project:'.$projectId.':'.$this->id;
    }

    public function isPrivate(): bool
    {
        return null !== $this->projectClass;
    }
}
