# Changelog

This changelog records released versions and unreleased changes of Sillbeam.

## v0.2.0 — unreleased

### RC verification and release preparation

- Fix CVE-2026-108082 in development/reference-app lockfiles by updating HttpFoundation to 8.1.9/7.4.21; retain dependency constraints and audit both lockfiles in CI.

- Verify the integrated candidate across PHP 8.4/8.5 and Symfony 7.4/8.1, isolated consumers, Flex and the real no-dev Composer ZIP; record observed results and remaining release gates.
- Extend the benchmark with paired native Symfony applications, 10/50/100 modules, cold/cached builds, warm boot, HTTP p50/p95 and allocated peak memory; retain raw samples and measurement limits without performance claims.
- Add benchmark smoke coverage and the ZIP installation check to CI; describe the lifecycle subprocess helper as impure for current static analysis.
- Complete migration/release notes for aggregate findings, container-independent reports and production prebuild. See the [RC report](docs/development/core-rc-2026-10-23.md).

### Architecture reporting

- Extend `sillbeam:architecture` with one text/JSON report, schema version 1 and exit codes 0 (valid), 1 (violations), 2 (unavailable or invalid configuration/usage).
- Add an opt-in Console Application adapter that uses the existing kernel/bootstrap and Symfony configuration loaders before container compilation, sharing the same analyzer and configuration processor.
- Preserve module/Project catalogs and represent unavailable activation graphs explicitly as unknown. See [CLI setup and schema](docs/reference/cli.md).

### Architecture validation

- Follow Symfony debug mode: validate cold/rebuilt development containers and CLI builds; production HTTP uses prebuilt state and returns generic 503 when compilation would be required.
- Validate current sources/configuration before cache clear/warmup, including warm caches and no-warmup/optional-warmer flags. Invalid builds fail before proceeding.
- Include remediation hints and available locations in development diagnostics and the existing text/JSON reports. See [validation lifecycle](docs/reference/architecture-analysis.md#development-and-production-lifecycle).

- Aggregate independently detectable existing Module, Project and dependency violations as structured findings with stable rule identifiers, severity and available context.
- Add a reusable analysis result with deterministic ordering and deduplication; distinguish rule violations from fatal analysis blockers.
- Keep strict container-build rejection through a `LogicException`-compatible aggregate exception; do not expose invalid runtime graphs.
- Preserve existing rules, CLI output and runtime integrations. See [architecture analysis](docs/reference/architecture-analysis.md).

### Breaking changes

- Rename the Composer package from `websign-dev/sillbeam` to `websign-dev/sillbeam-core` and prepare repository references for `WebSign-Dev/sillbeam-core`.
- Move all PHP APIs from `Sillbeam\` to `Sillbeam\Core\`.
- Replace `Sillbeam\SillbeamBundle` with `Sillbeam\Core\SillbeamCoreBundle` and `SillbeamExtension` with `Sillbeam\Core\DependencyInjection\SillbeamCoreExtension`.
- Move the locally prepared Flex recipe to `websign-dev/sillbeam-core/0.2`.
- Preserve `sillbeam` configuration, `sillbeam:*` commands, routing type `sillbeam`, named service IDs, and all Module, Project, service, routing and Doctrine behavior.

See the [migration guide](docs/migration/0.2.0.md). No compatibility aliases for the old PHP namespace are provided. Repository renaming, publication and release tagging are separate manual steps; this version is not published.

## v0.1.0

Sillbeam v0.1.0 is the first public release of the Symfony module architecture layer, distributed as the Composer package `websign-dev/sillbeam`.

### Added

- Attribute-based Module and optional Project descriptors with configurable discovery roots, PSR-4 namespaces and exclusions.
- Build-time validation of descriptor ownership, unique IDs, declared dependencies, cycles and private module boundaries.
- Static validation of direct PHP class references between active modules, with file and line diagnostics for undeclared or forbidden dependencies.
- Immutable Module and Project catalogs, active dependency graphs and activation diagnostics through registry contracts.
- Native Symfony service imports with autowiring, autoconfiguration, environment attributes, exclusions and interface aliases.
- Attribute routes for active modules and Projects, including Project route-name prefixes and host filters.
- Optional DoctrineBundle integration for active module and direct Project entities, preserving native mappings and explicit overrides.
- Request-based Project context, `sillbeam:architecture` text/JSON output and `sillbeam:about` runtime diagnostics.
- Support for the standard Symfony kernel and the optional `SillbeamKernel`.
- Symfony Flex recipe source for bundle registration and a single route import; default operation requires no package configuration file.

### Fixed

- Excluded `Tests/`, `Fixtures/` and `Migrations/` from automatic Module/Project service imports, including nested directories; production-only Composer installations can retain development source files without registering them as services.

- Preserved discovery exclusions across overlapping roots and subsequent service, route and entity imports.
- Excluded entity subdirectories from generated parent Doctrine mappings while retaining intentional native mapping overrides.
- Invalidated development containers when the first Sillbeam package configuration is added after a zero-config boot.
- Rejected unknown Project configuration even when no Projects are discovered, multiple descriptor attributes on one class and IDs with trailing newlines.
- Resolved descriptor attribute names consistently with PHP's case-insensitive class resolution.
- Preserved native Doctrine driver priority when sorting generated Sillbeam mappings.
- Corrected configuration type annotations for static analysis with Symfony 7.4.0.

### Changed

- Standardized installation on `composer require websign-dev/sillbeam:^0.1.0`, with `0.1.0` available as an exact version constraint.
- Aligned example applications, the isolated test application and independent installation checks with the 0.1.0 package version.
- Published English guides, configuration and API references, migration instructions and release notes.
- Updated the security policy with private reporting channels and support for v0.1.0.
- Restricted library distribution to runtime code, package metadata, license and public documentation; development applications, tools, caches and internal plans are excluded.
- Removed the former private Nexum/Foundation APIs. API/CRUD, authentication, entity bases, stores and infrastructure helpers are no longer included. Existing development consumers must follow the [migration guide](docs/migration/modular-monolith.md).

### Compatibility and verification

- Requires PHP `^8.4`, Composer runtime API `^2.2` and Symfony `^7.4 || ^8.0`; Doctrine remains optional.
- Verified PHP 8.4/8.5 with Symfony 7.4/8.0/8.1 through the quality suite and independent Composer installations.
- Covered Symfony-only, Modules-only, Project-only, Single-Project, Multi-Project and Hybrid layouts, both kernels and operation with/without Doctrine.
- Verified cold/warm development and production containers, documented service/controller examples, and Flex installation/removal preserving application configuration.
- Enforced PHPUnit handler ownership checks so risky integration tests fail rather than returning a successful exit code.

Projects share the Symfony container, event system and database; they do not provide tenant or security isolation. Static validation covers supported PHP class references within module areas, not dynamic class strings or application-wide access control. This is a pre-1.0 API.

See the [v0.1.0 release notes](docs/releases/0.1.0.md) for installation, compatibility evidence and known limitations.
