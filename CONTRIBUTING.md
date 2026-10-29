# Contributing to Sillbeam

Bug reports, documentation improvements and focused pull requests are welcome. Sillbeam is a modular architecture layer for Symfony; contributions should preserve its integration with native Symfony services, routing and runtime behavior.

## Report a bug or propose a change

Search [existing issues](https://github.com/WebSign-Dev/sillbeam-core/issues) before opening a new report. Include:

- The Sillbeam commit or version, PHP version and Symfony version.
- A minimal reproduction, relevant configuration and module or Project layout.
- Expected behavior, actual behavior and the complete relevant error message.
- Architecture diagnostic output when the container can boot.

Remove credentials and private application data from shared configuration and logs. For larger features or changes to the public API, open an issue to discuss the intended behavior before implementation. The [release notes](docs/releases/0.2.0.md) describe the current scope and limitations. Report security vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## Set up a development checkout

Use PHP 8.4 or newer, Composer and the PHP extensions required by the locked dependencies. Enable `pdo_sqlite` for the persistence integration tests. PHP 8.4 is the minimum supported version; check compatibility when developing on a newer runtime.

```bash
git clone https://github.com/WebSign-Dev/sillbeam-core.git
cd sillbeam-core
composer install
git switch -c describe-your-change
```

If contributing through a fork, clone your fork instead. Use a descriptive branch name.

The six example application variants and the main fixtures use the root Composer autoloader. You do not need to start an external runtime to run the root test suite. The separate consumer under `apps/test-app` has its own dependencies and runtime; see the [test-app guide](docs/development/test-apps.md).

## Make a focused change

- Keep changes tied to one problem or feature and explain the resulting behavior.
- Follow the existing PSR-4 layout, strict typing and code style. Production classes use the `Sillbeam\Core\` namespace.
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

Review the resulting diff and rerun the affected checks. See [testing documentation](docs/development/testing.md) and the [CI workflow](https://github.com/WebSign-Dev/sillbeam-core/blob/main/.github/workflows/ci.yml) for coverage and the PHP/Symfony compatibility matrix. Local success on one combination does not establish that every matrix job passes.

## Code documentation and API reference

Write PHPDoc in English. Describe the contract and its reason, rather than repeating
native types or trivial method bodies. Document non-obvious parameters, return
semantics, expected exceptions, filesystem/container/output side effects and important
architecture constraints. Keep PHPStan-compatible collection types, array shapes and
`class-string` annotations; do not weaken them for the renderer.

Mark supported entry points, contracts and their input/output data classes with `@api`.
The [public API reference](docs/reference/public-api.md) defines this boundary.
Consumers should inject registry/context interfaces; their concrete services, discovery,
compiler, AST index, loaders, diagnostic presentation and Symfony integration helpers
are `@internal`. Public PHP visibility alone does not make a service supported API.
Inherited interface contracts need not be repeated on trivial implementations. Document
meaningful overrides and keep new internal declarations explicitly marked.

Build the navigable HTML reference from the repository root:

```bash
composer install
composer docs:build
```

Open `build/docs/api/index.html` in a browser. `phpdoc.dist.xml` labels the existing
contracts for the 0.3.0 documentation release and scans only `src/`. It renders public
and protected members of non-internal types; `@internal` classes/members are omitted.
Check that changed API contracts appear, their parameter/return types and exceptions
are readable, navigation links work, and internal service pages are absent.

phpDocumentor 3 is a development-only PHAR installed through `phpdocumentor/shim`.
Composer explicitly allows only `phpdocumentor/shim`; do not use a wildcard plugin
allowance. Its `phar-io/composer-distributor` dependency is a library and needs no
plugin allowance. The installer downloads
and verifies the upstream PHAR signature. The PHAR isolates documentation dependencies
from the application's Symfony constraints. Composer platform checks cover the installer,
not everything bundled inside the PHAR: run `vendor/bin/phpdoc --version` and the real
build as well. The installed 3.10 PHAR requires PHP `^8.1` (covered by this project's
PHP `^8.4` floor), DOM/libxml, SimpleXML/XML/XMLWriter, fileinfo, iconv, tokenizer,
Phar, zlib and the core date/filter/hash/JSON extensions. Keep mbstring enabled as
recommended upstream. The bundled Symfony components conflict with `ext-psr`; the
PHAR dependency isolation does not isolate PHP extensions. GnuPG must be available
for the installer's signature verification. Diagrams are optional and not required
for this HTML build. See the upstream
[installation requirements](https://docs.phpdoc.org/guide/getting-started/installing.html) and
[configuration reference](https://docs.phpdoc.org/guide/references/configuration.html).
No documentation tool belongs in `require` or application boot code.

Generated HTML (`build/docs/`), parser caches (`.phpdoc/`) and local overrides
(`phpdoc.xml`) are ignored. The Composer script explicitly uses the distribution
configuration for reproducibility. When changing visibility or removing API elements,
delete the generated `build/docs/api/` directory before rebuilding: phpDocumentor does
not remove obsolete HTML files. Run `composer test`, `composer analyse` and
`composer cs:check` after PHPDoc changes; annotations are inputs to static analysis.

## Submit a pull request

Target `main` and describe:

- The concrete problem and the behavior after the change.
- Relevant reproduction steps or examples.
- Checks you ran, their results and any checks you could not run.
- Compatibility implications, configuration changes or migration steps.

Keep commits understandable and the diff focused. Include documentation alongside implementation changes when users need it. Maintainers may request revisions before merging.

## License

Sillbeam is licensed under the [MIT License](LICENSE). By submitting a contribution, you agree that your contribution may be distributed under that license. Only contribute material you have the right to license this way.
