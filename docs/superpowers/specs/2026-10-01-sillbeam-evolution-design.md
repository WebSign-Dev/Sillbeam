# Sillbeam evolution: design

The approved review in `docs/development/sillbeam-evolution-review-2026-10-01.md` and the user's supplied vision are the scope. Sillbeam becomes an optional Symfony module architecture layer. PHP >=8.4 and Symfony ^7.4 || ^8.0 remain supported. Projects share one container and runtime. Strata is not implemented or required by this work.

## Build and integrations

Normalize Sillbeam configuration once before prepend. Discover descriptors, validate identities and ownership, compile activation/dependencies, inspect actual static references, and reuse the resulting architecture for Doctrine, DI, routing and diagnostics. Preserve all discovered definitions for diagnosis; integrations consume active definitions only. Automatic private roots skip disabled modules; explicit requirements on disabled modules fail. Graph traversal visits each module once per scope.

Service discovery delegates to Symfony's native file loader including environment attributes, excludes and aliases. Normal application configuration overrides Sillbeam defaults through Symfony's usual extension merging. Doctrine is automatic when DoctrineBundle is present, explicitly disableable, and contributes attribute mappings only. No Sillbeam entity bases, filters or stores.

## Discovery

Keep discovery.root and discovery.namespace as the source envelope/PSR-4 defaults. Add discovery.modules and discovery.projects as lists of path/namespace/excludes records. Default boundaries are src/Module and src/Project; project-owned modules are discovered inside their project. Relative paths resolve against kernel.project_dir. All roots are canonical and namespaces validated. Global excludes and root-local excludes prune discovery. Attributed descriptors outside allowed boundaries in the source envelope fail; unrelated classes, interfaces, traits and enums never become descriptors by filename alone.

Descriptors may have arbitrary PSR-4 filenames. A source area has one owner: reject duplicate/overlapping module roots, duplicate/overlapping project roots, orphan project-private modules and shared/private overlap. Projects can contain private module areas. Module IDs/class identities and host rules remain validated. Integrations.controllers and integrations.entities configure directory glob patterns; integrations.services.exclude configures module-relative exclusions. Defaults recognize Controller and Entity directories at any depth, exclude Entity and Dto data from services, and impose no domain/application layout.

## Actual dependencies

Use PHP-Parser NameResolver and a source index. Inspect actual class reference sites, never unused imports alone. Validate direct edges and private scope restrictions for active modules. Index inactive modules too so references to them are identifiable and rejectable. Module/project descriptor metadata does not count as business usage. Ordinary application code and vendor classes are outside this enforcement; they retain normal Symfony behavior. PHPDoc, runtime strings and dynamic container references are explicitly outside the initial guarantee. Build failures identify file, line, source module and referenced class/target module.

## Package boundary and migration

Remove Foundation components and their tests/docs/configuration from Sillbeam; their historical implementation remains recoverable in Git. Do not create a partially designed Strata package or keep compatibility shims that require it. Convert the internal fixture to plain Symfony controllers/services/Doctrine entities. Keep application variant tests and add meaningful core regressions. Trim runtime requirements to the module layer plus PHP-Parser; optional Doctrine libraries belong in development requirements and suggest. The migration is intentionally breaking and documented, including replacement of removed APIs with native Symfony/Doctrine or application code.

## Diagnostics and verification

sillbeam:architecture is always a core command. Output all discovered modules, enabled/active states, activation reasons, dependencies declared/observed, owners, paths and integration directories, plus projects/hosts. Support --format=json. Keep sillbeam:about and optional SillbeamKernel. Route/cache resources derive from discovery, including absent default directories and new/removed files. Verify normal overrides, a minimal zero-config kernel, Doctrine disabled metadata, custom roots, alternate integration patterns, aliases, scopes, actual references and cache invalidation. Run PHPUnit, PHPStan, Deptrac, CS and Composer validation and provide a reproducible supported-version CI matrix.
