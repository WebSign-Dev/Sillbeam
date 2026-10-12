# Contributing to Sillbeam

Bug reports, documentation improvements and focused pull requests are welcome. Sillbeam is a modular architecture layer for Symfony; contributions should preserve its integration with native Symfony services, routing and runtime behavior.

## Report a bug or propose a change

Search [existing issues](https://github.com/WebSign-Dev/Sillbeam/issues) before opening a new report. Include:

- The Sillbeam commit or version, PHP version and Symfony version.
- A minimal reproduction, relevant configuration and module or Project layout.
- Expected behavior, actual behavior and the complete relevant error message.
- Architecture diagnostic output when the container can boot.

Remove credentials and private application data from shared configuration and logs. For larger features or changes to the public API, open an issue to discuss the intended behavior before implementation. The [release notes](docs/releases/0.1.0.md) describe the current scope and limitations. Report security vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## Set up a development checkout

Use PHP 8.4 or newer, Composer and the PHP extensions required by the locked dependencies. Enable `pdo_sqlite` for the persistence integration tests. PHP 8.4 is the minimum supported version; check compatibility when developing on a newer runtime.

```bash
git clone https://github.com/WebSign-Dev/Sillbeam.git
cd Sillbeam
composer install
git switch -c describe-your-change
```

If contributing through a fork, clone your fork instead. Use a descriptive branch name.

The six example application variants and the main fixtures use the root Composer autoloader. You do not need to start Docker or Harbor to run the root test suite. The separate consumer under `apps/test-app` has its own dependencies and runtime; see the [test-app guide](docs/development/test-apps.md).

## Make a focused change

- Keep changes tied to one problem or feature and explain the resulting behavior.
- Follow the existing PSR-4 layout, strict typing and code style. Production classes use the `Sillbeam\` namespace.
- Add regression coverage for bug fixes and meaningful tests for changed behavior. Use unit tests for discovery and dependency rules, and integration tests for container, routing or persistence behavior.
- Preserve conventional Symfony applications, module-only applications, optional Projects and operation without Doctrine where relevant.
- Update the guides, configuration reference and public API documentation when behavior changes. Explain breaking changes in the [migration guide](docs/migration/modular-monolith.md).
- Avoid unrelated dependency upgrades and generated cache or vendor files. Update Composer lockfiles when dependency changes require it.

The [architecture overview](docs/architecture/overview.md) explains ownership and compilation. The [public API reference](docs/reference/public-api.md) describes the supported attributes and runtime interfaces.

## Run the checks

From the repository root:

```bash
composer validate --strict
composer test
composer analyse
composer architecture
composer cs:check
composer test:consumer
composer test:release-package
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

| Check | What it verifies |
| :--- | :--- |
| `validate --strict` | Composer metadata and lockfile consistency. |
| `test` | Unit and integration behavior, including application layouts and persistence. |
| `analyse` | PHPStan static analysis. |
| `architecture` | Deptrac rules for the library's own dependency layers. |
| `cs:check` | The repository's PHP CS Fixer rules. |
| `test:consumer` | Independent installations of all six layouts, both kernels, with/without Doctrine. |
| `test:release-package` | Actual Composer ZIP installed in a separate manual consumer, including the documented Hello example. |
| `test:flex-recipe` | Recipe installation/removal and both consumer kernels; the environment flag also checks Doctrine. |

The Flex check needs Composer network access and permission to open a local HTTP port. Report an environment limitation explicitly if it prevents a check from running.

To apply the configured formatting rules:

```bash
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --allow-unsupported-php-version=yes --sequential
```

Review the resulting diff and rerun the affected checks. See [testing documentation](docs/development/testing.md) and the [CI workflow](https://github.com/WebSign-Dev/Sillbeam/blob/main/.github/workflows/ci.yml) for coverage and the PHP/Symfony compatibility matrix. Local success on one combination does not establish that every matrix job passes.

## Submit a pull request

Target `main` and describe:

- The concrete problem and the behavior after the change.
- Relevant reproduction steps or examples.
- Checks you ran, their results and any checks you could not run.
- Compatibility implications, configuration changes or migration steps.

Keep commits understandable and the diff focused. Include documentation alongside implementation changes when users need it. Maintainers may request revisions before merging.

## License

Sillbeam is licensed under the [MIT License](LICENSE). By submitting a contribution, you agree that your contribution may be distributed under that license. Only contribute material you have the right to license this way.
