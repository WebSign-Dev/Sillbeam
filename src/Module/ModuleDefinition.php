<?php

declare(strict_types=1);

namespace Sillbeam\Module;

final readonly class ModuleDefinition
{
    /**
     * @param class-string                                                              $class
     * @param list<class-string>                                                        $dependencies
     * @param list<string>                                                              $entityDirectories
     * @param list<string>                                                              $controllerDirectories
     * @param list<class-string>                                                        $observedDependencies
     * @param list<array{module: class-string, class: string, file: string, line: int}> $dependencyReferences
     * @param list<string>                                                              $activationReasons
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
        public array $observedDependencies = [],
        public array $dependencyReferences = [],
        public array $activationReasons = [],
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
