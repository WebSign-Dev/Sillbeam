<?php

declare(strict_types=1);

namespace Sillbeam\Core\Project;

final readonly class ProjectDefinition
{
    /**
     * @param class-string       $class
     * @param list<class-string> $declaredModules
     * @param list<class-string> $modules
     * @param list<string>       $hosts
     * @param list<string>       $controllerDirectories
     * @param list<string>       $entityDirectories
     */
    public function __construct(
        public string $id,
        public string $class,
        public string $description,
        public array $declaredModules,
        public array $modules,
        public string $directory,
        public string $descriptorFile,
        public string $namespace,
        public array $hosts,
        public array $controllerDirectories,
        public array $entityDirectories = [],
    ) {
    }

    public function canonicalHost(): ?string
    {
        return $this->hosts[0] ?? null;
    }
}
