<?php

declare(strict_types=1);

namespace Sillbeam\Core\Project;

/**
 * Immutable Project catalog entry with declared selections and its compiled module graph.
 *
 * Hosts are normalized exact hostnames; controller/entity directories describe discovery results.
 *
 * @api
 */
final readonly class ProjectDefinition
{
    /**
     * Creates a catalog snapshot without validating selections, paths or hosts.
     *
     * @param class-string       $class                 project descriptor class used as the catalog key
     * @param list<class-string> $declaredModules       explicit shared-module selections from the Project attribute
     * @param list<class-string> $modules               compiled graph including private modules and transitive dependencies
     * @param list<string>       $hosts                 normalized exact hostnames in configured order; empty for an unrestricted single Project
     * @param list<string>       $controllerDirectories project-owned controller directories, excluding private-module directories
     * @param list<string>       $entityDirectories     project-owned entity directories, excluding private-module directories
     * @param string             $id                    globally unique Project ID
     * @param string             $description           human-readable descriptor description
     * @param string             $directory             normalized Project directory
     * @param string             $descriptorFile        descriptor PHP source path
     * @param string             $namespace             project namespace without a trailing separator
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

    /**
     * Returns the first configured exact hostname, or null for a hostless Project.
     */
    public function canonicalHost(): ?string
    {
        return $this->hosts[0] ?? null;
    }
}
