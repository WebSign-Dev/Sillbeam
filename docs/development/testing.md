# Quality checks

```bash
composer install
composer test
composer analyse
composer architecture
composer cs:check
composer docs:build
composer validate --strict
composer audit --locked
composer audit --locked --working-dir=apps/test-app/app
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
SILLBEAM_TEST_EXISTING_ROUTE=1 composer test:flex-recipe
composer test:consumer
composer test:release-package
```

Unit tests validate declared graphs, flexible discovery, PSR-4 resolution, static references and native DI attributes/aliases. Integration checks boot standard Symfony kernels, six operating modes, native service overrides and a kernel without Doctrine. ORM tests validate active/private/excluded mappings alongside native mappings and persistence. Cache resources monitor added and removed source paths.

PHPStan runs with `--debug` so analysis also works in environments without local worker sockets. Analysis is configured with 512 MiB of PHP memory. Flex requires Composer network access and a local HTTP endpoint; the test installs the recipe in a temporary consumer project, boots without a package configuration file using both kernel variants, optionally with Doctrine, then removes the recipe and checks that user configuration is preserved.

`test:consumer` installs the six application scenarios without and with Doctrine in independent Composer projects and checks both kernels, HTTP/autowiring, CLI, mappings/persistence and dependency validation. The root test autoloader is not loaded there.

The CI matrix installs current compatible dependencies for PHP 8.4/8.5 with the maintained Symfony 7.4 and 8.1 lines, including matching transitive components. Symfony 8.0 is no longer maintained and its available HttpFoundation and YAML versions are blocked by Composer security advisories, so it is excluded from the automatic matrix. Composer security blocking remains enabled; earlier Symfony 8.0 results are historical compatibility evidence. The [package compatibility evidence](package-compatibility-2026-10-10.md) lists the combinations actually tested and reproduction commands. `tools/prepare-compatibility.php` is intended only for disposable test copies and changes their Composer requirements; a configured matrix does not replace observed test results.

`SymfonySkeletonTest` checks the normal namespace service import with and without area exclusions, native overrides, interface aliases and DI attributes in development/production with warm containers. `tests/bootstrap.php` registers Symfony's exception handler before PHPUnit's test snapshots; `failOnRisky` prevents successful exit codes when handlers are left behind, including under Symfony 7.4/8.0.

Reproducible performance comparison: `php tools/benchmark-architecture.php > benchmark.jsonl`. It pairs Sillbeam and native Symfony applications with 10/50/100 logical areas in development/production. Both expose identical routes and use 20 chained services per area; every response is checked. Three alternating paired repetitions measure cold and cached `cache:warmup`, warm container boot, first HTTP and 150 subsequent HTTP samples per variant/environment/size with p50/p95 and allocated PHP peak memory. Each build/boot/HTTP batch gets a separate PHP process; HTTP uses `Kernel::handle`, not a network server. `--smoke` is exercised by the full PHPUnit suite; `--phases` retains the original standalone architecture-phase diagnostic. It is separate from the fair paired application measurements because it preparses sources. See [methodology/results](core-benchmark-2026-10-23.md) and [RC verification](core-rc-2026-10-23.md).

Use `COMPOSER_PROCESS_TIMEOUT=900` (or 0 for an intentionally unbounded local run) for slower consumer installations. `COMPOSER_DISABLE_NETWORK=1 COMPOSER_CACHE_DIR=/path/to/populated/cache` supports offline consumers/archive tests, but cannot be used for Flex because it also blocks the local recipe HTTP endpoint. CI now includes the real release ZIP/no-dev test and explicit audits of both lockfiles. A remote workflow run is separate from local execution of its commands; the workflow currently runs on main/dev/dev-0.3.x pushes or manual dispatch, not pull requests.

For the technical v0.2.0 migration, see the [Core rename verification](core-rename-2026-10-23.md) for its historical checks. Those counts remain historical; see the [0.3.0 acceptance report](release-0.3.0-acceptance/README.md) for the current candidate.

The Flex test now runs controlled auto-scripts to assert the actual instructions
compiled from `post-install.txt`, and compares pre-existing services, console,
routing and package files after install/removal. `SILLBEAM_TEST_EXISTING_ROUTE=1`
also checks non-overwrite of an existing recipe destination and the standard Flex
ownership boundary at uninstall. See the [recipe verification record](flex-recipe-verification.md)
for official checker results and the outstanding Packagist prerequisite.

## Installation and onboarding (0.3.0)

The [2026-10-29 verification](installation-onboarding-2026-10-29.md) records the
Composer/Flex matrix for fresh and existing Symfony 7.4/8.1 consumers, each with
and without Doctrine, including broken installation, manual repair, read-only
Doctor checks and the documented first HTTP module. `composer test:flex-recipe`
now defaults to installing/booting Symfony before adding Core; use
`SILLBEAM_TEST_FRESH=1` for a single fresh installation. The report lists exact
versions, reproduction commands and scope limits.

## Doctor JSON v1

The [Doctor JSON verification](doctor-json-2026-10-29.md) records schema/status
coverage, real native/Core CLI stream and option tests, text regression comparison
and Composer/Flex consumers. See the [contract](../reference/doctor-json.md) and
[schema](../reference/doctor-report.schema.json) for the machine interface.

## Targeted module inspection

The [module-inspection verification](module-inspection-2026-10-29.md) records
module-only/shared/private/Multi-Project coverage, ambiguity, disabled/unassigned
modules, cycles and partial analyses, plus real CLI and compiled-consumer checks.
The [reference](../reference/module-inspection.md) defines selection and state
semantics; inspection uses existing analysis/report evidence only.

## Mermaid graph export

The [graph verification report](mermaid-graph-2026-10-29.md) records deterministic
source, evidence/state boundaries, label escaping and both CLI entry points.
The [graph reference](../reference/mermaid-graph.md) defines the export contract.

## English PHPDoc JSON export

`composer docs:export-json` manually writes the English public API artifact.
See the [contract](../reference/phpdoc-json.md) and
[verification record](phpdoc-json-export-2026-10-29.md). Export integration tests
use temporary projects so ordinary test runs do not update the website artifact.

## Architecture graph UX

`vendor/bin/phpunit tests/Integration/GraphCommandTest.php tests/Integration/GraphSvgTest.php`
checks the common Mermaid/DOT view, direct filters, Project boundaries, collisions,
status evidence, both CLI entries and renderer/filesystem failures. Renderer test
executables exercise actual argument-array process invocation without Graphviz.
The real SVG integration test runs when `dot` is already executable on PATH;
otherwise PHPUnit reports one explicit skip. No renderer is installed by tests.
See the [UX verification report](architecture-graph-ux-2026-10-30.md).
