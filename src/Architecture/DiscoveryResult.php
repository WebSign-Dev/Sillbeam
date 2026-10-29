<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Sillbeam\Core\Module\ModuleDefinition;
use Sillbeam\Core\Project\ProjectDefinition;

/**
 * Immutable discovered catalogs and tracked source resources used by analysis results.
 *
 * Discovery alone does not establish valid dependencies or active runtime membership.
 *
 * @api
 */
final readonly class DiscoveryResult
{
    /**
     * Stores discovered catalogs before dependency/activation validation.
     *
     * @param array<class-string, ModuleDefinition>  $modules   discovered module catalog keyed by descriptor class, before activation compilation
     * @param array<class-string, ProjectDefinition> $projects  discovered Project catalog keyed by descriptor class
     * @param list<string>                           $resources source paths and root directories tracked for cache invalidation
     * @param string                                 $root      normalized application source directory
     * @param string                                 $namespace application PSR-4 namespace prefix
     */
    public function __construct(
        public string $root,
        public string $namespace,
        public array $modules,
        public array $projects,
        public array $resources,
    ) {
    }
}
