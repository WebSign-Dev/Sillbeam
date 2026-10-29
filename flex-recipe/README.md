<p align="center">
  <img src="../docs/assets/sillbeam-logo.png" alt="Sillbeam – Build · Connect · Extend" width="520">
</p>

# Sillbeam Symfony Flex Recipe

This directory contains the technically complete, locally tested Symfony Recipes
Contrib source for
`websign-dev/sillbeam-core`. Its directory layout can be copied directly into a
fork of `symfony/recipes-contrib`. Public Contrib approval remains outstanding;
the Composer package is already registered on Packagist.

Recipe layout:

```text
websign-dev/sillbeam-core/0.2/
├── manifest.json
├── post-install.txt
└── config/
    └── routes/sillbeam.yaml
```

The recipe deliberately performs only two installation tasks:

1. register `Sillbeam\Core\SillbeamCoreBundle` for every environment;
2. create the one-time `type: sillbeam` route import.

Doctrine mappings activate automatically when the application registers DoctrineBundle.
The package contains the module architecture layer; Foundation capabilities are removed.

The recipe preserves existing `services.yaml` imports. With the standard `App\:` import of `src/`, consumers must add `Module` and `Project` (or their custom discovery areas) to its exclusions when Sillbeam should control automatic activation. Explicit services remain available. See the [integration rules](../docs/guides/getting-started.md#preserve-symfony-integration).

## Validation

From the Sillbeam repository root:

```bash
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
SILLBEAM_TEST_FRESH=1 composer test:flex-recipe
SILLBEAM_TEST_FRESH=1 SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

The check builds a local Flex endpoint from this source and installs a simulated
0.3.0 Core package into a temporary Symfony consumer through real Composer. By
default Symfony is installed and booted before adding Core; `SILLBEAM_TEST_FRESH=1`
resolves the fresh consumer in one install. It diagnoses missing integration and
service overlap, applies explicit test-authored corrections, checks read-only Doctor
execution and unverified dynamic configuration, creates Hello with the existing
make command, and exercises the guide's actual service/controller. It verifies the generated bundle,
route and `symfony.lock` entries, actual Flex output from `post-install.txt`, and unchanged existing services, routing and console files, then boots without package configuration, exercises both kernels and removes the package again. Removal must preserve native services and user-authored package configuration. The normal PHPUnit suite also
guards the recipe structure and its safe defaults.

The PHP package cannot load its own recipe. Until the recipe is merged into
Symfony Recipes Contrib, consumers either register the bundle and route import manually or use
their own Flex endpoint containing this recipe.

Install an available compatible package with `composer require websign-dev/sillbeam-core`. For 0.3.0 release consumers, `:^0.3.0` requires that release to be published. See [release status](../README.md#release-status). The `0.2/` directory is the compatible minimum recipe version and remains used for this onboarding update. Recipe availability is independent of package installation; manual setup remains supported.

The package declares `type: symfony-bundle`, following [Symfony bundle installation guidance](https://symfony.com/doc/current/bundles/best_practices.html#installation). No semantically empty file is created under `config/packages`, as required by the [recipe checklist](https://github.com/symfony/recipes/blob/main/README.rst#recipe-checklist).

Before running Doctor, manually select `use Sillbeam\Core\Console\Application;` in the existing `bin/console`;
retain its bootstrap and Kernel. This keeps checks available before container
compilation even when bundle registration is missing. Run `php bin/console sillbeam:doctor`,
review its action items and unverified checks, and make the reviewed changes yourself.
Review broad service imports and exclude module/Project discovery
areas where Sillbeam should control activation. Then create the first descriptor
with `php bin/console sillbeam:make:module Hello` and run
`php bin/console sillbeam:architecture`. Add the service and controller from the [first-module example](../docs/guides/getting-started.md#4-create-the-first-module) and verify `/hello`. See [CLI setup](../docs/reference/cli.md).
The recipe never edits `bin/console` or `services.yaml`.

## Submit to symfony/recipes-contrib

1. `websign-dev/sillbeam-core` is already registered on Packagist. Once the stable
   Core tag is available, verify that Packagist lists a matching released version
   (at least 0.2.0) before submitting the recipe. The package must retain its MIT
   license and `symfony-bundle` type. A local path repository does not prove this.
2. Copy only `websign-dev/sillbeam-core/0.2/` from this directory into a checkout
   of `symfony/recipes-contrib`, preserving that directory structure. Do not copy
   this README, tests, endpoint fixtures or the PHP package source.
3. Run the current official `symfony-tools/recipes-checker` from that checkout:
   `run lint:manifests --contrib`, `run lint:yaml` with YAML paths on stdin, and
   `run lint:packages`. Follow the current upstream QA workflow for text/layout
   and consumer checks. See [the local verification record](../docs/development/flex-recipe-verification.md).
4. A maintainer must separately submit and complete the external review process.
   Current Contrib rules require successful Symfony Bot validation and an
   independent approval, with a Symfony Core Merger involved as author/reviewer.
   No external submission or publication is performed by this repository's tests.
5. After acceptance, check that the recipe is present in the generated default
   Flex endpoint. Consumers must allow Contrib recipes, for example with
   `composer config extra.symfony.allow-contrib true`, and allow the Flex plugin.

Publishing the v0.2.0 package tag alone does **not** activate this recipe. Stable
package-version availability, recipe acceptance and endpoint availability are
separate steps. Package registration is complete. Until recipe approval, use
manual installation or an explicitly configured private endpoint. The `0.2` directory describes the minimum recipe version; Flex chooses
an eligible recipe independently of the package's own release archive.

## Source format and ownership

`post-install.txt` is a special recipe source file, displayed rather than copied.
Do not add `post-install-output` to the source `manifest.json`; the endpoint
builder derives that generated field from the text file. The local test mirrors
that conversion and invokes Flex's real auto-scripts to verify the printed lines.

The manifest registers the central bundle for `all` and copies only `config/`,
which currently contains the necessary route import. This is more than bundle
registration alone, so a recipe is justified. No aliases, copy-from-package,
service edits, bootstrap edits or empty package configuration are needed.
Ordinary Flex installation preserves existing destination files; do not use
`--force` without reviewing the consequences. Flex tracks the route filename in
`symfony.lock`, including a pre-existing file at that destination: back up any
application-specific edits to that recipe-managed filename before removal.
Application-owned configuration under other filenames is preserved by the tests.

Sources checked on 2026-10-25:
[recipe format and validation](https://github.com/symfony/recipes/blob/main/README.rst),
[Contrib rules](https://github.com/symfony/recipes-contrib/blob/main/README.rst),
[upstream QA workflow](https://github.com/symfony/recipes/blob/main/.github/workflows/callable-qa.yml),
[official checker](https://github.com/symfony-tools/recipes-checker).
