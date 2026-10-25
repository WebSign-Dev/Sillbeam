# Architecture

The container build creates a shared source index. PHP-Parser resolves namespaces and imports, identifies attributes on descriptors and records static class references. Discovery assigns descriptors to their configured boundaries and checks that Module and Project directories are unique and do not overlap.

The compiler validates IDs, declared dependencies, cycles and host assignments, then determines the active graphs. Each visited node is expanded once per graph. The code analyzer checks direct references from active modules against direct declarations and the private Project boundary. Transitive declarations do not permit direct access.

This result feeds registries, native service imports, routing and optional Doctrine integration. Disabled or unassigned modules remain available for diagnostics without being imported automatically. Parent imports from Projects also exclude module areas. Explicit native Symfony/Doctrine imports remain an intentional escape hatch and can reactivate code.

Directory, File and FileExistence resources monitor source boundaries, including default directories that do not yet exist. As with Symfony, production caches are rebuilt during deployment. Activation values from environment variables are resolved at build time; changes require a cache rebuild.

A Project is an application surface: host routing and request context, with a shared container, event system and EntityManager. It provides neither isolation of global handlers nor a security boundary.

Explicit CLI checks and standalone Markdown reports use the same findings and
evidence-based diagnoses. Invalid candidates remain outside runtime registries.
See [architecture checking and reports](../guides/architecture-reporting.md) for
container-independent reporting, incomplete results and the optional informative
coupling appendix.
