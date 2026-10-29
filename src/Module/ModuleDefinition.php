<?php

declare(strict_types=1);

namespace Sillbeam\Core\Module;

/**
 * Immutable module catalog entry with declared dependencies and observed source evidence.
 *
 * Enabled modules are not necessarily active: registry graph membership determines activity.
 * Observed dependencies never activate modules or replace explicit dependency declarations.
 *
 * @api
 */
final readonly class ModuleDefinition
{
    /**
     * Creates a catalog snapshot; does not validate or activate dependencies.
     *
     * @param class-string                                                              $class                 descriptor class used as the registry key
     * @param list<class-string>                                                        $dependencies          declared direct module dependencies; transitive activation is compiled separately
     * @param list<string>                                                              $entityDirectories     discovered normalized entity directories
     * @param list<string>                                                              $controllerDirectories discovered normalized controller directories
     * @param list<class-string>                                                        $observedDependencies  unique module targets observed in static source references
     * @param list<array{module: class-string, class: string, file: string, line: int}> $dependencyReferences  source evidence for observed module references
     * @param list<string>                                                              $activationReasons     reasons for graph membership, such as application, Project selection or dependency
     * @param string                                                                    $id                    ID unique within the shared scope or owning Project
     * @param string                                                                    $description           human-readable descriptor description
     * @param bool                                                                      $enabled               configured availability, independent of active graph membership
     * @param string                                                                    $directory             normalized module directory
     * @param string                                                                    $descriptorFile        descriptor PHP source path
     * @param string                                                                    $namespace             module namespace without a trailing separator
     * @param class-string|null                                                         $projectClass          owning Project descriptor, or null for a shared module
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

    /**
     * Returns the scoped configuration key: `shared:<id>` or `project:<projectId>:<id>`.
     *
     * The caller must supply the owning Project ID for a private module. The method
     * does not infer or validate ownership and does not throw for a missing ID.
     *
     * @param string|null $projectId owning Project ID; ignored for shared modules
     */
    public function key(?string $projectId = null): string
    {
        return null === $this->projectClass ? 'shared:'.$this->id : 'project:'.$projectId.':'.$this->id;
    }

    public function isPrivate(): bool
    {
        return null !== $this->projectClass;
    }
}
