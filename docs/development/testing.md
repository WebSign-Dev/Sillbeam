# Quality checks

```bash
composer install
composer test
composer analyse
composer architecture
composer cs:check
composer validate --strict
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
composer test:consumer
composer test:release-package
```

Unit tests validate declared graphs, flexible discovery, PSR-4 resolution, static references and native DI attributes/aliases. Integration checks boot standard Symfony kernels, six operating modes, native service overrides and a kernel without Doctrine. ORM tests validate active/private/excluded mappings alongside native mappings and persistence. Cache resources monitor added and removed source paths.

PHPStan runs with `--debug` so analysis also works in environments without local worker sockets. Analysis is configured with 512 MiB of PHP memory. Flex requires Composer network access and a local HTTP endpoint; the test installs the recipe in a temporary consumer project, boots without a package configuration file using both kernel variants, optionally with Doctrine, then removes the recipe and checks that user configuration is preserved.

`test:consumer` installs the six application scenarios without and with Doctrine in independent Composer projects and checks both kernels, HTTP/autowiring, CLI, mappings/persistence and dependency validation. The root test autoloader is not loaded there.

The CI matrix installs current compatible dependencies for PHP 8.4/8.5 with Symfony 7.4/8.0/8.1, including matching transitive components. The [package compatibility evidence](package-compatibility-2026-10-10.md) lists the combinations actually tested and reproduction commands. `tools/prepare-compatibility.php` is intended only for disposable test copies and changes their Composer requirements; a configured matrix does not replace observed test results.

`SymfonySkeletonTest` checks the normal namespace service import with and without area exclusions, native overrides, interface aliases and DI attributes in development/production with warm containers. `tests/bootstrap.php` registers Symfony's exception handler before PHPUnit's test snapshots; `failOnRisky` prevents successful exit codes when handlers are left behind, including under Symfony 7.4/8.0.

Reproducible performance test: `php tools/benchmark-architecture.php`. Each combination of 10/50/100 modules and development/production runs in its own process with a temporary PSR-4 workspace, 20 chained services plus a descriptor and controller per module. JSON lines contain cold/warm source index, discovery, graph compiler, dependency analysis, container boot, route loading and process peak memory. The warm index applies within a build; it is not a persistent discovery cache. Measurements and limitations are in the [current release audit](release-readiness-2026-10-12.md).
