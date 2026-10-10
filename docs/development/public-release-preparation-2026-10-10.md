# Public release preparation — 2026-10-10

The proposed first public version is **0.1.0**. This preparation changes documentation, distribution rules and package metadata; the architecture, production source and runtime constraints remain unchanged. Changelog and release notes are drafts until publication. No release date, tag, push or publication was created.

## Documentation and metadata

README distinguishes local `dev-main` installation from future Packagist `^0.1` installation. The quickstart contains actual service/controller code and explains native imports; Project/Module IDs, host syntax, active dependency graphs, Registry return types, optional Doctrine and both kernels match source and tests. Examples remain ordinary Symfony/Doctrine code.

Composer name `websign-dev/sillbeam`, namespace `Sillbeam\`, MIT license, `0.1.x-dev` branch alias and PHP `^8.4` / Symfony `^7.4 || ^8.0` requirements are consistent. Source/docs/issues URLs match the configured Git remote. Unnecessary root development stability preferences were removed; `composer update --lock` only changed hash/stability metadata, preserving all locked dependency versions. Consumers do not inherit dependency-package development requirements or scripts.

The `0.1/` Flex recipe is prepared independently of package publication. The skeleton is explicitly documented as a directory template with a future `^0.1` dependency, not a runnable or separately published create-project package. No bootstrap feature was added.

## Repository and distribution hygiene

Internal superseded evolution reviews/plans, personal path references and an unused roadmap image were removed. Current technical/compatibility evidence remains available. Local IDE/cache/generated reference files are not publication inputs. The new Composer archive allowlist ships `src/`, public `docs/`, Composer metadata, README, CHANGELOG, LICENSE, CONTRIBUTING, SUPPORT and SECURITY. Git export exclusions also omit apps, tests, tools, skeleton, recipe, lockfile and local development artifacts. README/public links to recipe/skeleton/CI use repository URLs where those files are intentionally outside the library distribution.

The first local archive inspection exposed that directory-only exclusions were insufficient for the local export; explicit Composer selection and recursive Git exclusions were added and the resulting ZIP inspected. The final ZIP contains 83 files (approximately 3.4 MB uncompressed), including all 37 production PHP files. No vendor tree, tests, apps, IDE files, caches, local credentials or development lockfiles are shipped.

A targeted signature scan of current tracked text and all 42 reachable historical commits found no private-key, GitHub-token or AWS-access-key signatures. This is not a guarantee for every possible credential format. A tracked development APP_SECRET was removed from the test consumer; public local database values remain explicitly labelled as test-only. That APP_SECRET and older personal paths remain in Git history. If any example credential was reused outside the isolated development environment, replace it there before making history public. No history rewrite was performed.

## Final verification

- PHPUnit: 74 tests / 263 assertions; PHPStan: no errors; Deptrac: 0 violations/warnings/errors (102 uncovered external references); code style: no changes required.
- Strict Composer validation for package and skeleton, platform requirements and optimized strict PSR-4 autoload generation.
- Twelve fresh independent consumer installations on PHP 8.5.4 / Symfony 8.1.8, covering all six layouts, with/without Doctrine and both kernels.
- Flex recipe installation without/with Doctrine, each using both kernels.
- Actual clean Composer ZIP installed in a separate Symfony consumer using a local package repository and a **test-only simulated 0.1.0 version**. The documented Hello module/service/controller, route, autowiring and both CLI commands are exercised with the standard kernel and SillbeamKernel. This does not create a tag or prove Packagist availability.
- Local Markdown links and archive inventory checked. The previous [six-cell compatibility matrix](package-compatibility-2026-10-10.md) remains the cross-version evidence; unchanged runtime code was not redundantly re-tested in all six cells during this documentation pass.

## Publication decisions still required

1. Confirm the proposed 0.1.0 version and publication date; tag only after release approval.
2. Enable GitHub private vulnerability reporting before opening the repository, or provide another verified private contact channel.
3. Review whether historical test credentials were reused; rotate such external credentials if applicable. The public local test database configuration is not production configuration.
4. Register the package/repository on Packagist and decide when to submit the prepared recipe to Symfony Recipes Contrib. Manual installation works independently.
5. Re-run GitHub workflows after public access is enabled. Local tests do not claim those remote workflows have run.

There is no independently published skeleton in this release. No remaining reproduced runtime or installation blocker was found in the final local checks.
