# Release candidate verification v0.1.0 — 2026-10-12

> Historical, completed verification snapshot. Installation, publication and security-channel status describe the dated run, not the current release. For v0.1.0, use `composer require websign-dev/sillbeam:^0.1.0`; see the [release notes](../releases/0.1.0.md) and [Flex guide](../guides/flex-recipe.md).
Verification extends the evidence from 2026-10-10 with normal Symfony skeleton service imports, development/production containers, warm caches and available PHP/Symfony versions. Starting point: 74 tests / 263 assertions; the existing change to `docs/assets/sillbeam-logo.png` was preserved unchanged. No push, merge, tag or release. Composer requirements, lockfile and public runtime API remain unchanged.

## Corrections and integration findings

- The Flex consumer test used `services: {}` and therefore did not check a normal skeleton import. `tools/test-flex-recipe.php` now uses `App\:` with active standard defaults and required area exclusions, a native service and a disabled module. Both kernels are started twice in separate processes in each of `dev`/`prod`, with cold and warm caches, without/with Doctrine.
- Under Symfony 8.0.15 and minimum Symfony 7.4.0 dependencies, FrameworkBundle registered an exception handler during tests: 30 tests were “risky”, yet the old test command exited with code 0. First reproduced with `failOnRisky`, exit code 1. `tests/bootstrap.php` registers Symfony's handler before PHPUnit's ownership snapshots; `phpunit.xml.dist` uses this bootstrap and fails on risky tests. No risky tests remain in the tested combinations. PHPUnit validation was not suppressed and the application kernel was not changed.
- `SymfonySkeletonTest` demonstrates that an unrestricted native import also registers services from disabled modules. This is a preserved Symfony escape hatch, not a Sillbeam activation defect. For Sillbeam-controlled automatic services, exclude `Module`/`Project` or custom areas from broad native imports. With these exclusions, native services, explicit arguments/interface aliases, `Exclude`, `When`, `WhenNot` and `AsAlias` remain available; disabled private and unassigned shared modules do not enter services/routes through Sillbeam. The recipe does not change user service configuration.

No further runtime defects were reproduced in discovery, compiler, source index/parser, dependency analysis, registries, routing or Doctrine; therefore no speculative production changes or optimizations were made. Existing tests check direct/transitive dependencies, cycles, private boundaries, multiple roots/Projects, hosts and request context. Added coverage includes unknown dependencies in inactive modules, activation of another Project's private module, static AST type/expression positions and explicit limits of dynamic class strings/PHPDoc/imports. Doctrine tests confirm a native override of a generated mapping; cache resources are additionally changed for descriptors, controllers and route configuration.

Affected: `tests/Integration/{SymfonySkeletonTest,ModuleLayerTest}.php`, `tests/Unit/{ArchitectureTest,SourceFileParserTest}.php`, `tests/Support/TestApplicationKernel.php`, `tests/bootstrap.php`, `phpunit.xml.dist`, `tools/{test-flex-recipe,benchmark-architecture}.php`, `.php-cs-fixer.dist.php` and README/getting-started/Flex/testing documentation. The test kernel now separates environment/debug containers. Code style checks also cover both tools.

## Checks actually performed

All required commands passed on the final code: `composer validate --strict`, `composer test` (82 tests / 308 assertions), `composer analyse`, `composer architecture`, `composer cs:check`, `composer test:flex-recipe`, `git diff --check`. Deptrac: 0 violations/warnings/errors; 102 existing external/uncovered dependencies remain reported. Expected 404 logs from negative tests are not test failures.

| PHP | Symfony FrameworkBundle | Full PHPUnit suite |
| --- | --- | --- |
| 8.4.26 | 7.4.0, 7.4.20, 8.0.15, 8.1.6 | 82 tests / 308 assertions each, no risky tests |
| 8.5.4 | 7.4.0, 7.4.20, 8.0.15, 8.1.6 | 82 tests / 308 assertions each, no risky tests |

