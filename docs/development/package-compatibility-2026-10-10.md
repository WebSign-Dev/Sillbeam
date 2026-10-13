# Package compatibility and installation — 2026-10-10

> Historical, completed verification snapshot. Installation, publication and security-channel status describe the dated run, not the current release. For v0.1.0, use `composer require websign-dev/sillbeam:^0.1.0`; see the [release notes](../releases/0.1.0.md) and [Flex guide](../guides/flex-recipe.md).
The starting point is technical stabilization in `57bfe48`. Verification uses actual PHP runtimes and independent Composer resolutions in temporary, separate directories. Historical private GitHub CI runs were not used as evidence of defects.

## Supported and actually tested versions

`composer.json` allows PHP `^8.4` and Symfony components `^7.4 || ^8.0`. Tested version lines are PHP 8.4/8.5 with Symfony 7.4/8.0/8.1. Composer determines which patch version can be installed, including all dependency requirements. No platform override or `--ignore-platform-reqs` was used.

| PHP runtime | Symfony FrameworkBundle | Full quality suite | 12 manual consumer installations | Flex without/with Doctrine |
| --- | --- | --- | --- | --- |
| 8.4.26 | 7.4.20 | passed | passed | passed |
| 8.4.26 | 8.0.15 | passed | passed | passed |
| 8.4.26 | 8.1.8 | passed | passed | passed |
| 8.5.11 | 7.4.20 | passed | passed | passed |
| 8.5.11 | 8.0.15 | passed | passed | passed |
| 8.5.11 | 8.1.8 | passed | passed | passed |

Transitive Symfony components also follow the selected version line. Contracts, polyfills and Flex have independent version numbers and are not forced to 7.4/8.0/8.1. Tested optional dependencies: DoctrineBundle 3.3.2 and ORM 3.7.5. AST parser: 5.9.0; PHPUnit: 12.5.38. This does not automatically test other patch versions or later versions allowed by Composer constraints.

Each matrix cell passed PHPUnit (74 tests / 263 assertions), PHPStan, Deptrac, code style, `composer validate --strict` and `composer check-platform-reqs`. Deptrac reports 0 violations, warnings and errors; external dependencies remain outside the project's layers. Containers initially had 128 MiB of PHP memory, causing PHPStan to abort; all runs passed with an explicit 512 MiB. This setting is also configured in CI and is not a Sillbeam runtime requirement.

## Independent consumers

`composer test:consumer` copies only source and configuration for the six scenarios into fresh applications: Symfony-only, Modules-only, Project-only, Single-Project, Multi-Project and Hybrid. Each scenario receives its own installation without or with Doctrine. Sillbeam is **copied** from a local path repository, rather than used through the root autoloader or a vendor symlink. Each consumer starts without a lockfile, uses normal stable Composer resolution and has its own vendor autoloader. Root development dependencies and `Sillbeam\Tests` are explicitly excluded.

Each of the 72 manual matrix installations is tested in separate PHP processes with the standard Symfony kernel and optional SillbeamKernel: 144 successful kernel profiles in total. The test validates active discovery graphs, autowiring through actual HTTP responses, routes/host boundaries, both Sillbeam CLI commands and, with Doctrine, native and Module/Project mappings with persistence through the same EntityManager. An actual consumer build also rejects active references to disabled module code. All other existing dependency rules remain covered by the full PHPUnit suite.

The Flex test installs the prepared recipe through a temporary local HTTP endpoint. It checks bundle registration, exactly the two recipe configuration files, `symfony.lock`, the module route and both kernel variants. The Doctrine profile also checks module mapping. All twelve matrix recipe installations passed. This proves the prepared recipe, not its availability in Recipes Contrib; package and recipe remain unpublished.

## Reproduction and CI

Run the following commands **in a disposable copy** under the desired actual PHP runtime. `prepare-compatibility.php` intentionally changes Composer requirements there. Repeat for each of the three Symfony lines and both PHP versions:

```bash
export SILLBEAM_SYMFONY_VERSION='7.4.*' # alternatively 8.0.* or 8.1.*
php tools/prepare-compatibility.php
composer update --with-all-dependencies --no-scripts --no-plugins --no-interaction --prefer-dist
composer validate --strict
composer check-platform-reqs
composer test
composer analyse
composer architecture
composer cs:check
composer test:consumer
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

The suite requires PHP extensions including XML/DOM, mbstring and PDO SQLite; the analyzer receives 512 MiB of memory. Installation tests require Composer network access; Flex also needs a local HTTP port. Without `SILLBEAM_SYMFONY_VERSION`, `composer test:consumer` tests normally resolved versions for the current runtime. CI uses the same preparation and tests for all six combinations. GitHub workflows were not started here; they can run independently again after public access is enabled.

## Result

No production compatibility or integration defects were reproduced; production code and runtime constraints remain unchanged. Only the required independent installation checks, reproducible version selection and CI coverage were added. No open technical blockers for the six tested combinations. No publication or push took place.
