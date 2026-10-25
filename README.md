<a id="readme-top"></a>

<div align="center">
  <img src="docs/assets/sillbeam-header.png" alt="Sillbeam — modular Symfony architecture, connecting feature modules, shared contracts and infrastructure." width="100%">
</div>

<div align="center">

[![Composer][composer-shield]][composer-url]
[![CI][ci-shield]][ci-url]
[![PHP][php-shield]](#requirements)
[![Symfony][symfony-shield]](#requirements)
[![Status][status-shield]](#install-with-composer)
[![License][license-shield]](LICENSE)

**A modular architecture layer for scalable, maintainable Symfony applications.**

[Website](https://getsillbeam.websign.de/) · [Quick start](#quick-start) · [Documentation](docs/README.md) · [Release notes](docs/releases/0.2.0.md) · [Report an issue][issues-url]

</div>

Sillbeam Core extends Symfony with explicit module boundaries, declared dependencies and optional Projects. It discovers your architecture, validates its dependency graph and checks static code references when Symfony builds the container. Symfony continues to handle services, routing and the application runtime.

Use it in an existing Symfony application or build a modular monolith from the start. Doctrine integration is optional, and the standard Symfony kernel is sufficient. `SillbeamKernel` provides a convenient alternative.

## Modular architecture

| Capability | Scope |
| :--- | :--- |
| **Feature modules** | Organize code through `#[SillbeamModule]` descriptors and declare direct dependencies. |
| **Architecture checks** | Detect cycles, missing dependencies, invalid boundaries and undeclared static cross-module references during container compilation. |
| **Symfony services** | Discover module services through native autowiring, autoconfiguration and Symfony service attributes. |
| **Module routing** | Import controller routes from active modules and Projects through one route-loader entry. |
| **Optional Projects** | Compose multiple surfaces with shared and private modules, including host-based routing. |
| **Optional Doctrine integration** | Register entity mappings from active modules and Projects when DoctrineBundle is installed. |
| **Flexible discovery** | Configure multiple roots, namespaces, exclusions and alternative controller or entity directories. |
| **Architecture diagnostics** | Inspect the catalog, activation reasons and dependency evidence as text or JSON. |

Modules normally live in `src/Module`; Projects live in `src/Project`. These defaults can be [configured](docs/reference/configuration.md). Each descriptor owns its directory and descendants. Descriptor filenames are flexible, but must match their PSR-4 class names.

## Quick start

### Requirements

- **PHP 8.4 or newer** and Composer.
- **Symfony 7.4 or 8.x** in the consuming application.
- **DoctrineBundle**, only if you want automatic Doctrine entity mappings.

The current CI targets PHP 8.4/8.5 with Symfony 7.4/8.1. The [independent v0.2.0 acceptance](docs/development/installation-acceptance-2026-10-25/INSTALLATIONS-ABNAHME.md) records its exact tested versions and historical limitations; the [F01/F02 follow-up](docs/development/doctor-f01-f02-verification-2026-10-25/README.md) documents the subsequent Doctor corrections. The original report is not an unconditional release approval. Earlier compatibility snapshots describe their own historical scope.

### Install with Composer

Install Sillbeam in your Symfony application's root directory:

```bash
composer require websign-dev/sillbeam-core:^0.2.0
```

The current Core version is **v0.2.0**. The package is [registered on Packagist][composer-url]. The constraint `^0.2.0` allows compatible updates within the 0.2 release line; it requires the stable `0.2.0` version to be listed by Packagist. Package registration alone does not provide that version. To pin it exactly, use `composer require websign-dev/sillbeam-core:0.2.0`. See [release status](#release-status) for the tag check.

### Register Sillbeam

Register the bundle in your existing bundle array:

```php
// config/bundles.php
return [
    // Keep your existing bundles here.
    Sillbeam\Core\SillbeamCoreBundle::class => ['all' => true],
];
```

Create the route import. No `config/packages/sillbeam.yaml` is required for the defaults; create it only when configuring [options](docs/reference/configuration.md). If Flex has already enabled the bundle, keep its existing registration.

```yaml
# config/routes/sillbeam.yaml
sillbeam:
    resource: .
    type: sillbeam
```

<details>
<summary><strong>Install through Symfony Flex</strong></summary>

The [Flex recipe](https://github.com/WebSign-Dev/sillbeam-core/blob/main/flex-recipe/README.md) registers the bundle and creates the route import above when available through your Symfony Flex endpoint. Recipe availability is independent of Composer package installation. The recipe is technically complete and locally tested, but public Recipes Contrib approval is outstanding. The package is already registered on Packagist. A matching stable package version, Contrib acceptance and endpoint availability are required for automatic recipe installation; the v0.2.0 tag alone does not activate it. Consumers must allow Contrib recipes (`composer config extra.symfony.allow-contrib true`). If Composer/Flex does not install the recipe, register the bundle and route import manually as shown above.

</details>

With the usual `App\:` import of `src/`, exclude `Module` and `Project` from that native service import when Sillbeam should control their automatic activation. Keep existing exclusions and explicit services. The Flex recipe preserves your service configuration; it does not add these exclusions. See the [Symfony integration rules](docs/guides/getting-started.md#preserve-symfony-integration), including custom discovery roots and native routing/Doctrine imports.

Run `php bin/console sillbeam:doctor` after integration and review its action items
and `not verified` checks. If needed, manually select
`use Sillbeam\Core\Console\Application;` in the existing `bin/console`, preserving
the bootstrap and Kernel. The recipe never edits that file. After reviewing service
imports, create the first descriptor below and run `php bin/console sillbeam:architecture`.

### Create your first module

Run `php bin/console sillbeam:make:module Billing` to create the minimal descriptor, or create `src/Module/Billing/BillingModule.php` manually:

```php
<?php

namespace App\Module\Billing;

use Sillbeam\Core\Module\Attribute\SillbeamModule;

#[SillbeamModule]
final class BillingModule
{
}
```

1. **Organize the feature.** Put its services inside the module directory. Sillbeam derives the `billing` ID and its PSR-4 namespace.
2. **Expose routes and persistence.** Controllers under `Controller/` use ordinary Symfony route attributes. Entities under `Entity/` receive automatic mappings when DoctrineBundle is registered. `Entity/` and `Dto/` are excluded from automatic service imports by default.
3. **Inspect the architecture.** Build the container and review the discovered modules:

```bash
php bin/console cache:clear
php bin/console sillbeam:architecture
php bin/console debug:router
```

See the [getting-started guide](docs/guides/getting-started.md) for service discovery and integration details.

## Modules and Projects

Declare another module in `dependencies` whenever your module directly references its classes. Sillbeam checks declarations and observed static references, and reports invalid access with file and line information. Unused imports do not create dependencies. Dynamic class names, PHPDoc and string service IDs are outside this analysis.

Without Projects, all enabled shared modules are active. With Projects, each Project selects shared modules and automatically includes its enabled private modules; dependencies complete its graph. Unassigned shared modules remain in the catalog but contribute no automatic services, routes or entity mappings.

| Application layout | Use case |
| :--- | :--- |
| **Symfony only** | Keep a conventional Symfony application with no module or Project descriptors. |
| **Modules only** | Structure one application around enabled shared feature modules. |
| **Project only** | Use direct Project services, controllers and entities without modules. |
| **Single Project** | Group a surface and its private modules without requiring host configuration. |
| **Multiple Projects** | Compose surfaces with distinct hosts and shared or private modules. |
| **Hybrid** | Combine conventional Symfony code with module and Project directories. |

Projects share the container, database and event system. They provide architectural ownership and routing composition, rather than process or tenant isolation. Global subscribers and handlers are not automatically restricted to a Project's host.

Read the [module guide](docs/guides/modules.md) and [Project guide](docs/guides/projects.md) for dependency rules, activation and routing.

## Everyday workflow

Run these commands from your Symfony application's root:

| Command | Purpose |
| :--- | :--- |
| `php bin/console sillbeam:architecture` | Explain architecture findings and contextual next steps. |
| `php bin/console sillbeam:doctor` | Inspect static installation evidence; review unverified checks manually. |
| `php bin/console sillbeam:architecture --format=json` | Export the architecture catalog and dependency evidence. |
| `php bin/console sillbeam:report` | Write a standalone Markdown architecture report. |
| `php bin/console sillbeam:about` | Show application, runtime and module information. |
| `php bin/console debug:container` | Inspect Symfony service registration. |
| `php bin/console debug:router` | Inspect imported application routes. |
| `php bin/console doctrine:mapping:info` | Inspect entity mappings when DoctrineBundle is installed. |

Architecture errors block ordinary container-backed commands. With `Sillbeam\Core\Console\Application` selected in the existing bootstrap, the exact architecture, report and Doctor commands remain available before container compilation. Doctor recognizes conventional Runtime callbacks and stock Symfony 7.4/8.1 kernels statically; dynamic or ambiguous configuration remains `not verified`. Unknown routing does not hide independently proven service-import conflicts. The regular Symfony entry point can write cache files before Doctor runs. See the [CLI reference](docs/reference/cli.md) and [troubleshooting guide](docs/operations/troubleshooting.md).

## Documentation

| Guide | Contents |
| :--- | :--- |
| [Documentation overview](docs/README.md) | Guide index and starting points. |
| [Getting started](docs/guides/getting-started.md) | Installation, descriptors and native Symfony integration. |
| [Architecture](docs/architecture/overview.md) | Discovery, ownership, compilation and dependency boundaries. |
| [Modules](docs/guides/modules.md) | Descriptors, dependencies and activation rules. |
| [Projects](docs/guides/projects.md) | Shared and private modules, hosts and request context. |
| [Configuration](docs/reference/configuration.md) | Discovery roots, exclusions and integration settings. |
| [Public API](docs/reference/public-api.md) | Attributes, registries and runtime interfaces. |
| [Doctrine](docs/modules/doctrine.md) | Optional entity mapping integration. |
| [Migration](docs/migration/modular-monolith.md) | Breaking changes and replacing the former Foundation features. |
| [Testing](docs/development/testing.md) | Repository checks and integration coverage. |
| [Test applications](docs/development/test-apps.md) | Example layouts and the isolated consumer application. |

## Release status

Sillbeam Core **v0.2.0** is the finished, feature-frozen Core version, distributed as [`websign-dev/sillbeam-core`][composer-url]. The [changelog](CHANGELOG.md) and [release notes](docs/releases/0.2.0.md) describe its features, breaking changes and known limits, including the completed Doctor F01/F02 corrections.

The package is registered on Packagist. At the public metadata check on **2026-10-25**, the [GitHub tag list](https://github.com/WebSign-Dev/sillbeam-core/tags) contained `v0.1.0` and no `v0.2.0`; Packagist listed no package versions. Stable installation therefore depends on making the corresponding Git tag available to Packagist. Composer derives the version from that tag; no fixed `version` field is needed, and `0.2.x-dev` remains the development branch alias.

The only outstanding external integration is public approval of the locally tested [Symfony Recipes Contrib recipe](https://github.com/WebSign-Dev/sillbeam-core/blob/main/flex-recipe/README.md). Manual integration works independently of that approval. Finalizing this repository does not create a tag, publish a package version or submit the recipe.

See the [v0.2 migration guide](docs/migration/0.2.0.md) for the breaking package, namespace and bundle changes.

For reports even when the service container cannot compile, use `Sillbeam\Core\Console\Application` in the existing `bin/console` bootstrap. See [CLI reporting](docs/reference/cli.md) for setup, JSON schema and exit codes.

## Scope and migration

Sillbeam focuses on module architecture within Symfony. Services, HTTP handling and runtime behavior use Symfony's existing mechanisms. Applications supply their own business functionality and infrastructure integrations.

The former Foundation layer—including API, Security, User, entity bases, stores and infrastructure helpers—has been removed. This is a **breaking change**: replace existing usage according to the [migration guide](docs/migration/modular-monolith.md) before updating. Strata remains a future idea and is not a dependency.

## Development & support

From the Sillbeam repository root:

```bash
composer install
composer validate --strict
composer test
composer analyse
composer architecture
composer cs:check
composer test:consumer
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

The example applications cover six operating variants. The isolated consumer lives under `apps/test-app`; its setup is described in the [test-app guide](docs/development/test-apps.md).

Read [Contributing](CONTRIBUTING.md) for development setup, required checks and pull request guidance, [Support](SUPPORT.md) for assistance and [Security](SECURITY.md) for private vulnerability reporting.

When [reporting an issue][issues-url], include your PHP and Symfony versions, relevant Sillbeam configuration, the application layout and diagnostic output. Remove credentials from shared output.

## License

Sillbeam is licensed under the [MIT License](LICENSE).

---

<div align="center">

[Documentation](docs/README.md) · [Report an issue][issues-url] · [Back to top](#readme-top)

</div>

<!-- Navigation -->
[issues-url]: https://github.com/WebSign-Dev/sillbeam-core/issues
[releases-url]: https://github.com/WebSign-Dev/sillbeam-core/releases
[composer-url]: https://packagist.org/packages/websign-dev/sillbeam-core
[ci-url]: https://github.com/WebSign-Dev/sillbeam-core/actions/workflows/ci.yml

<!-- Badges: repository CI and requirements from composer.json -->
[composer-shield]: https://img.shields.io/packagist/v/websign-dev/sillbeam-core?label=Composer&logo=composer&logoColor=white
[ci-shield]: https://github.com/WebSign-Dev/sillbeam-core/actions/workflows/ci.yml/badge.svg
[php-shield]: https://img.shields.io/badge/PHP-%E2%89%A5%208.4-777bb4?logo=php&logoColor=white
[symfony-shield]: https://img.shields.io/badge/Symfony-7.4%20%7C%208.x-000000?logo=symfony&logoColor=white
[status-shield]: https://img.shields.io/badge/status-v0.2.0-57606a
[license-shield]: https://img.shields.io/badge/license-MIT-57606a
