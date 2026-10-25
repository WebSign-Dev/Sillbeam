# Flex recipe verification — 2026-10-25

The source at `flex-recipe/websign-dev/sillbeam-core/0.2/` is prepared for an
external Recipes Contrib submission. No publication, remote branch, external PR,
merge or release tag was created. Production PHP sources and architecture
semantics are unchanged.

## Recipe and endpoint

The source manifest contains only `bundles` and `copy-from-recipe`. The central
`SillbeamCoreBundle` is registered for `all`; the only copied file is the required
`config/routes/sillbeam.yaml`. No empty package configuration is created.
`post-install.txt` carries the manual steps; it is not copied into the consumer.

The local endpoint test derives its generated `post-install-output` metadata
from that text file, matching the official checker's endpoint generator. The
fresh consumer has normal Flex `post-install-cmd` / `post-update-cmd` hooks and
an empty auto-scripts collection. Installation runs with scripts enabled, captures
real Composer/Flex output, and requires every nonempty instruction line to appear.
Only controlled test-owned scripts execute; no application repair is automated.

## Executed checks

Local runtime: PHP 8.5.4, Composer 2.9.5, Flex 2.11.0.

| Check | Result |
| --- | --- |
| `vendor/bin/phpunit tests/Unit/FlexRecipeTest.php` | 6 tests, 55 assertions passed; new format tests failed before the fix |
| `vendor/bin/phpunit --configuration=phpunit.xml.dist` | 542 tests, 6049 assertions passed |
| `php tools/test-flex-recipe.php` | Symfony 8.1.9, without Doctrine: passed |
| `SILLBEAM_TEST_DOCTRINE=1 php tools/test-flex-recipe.php` | Symfony 8.1.9, DoctrineBundle 3.3.3: passed |
| `SILLBEAM_SYMFONY_VERSION='7.4.*' php tools/test-flex-recipe.php` | Symfony 7.4.21, without Doctrine, final preservation assertions: passed |
| `SILLBEAM_TEST_DOCTRINE=1 SILLBEAM_TEST_EXISTING_ROUTE=1 php tools/test-flex-recipe.php` | Symfony 8.1.9, Doctrine, final assertions and pre-existing destination: passed |
| `composer validate --strict` | Passed |
| `vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug` | No errors |
| PHP CS Fixer check on `FlexRecipeTest.php` and `test-flex-recipe.php` | No fixable files |
| `git diff --check` | Passed |

Consumers use a temporary local Contrib endpoint and a copied local package
presented as 0.2.0. These tests prove source behavior, not public availability.
They verify bundle registration, the exact route import, `symfony.lock` ownership,
printed instructions, zero-config boot, native service availability, disabled
module isolation, both Symfony/Sillbeam kernel variants, cold/warm development
and production boots, optional Doctrine metadata, uninstall and native boot after
explicit application-owned cleanup. Pre-existing service, console, native routing
and package configuration contents are compared byte-for-byte after installation
and removal. Manually created Sillbeam package options survive removal.

The complete suite prints existing negative-test debug messages for unreadable
sources, report replacement failures and temporary/documentation cleanup; PHPUnit
reports no failures. This is not a full PHP/Symfony compatibility matrix run.

## Official Symfony recipe validation

Reviewed the current [recipe format/checklist](https://github.com/symfony/recipes/blob/main/README.rst),
[Contrib rules](https://github.com/symfony/recipes-contrib/blob/main/README.rst) and
[QA workflow](https://github.com/symfony/recipes/blob/main/.github/workflows/callable-qa.yml).
The [official recipes-checker](https://github.com/symfony-tools/recipes-checker)
was downloaded and installed separately under `/tmp`; no package dependency was
added. Source archive commit: `76967833e58b56c6fed3ba5ffdb64a1f729ea2bb`.

Run from `flex-recipe/` with that tool's `run` executable:

- `lint:manifests --contrib`: exit 0.
- `lint:yaml`, receiving `websign-dev/sillbeam-core/0.2/config/routes/sillbeam.yaml`
  on stdin: exit 0.
- `lint:packages`: exit 1: `Package "websign-dev/sillbeam-core" does not exist on Packagist`.
  Consequently matching public versions cannot yet be validated.

The checker's locked Symfony 5.4.1 HTTP client has a cURL destructor incompatibility
with PHP 8.5. The package check was repeated with PHP's native HTTP transport
(`php -n`, loading ctype/intl/mbstring/iconv), yielding the same missing-package
result without that fatal error. Its old Runtime still emits a PHP deprecation;
this is external tool behavior, not a package test failure.

Applicable upstream static checks also passed: valid JSON and YAML, four-space
JSON/YAML indentation, UTF-8/newline endings, no symlinks, no `.yml` or `.gitkeep`,
underscore config filenames, no `~` nulls, and no insecure Symfony documentation
links. Local package metadata has the required MIT license and `symfony-bundle`
type and no forbidden monolithic Symfony dependency. The route import supplies
a necessary task beyond bundle registration. Hosted PR checks (including
`lint:pull-request`) are intentionally left to the external submission process.

## Ownership boundary and publication gates

Normal Flex installation preserves a pre-existing destination route file; the
`SILLBEAM_TEST_EXISTING_ROUTE=1` case asserts its custom comment remains intact.
Flex nevertheless tracks `config/routes/sillbeam.yaml` and removes that filename
on uninstall. Consumers must preserve custom content at this managed filename
before removal. The recipe cannot change Flex's ownership model through its
manifest. Application configuration under other filenames and manually authored
package options is retained, as verified above.

Public submission remains gated on Packagist registration, a matching released
package version, external Symfony Bot validation/review/approval and endpoint
availability. The v0.2.0 package tag alone does not activate a recipe. Consumers
must allow Contrib recipes and the Flex Composer plugin. See the
[submission instructions](../../flex-recipe/README.md#submit-to-symfonyrecipes-contrib).

A separate read-only code review found no material implementation issue; its
request for this verification document has been completed. All changes remain
local and uncommitted.
