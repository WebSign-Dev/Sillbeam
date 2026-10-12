<p align="center">
  <img src="../docs/assets/sillbeam-logo.png" alt="Sillbeam – Build · Connect · Extend" width="520">
</p>

# Sillbeam Symfony Flex Recipe

This directory contains the publishable Symfony Recipes Contrib source for
`websign-dev/sillbeam`. Its directory layout can be copied directly into a
fork of `symfony/recipes-contrib`:

```text
websign-dev/sillbeam/0.1/
├── manifest.json
└── config/
    ├── packages/sillbeam.yaml
    └── routes/sillbeam.yaml
```

The recipe deliberately performs only three installation tasks:

1. register `Sillbeam\SillbeamBundle` for every environment;
2. create the central `config/packages/sillbeam.yaml` with zero configuration;
3. create the one-time `type: sillbeam` route import.

Doctrine mappings activate automatically when the application registers DoctrineBundle.
The package contains the module architecture layer; Foundation capabilities are removed.

The recipe preserves existing `services.yaml` imports. With the standard `App\:` import of `src/`, consumers must add `Module` and `Project` (or their custom discovery areas) to its exclusions when Sillbeam should control automatic activation. Explicit services remain available. See the [integration rules](../docs/guides/getting-started.md#symfony-integration-erhalten).

## Validation

From the Sillbeam repository root:

```bash
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

The check builds a local Flex endpoint from this source, installs Sillbeam into a
temporary Symfony consumer through Composer and verifies the generated bundle,
configuration, route and `symfony.lock` entries, then boots both kernel variants with the selected Doctrine profile. The normal PHPUnit suite also
guards the recipe structure and its safe defaults.

The PHP package cannot load its own recipe. Until the recipe is merged into
Symfony Recipes Contrib, consumers either add the three files manually or use
their own Flex endpoint containing this recipe.

The `0.1/` recipe applies to the proposed `0.1.0` package release and later compatible 0.1 versions. Recipe availability is independent of Packagist publication. Manual setup remains supported.
