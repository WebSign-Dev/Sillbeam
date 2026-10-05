# Nexum Evolution Implementation Plan

> **For agentic workers:** Use superpowers:executing-plans to implement this plan task-by-task. The user explicitly requested the entire approved review's implementation in this session.

**Goal:** Deliver Nexum's configurable, validated Symfony module layer.
**Architecture:** A shared build result supplies native DI, routing and optional Doctrine. A static source index supplies discovery and actual dependency validation.
**Tech Stack:** PHP 8.4+, Symfony 7.4/8.x, PHP-Parser 5, optional Doctrine ORM 3.
**Spec:** docs/superpowers/specs/2026-10-01-nexum-evolution-design.md

## Global Constraints

- Preserve existing local Harbor changes and nexum.zip.
- PHP ^8.4; Symfony ^7.4 || ^8.0.
- No required Foundation or Strata components; one Symfony container.
- Native application overrides remain available.
- Static class references only; no promise of dynamic PHP/PHPDoc inference.

## Review Focus

- Missing roots must remain monitored so creating a first module invalidates caches.
- Overlapping roots and namespace prefixes must not change ownership by scan order.
- Disabled module code must not leak through Doctrine or parent Project service imports.
- Native Symfony exclusions/environment/aliases and user overrides must survive.
- Unused imports and descriptor metadata must not produce fake actual dependencies.

### Task 1: Correct core regressions

Files: tests/Unit/{ArchitectureTest,DiscoveryOptionsResolverTest,ArchitectureServiceLoaderTest}.php; src/Architecture/ArchitectureCompiler.php; src/DependencyInjection/ArchitectureServiceLoader.php.
Interfaces: preserve compile(DiscoveryResult,array): CompiledArchitecture and load(ContainerBuilder,string,string,string,bool): void while adding optional environment/excludes.
- [x] Add and run failing disabled-private, ownership, relative-path and native-service behavior tests.
- [x] Implement visited graph traversal and native Symfony class imports.
- [x] Run focused tests and record result.

### Task 2: Source index and flexible discovery

Files: src/Architecture/{DiscoveryRoot,DiscoveryOptions,DiscoveryOptionsResolver,SourceInspector,SourceFile,PathMatcher}.php; src/{Module/ModuleDiscovery,Project/ProjectDiscovery,Architecture/ArchitectureDiscovery}.php; tests/Unit/FlexibleDiscoveryTest.php.
Interfaces: DiscoveryOptions retains root/namespace and adds moduleRoots/projectRoots/excludes/integration patterns. SourceInspector indexes parsed files and resolves namespace/root ownership.
- [x] Write and run failure tests for alternate/multiple roots, excludes, arbitrary descriptor names, ordinary interfaces, orphan private descriptors, duplicate/overlapping ownership and alternate integrations.
- [x] Implement canonical roots, AST descriptor identification and ownership validation.
- [x] Run unit discovery/architecture suite.

### Task 3: Static dependency enforcement

Files: src/Architecture/{SourceFile,SourceInspector,CodeDependencyAnalyzer,CompiledArchitecture}.php; tests/Unit/CodeDependencyAnalyzerTest.php.
Interfaces: analyzer analyze(DiscoveryResult,CompiledArchitecture,DiscoveryOptions): array of observed module references, with source file/line evidence.
- [x] Write and run failures for missing direct dependency, illegal scopes, inactive target, aliases, class reference types and unused imports.
- [x] Implement AST references, class ownership index and direct dependency validation.
- [x] Verify allowed references and inactive-code behavior.

### Task 4: Shared build and Foundation removal

Files: src/DependencyInjection/{Configuration,NexumExtension}.php; src/Routing/NexumRouteLoader.php; composer.json; fixtures/apps/skeleton/Flex recipe; core integration tests; remove Foundation src/tests.
Interfaces: one cached CompiledArchitecture across prepend/load; Doctrine activates on installed bundle unless disabled; routing consumes active registries and discovery resources.
- [x] Write failing minimal-kernel/Doctrine activation/exclusion/override tests.
- [x] Remove Foundation integration and runtime requirements; migrate fixture to plain Symfony/Doctrine.
- [x] Run complete PHPUnit suite and regenerate Composer lock without unnecessary package upgrades.

### Task 5: Core diagnostics, docs and supported versions

Files: src/Architecture/Console/ArchitectureCommand.php; registries/definition evidence fields; docs, README, migration, .github/workflows/ci.yml, deptrac.yaml and tools/test-flex-recipe.php.
Interfaces: nexum:architecture --format=json returns module catalog, project assignments, reasons, paths and observed references.
- [x] Write and run failing command and custom-root cache tests.
- [x] Implement command, resource tracking and package/docs/migration/CI alignment.
- [x] Run PHPUnit, PHPStan, Deptrac, style and Composer checks.

### Task 6: Whole-change review

- [x] Review completed diff against spec, migration and new boundary tests with a fresh reviewer.
- [x] Fix important findings with regression tests; rerun full checks.
- [x] Report actual verification and limitations; leave reviewable changes without publishing or merging.