Symfony 7.4.0 was resolved separately with `--prefer-lowest --prefer-stable`: DI 7.4.0, PHP-Parser 5.7.0, DoctrineBundle 3.3.0. Test copies do not change the repository lockfile. PHP 8.4 runs in the existing Docker image `dwell-php-matrix:8.4`, without network access and with only a temporary test copy mounted. PHPStan also passed under Symfony 7.4.20 and 8.0.15.

Additionally: `composer test:consumer` passed, twelve independent installations (six operating modes without/with Doctrine, both kernels), PHP 8.5.4 / Symfony 8.1.8 / DoctrineBundle 3.3.2 / ORM 3.7.5. Flex skeleton tests without/with Doctrine passed under Symfony 8.1.8; additionally without Doctrine under 7.4.20 and with Doctrine under 8.0.15. These Flex runs check cold/warm `dev`/`prod` caches and both kernels. Composer network access and the local HTTP endpoint ran outside the sandbox.

## Measurements

Reproduction: `php tools/benchmark-architecture.php`. Each size/environment uses its own process and temporary workspace, with a descriptor, controller and 20 services per module; dependencies and type references form a chain. Measured under PHP 8.5.4 / Symfony 8.1.6. Times in milliseconds, peak memory in MiB:

| Modules | Env | Files | Index cold / warm | Discovery after index / warm | Graph | Analysis / warm | Container cold / warm | Routes cold / warm | Worker peak |
| ---: | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 10 | dev | 220 | 86.669 / 0.010 | 5.798 / 4.718 | 0.582 | 0.782 / 0.592 | 432.782 / 9.354 | 10.467 / 4.482 | 28 |
| 10 | prod | 220 | 85.035 / 0.009 | 5.683 / 4.632 | 0.579 | 0.745 / 0.572 | 369.046 / 0.304 | 9.867 / 3.607 | 24 |
| 50 | dev | 1100 | 368.987 / 0.011 | 25.681 / 23.166 | 0.691 | 4.967 / 4.596 | 1172.464 / 43.320 | 53.689 / 32.235 | 42 |
| 50 | prod | 1100 | 372.947 / 0.012 | 25.599 / 23.552 | 0.684 | 5.032 / 4.856 | 1003.527 / 0.313 | 33.448 / 15.315 | 36 |
| 100 | dev | 2200 | 725.209 / 0.010 | 53.332 / 50.049 | 0.993 | 15.372 / 14.115 | 2237.515 / 105.589 | 149.993 / 98.576 | 60 |
| 100 | prod | 2200 | 753.572 / 0.014 | 51.961 / 47.766 | 1.073 | 15.442 / 14.187 | 1914.278 / 0.319 | 84.931 / 38.496 | 50 |

With 10/50/100 modules, there are 180/980/1,980 observed references. Parsing/discovery and cold container builds grow approximately linearly; the area lookup in dependency analysis grows faster, but at 100 modules takes only about 15 ms compared with roughly 750 ms for parsing. Warm development boots include resource checks and grow with the application; warm production boots use the dump without this check. No invented thresholds and no demonstrated need for optimization at these sizes.

Measurement limits: “cold” means missing container/routing cache, **not** a completely cold PHP process or OS filesystem cache. Separate architecture phases were measured before the container build; descriptor classes are already loaded. Peak memory covers the entire worker, including retained source index/analysis objects, not only the consumer kernel. “Routes” measures `getRouteCollection()`, not an HTTP load test. Individual measurements with a second full run for plausibility checking, not statistical percentiles or a load guarantee. The warm source index applies only within a build; production deployments still require an explicit cache rebuild.

## Remaining limitations

Exact minimum patch versions PHP 8.4.0/8.5.0 and Symfony 8.0.0 were not run; neither were fresh PHP 8.4 Flex installations. Tested patch versions are listed above; Symfony 7.4.0 was actually tested as the minimum Symfony version. Dynamic class names, PHPDoc, string service IDs and code outside module areas remain outside static dependency validation. Broad native service/route/Doctrine imports explicitly remain user configuration and can intentionally include inactive areas; documented exclusions are an integration prerequisite. Build environment changes and production caches require rebuilding during deployment. No open reproduced defect within the tested scope. Independent review of the changes found no actionable issues.
