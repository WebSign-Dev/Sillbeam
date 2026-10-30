# Flex recipe 0.3 submission preparation — 2026-10-30

Recipe source: `flex-recipe/websign-dev/sillbeam-core/0.3/`.
The unpublished local 0.2 source was moved to 0.3 for this first Contrib submission.
The post-install guide link is pinned to the published v0.3.0 tag.

Public package: `websign-dev/sillbeam-core` v0.3.0, MIT, symfony-bundle,
source reference `5a6694c5a06fb78adc08ab61215922e953a9368d`.

## Verification

- `vendor/bin/phpunit tests/Unit/FlexRecipeTest.php tests/Integration/CoreMigrationTest.php`:
  9 tests, 71 assertions passed.
- `composer validate --strict`, `php -l tools/test-flex-recipe.php`, `git diff --check`: passed.
- Official recipes-checker: `lint:manifests --contrib`, `lint:yaml` (recipe YAML on stdin),
  `lint:packages`: exit 0. Tool installed separately under /tmp; package lint used
  native HTTP transport via `php -n` with ctype, intl, mbstring and iconv to avoid
  its old HTTP client's PHP 8.5 compatibility issue. Old Runtime emitted a deprecation.
- `SILLBEAM_TEST_RELEASED=1 php tools/test-flex-recipe.php`: passed against actual
  Packagist v0.3.0, Symfony 8.1.10, Flex 2.12.0, without Doctrine.
- `SILLBEAM_TEST_RELEASED=1 SILLBEAM_SYMFONY_VERSION='7.4.*' SILLBEAM_TEST_DOCTRINE=1 php tools/test-flex-recipe.php`:
  passed against actual Packagist v0.3.0, Symfony 7.4.22, DoctrineBundle 3.3.4.

Both integration runs verified installation, recipe 0.3 tracking, printed instructions,
application file preservation, Doctor/onboarding, both kernel variants, dev/prod
boots, HTTP and removal; the Doctrine variant also verified mappings.
The local recipe endpoint is intentionally unpublished. Released-package mode
removes the local Composer path repository; default development mode remains available.

Only the three files beneath the 0.3 directory belong in recipes-contrib.
PR title/body and copy instructions: `flex-recipe/PR-DRAFT.md`.
No push, remote writes or upstream PR were performed. Hosted PR-specific checks
and Symfony review/approval must occur after separately authorized submission.
Historical acceptance reports retain the recipe version used at their original run.
