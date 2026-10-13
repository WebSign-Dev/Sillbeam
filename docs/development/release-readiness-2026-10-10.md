# Technical release verification — 2026-10-10

> Historical, completed verification snapshot. Installation, publication and security-channel status describe the dated run, not the current release. For v0.1.0, use `composer require websign-dev/sillbeam:^0.1.0`; see the [release notes](../releases/0.1.0.md) and [Flex guide](../guides/flex-recipe.md).
All 37 production files of the module layer and associated unit/integration tests were reviewed: discovery and physical ownership, declared and static dependencies, activation graphs, Symfony DI/routing, Doctrine, registries/request context, cache resources and diagnostic API. Starting point: `3d1c510`, clean working tree, 65 tests / 240 assertions.

## Reproduced and corrected defects

- Without discovered Projects, unknown `projects` configuration was ignored. Existing validation of unknown Project IDs now also applies in application mode.
- A class with Module and Project attributes was assigned ownership depending on attribute order. Multiple descriptor attributes are now unambiguously rejected before reflection.
- Already loaded descriptor attributes with different casing were missed by the AST index. The index now follows PHP class resolution and stores the canonical attribute name. Composer autoloading and PSR-4 requirements remain.
- Module/Project IDs with a trailing newline passed format validation. The existing ID rule now checks the entire string.
- The Doctrine pass also reordered native mapping drivers, changing their priority for overlapping namespaces. Only generated Sillbeam mappings are now sorted within their positions; native mapping order and other driver calls are preserved.

Each defect was first demonstrated by a failing regression test. Corrections change neither the architecture nor permitted dependency directions. An existing AST test contained inappropriate configuration for nonexistent Projects; it was removed.

## Preserved semantics and tested boundaries

Active graphs require enabled dependencies; unknown dependencies, cycles, overlapping descriptor areas and private modules owned by other Projects remain errors. Inactive modules remain catalogued. Static validation covers actual class references between module areas; ordinary Symfony classes, direct Project code and dynamic class strings receive no additional architecture rules. Native service imports, aliases, environment attributes, routes and manual Doctrine mappings remain available.

`ProjectRegistry::forHost()` and the context still throw `OutOfBoundsException` when Projects exist and the host is unassigned. Missing Projects or requests yield `null` in the context; a single Project without hosts applies to every host. In Project mode, there is no active application graph for `hasModule()` without a request. These existing semantics are now explicitly documented and additionally tested for subrequests and native Hybrid routes. Symfony's own trusted-host validation still runs first.

Existing tests cover missing default roots, new/deleted files in custom roots and changes to configuration/Composer as cache resources. Environment activation values remain build configuration; changes require a cache rebuild. Production caches are still rebuilt during deployment.

## Scaling

Synthetic PSR-4 workspaces with 20 classes per shared module plus a descriptor; each subsequent area depends on the previous one and references its class. Discovery, compiler and code analyzer use the same source index. The Project variant adds four Projects with five private modules each and controller/entity directories. Measured on PHP 8.5.4 without a persistent discovery cache:

| Modules / Projects | PHP files | References | Discovery | Compiler | Code validation |
| --- | ---: | ---: | ---: | ---: | ---: |
| 20 / 0 | 420 | 380 | 0.170 s | 0.001 s | 0.002 s |
| 100 / 0 | 2,100 | 1,980 | 0.726 s | <0.001 s | 0.016 s |
| 200 / 0 | 4,200 | 3,980 | 1.434 s | 0.001 s | 0.046 s |
| 220 / 4 | 4,272 | 3,980 | 1.472 s | 0.001 s | 0.047 s |

Peak memory of the shared benchmark process: 16 MiB. These measurements show no relevant discovery/validation bottleneck in the tested structures; no performance optimization was made. They do not measure a full container build and provide no load guarantee for arbitrary graphs or filesystems.

## Final checks and remaining limitations

- `composer test`: 74 tests, 263 assertions passed.
- `composer analyse`: no PHPStan errors.
- `composer architecture`: no Deptrac violations, warnings or errors. 102 external dependencies or dependencies outside the project's layers remain reported as “Uncovered”.
- `composer cs:check`: no style deviations.
- `composer validate --strict`: valid Composer metadata and lockfile.
- `composer test:flex-recipe`: installation and minimal consumer boot passed. The local HTTP port required execution outside the sandbox.
- Independent diff review with no further findings; affected tests passed separately again.

Locally tested: PHP 8.5.4 / Symfony 8.1.6; the Flex consumer resolves its dependencies separately. PHP 8.4 and Symfony 7.4/8.0 were not rerun in this verification; the existing CI matrix remains the relevant configuration for them. PHP CS Fixer notes the difference from the minimum supported PHP version; changes use no PHP 8.5-exclusive syntax. No further reproduced defects remain open within the tested scope. No publication, tags or push.
