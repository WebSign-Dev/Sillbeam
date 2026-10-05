<p align="center">
  <img src="../docs/assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520">
</p>

# Nexum Symfony Flex Recipe

This directory contains the publishable Symfony Recipes Contrib source for
`benjaminoeffinger/nexum`. Its directory layout can be copied directly into a
fork of `symfony/recipes-contrib`:

```text
benjaminoeffinger/nexum/0.1/
├── manifest.json
└── config/
    ├── packages/nexum.yaml
    └── routes/nexum.yaml
```

The recipe deliberately performs only three installation tasks:

1. register `Nexum\NexumBundle` for every environment;
2. create the central `config/packages/nexum.yaml` with zero configuration;
3. create the one-time `type: nexum` route import.

Doctrine mappings activate automatically when the application registers DoctrineBundle.
The package contains the module architecture layer; Foundation capabilities are removed.

## Validation

From the Nexum repository root:

```bash
composer test:flex-recipe
```

The check builds a local Flex endpoint from this source, installs Nexum into a
temporary Symfony consumer through Composer and verifies the generated bundle,
configuration, route and `symfony.lock` entries, then boots a minimal kernel without Doctrine. The normal PHPUnit suite also
guards the recipe structure and its safe defaults.

The PHP package cannot load its own recipe. Until the recipe is merged into
Symfony Recipes Contrib, consumers either add the three files manually or use
their own Flex endpoint containing this recipe.
