# Release package preparation — 2026-10-12

> Historical, completed verification snapshot. Installation, publication and security-channel status describe the dated run, not the current release. For v0.1.0, use `composer require websign-dev/sillbeam:^0.1.0`; see the [release notes](../releases/0.1.0.md) and [Flex guide](../guides/flex-recipe.md).

Goal: a prepared, unpublished release candidate **v0.1.0** based on `051dbda`. No tag, push, merge, GitHub release, Packagist upload or live deployment. The runtime API and dependency versions remain unchanged.

## Package and Flex

`composer.json` now declares `symfony-bundle`; the homepage is the product website. Symfony requires this type for bundle installation and prohibits semantically empty recipe files under `config/packages`: [bundle requirements](https://symfony.com/doc/current/bundles/best_practices.html#installation), [recipe checklist](https://github.com/symfony/recipes/blob/main/README.rst#recipe-checklist). The recipe registers the bundle and installs only the route import. Default configuration works without `config/packages/sillbeam.yaml`. Two failing regression tests were recorded before correction.

The independent Flex test validates zero-config boot, both kernels, cold/warm development/production caches and installation/removal without/with Doctrine. Removal preserves native services and manually created configuration; after explicitly cleaning up application Sillbeam options and reverting to the native kernel, the application still boots. The native application requires its own Framework/Console/YAML dependencies. No global development stability flags are needed; `dev-main` is requested explicitly.

`composer test:release-package` installs the actual Composer ZIP in an isolated manual Symfony application without Flex or Doctrine. A version `0.1.0` simulated exclusively in the test does not replace a Git tag. PHP snippets and the native service import are taken from the current getting-started guide; the Hello route, autowiring and both diagnostic commands pass with both kernels and cold/warm `dev`/`prod` caches. Consumer Composer metadata, PSR-4 and platform requirements are also validated.

PHP `^8.4`, Symfony `^7.4 || ^8.0`, PHP-Parser `^5.7` and optional Doctrine integration remain. No new required package. Strict validation confirms consistency of the existing lockfile; type/homepage/scripts/archive rules require no version or lock-hash change. Composer stability and package metadata were checked against the [official schema documentation](https://getcomposer.org/doc/04-schema.md).

## Distribution and security checks

An actual Composer ZIP contained the completed internal development plan despite the Git export rule. A new regression test creates an internal sentinel and checks the actual archiver; it failed before the additional Composer exclusion. `docs/superpowers` is now explicitly excluded and the completed plan removed. Two generated Symfony `reference.php` files were removed from version control and are now ignored; locally generated test files remain working artifacts. Technical measurements and compatibility evidence are preserved; older audits are marked as completed historical snapshots linking to current installation/release notes.

The archive test validates runtime, license, security policy and getting started, and excludes tests, tools, examples, development plans, `.env`, caches, reference files and lockfile. Current public texts contain no personal `/home` or `/Users` paths. Example paths such as `/absolute/path/to/sillbeam` are explicitly placeholders.

`composer audit --locked --no-dev --format=json` and `composer audit --locked --format=json` reported no advisories or abandoned packages. MIT license, copyright and Composer license declaration are consistent. Signature scan of all 44 reachable commits: no matches for private keys, GitHub tokens or AWS access key formats. This is not a guarantee for every secret format. Currently committed test `.env` files contain development defaults and an empty `APP_SECRET`; old test values remain in history. External reuse cannot be ruled out locally and may require rotation outside this repository.

`SECURITY.md` explicitly mentions GitHub private reporting only when available. Its activation is not verified. The published general contact `sillbeam@websign.de` is only a way to request a confidential reporting channel, not an invented security mailbox service or response-time promise.

## Documentation and website

README, getting started, Flex/configuration references, changelog and prepared release notes align with bundle plus routing without empty configuration. Native `App\` service imports and their Module/Project exclusions remain explained. Projects provide no tenant/security isolation; dynamic class strings, PHPDoc, string service IDs and code outside module areas remain outside static dependency analysis. Website and GitHub entry points link to each other appropriately.

The actual website code is in the available repository [WebSign-Dev/Sillbeam_homepage](https://github.com/WebSign-Dev/Sillbeam_homepage), rather than the outdated Nexum homepage checkout. Targeted local corrections: real repository/issue links, correct package name, executable clone/Composer instructions, release status and Flex/import rules in German/English; canonical/Open Graph/robots/sitemap for 23 prerendered pages and client navigation. A demonstrated SSR/client hydration error was fixed by matching compiler comment options. `npm test` now automatically builds its required integration artifacts; the previously observed ENOENT error in a clean checkout is fixed. Branding, logo and CSS concept were preserved.

Browser checks on the local production preview: desktop 1280 px, tablet 768 px, smartphone 390 px, with no horizontal overflow; the mobile menu opens/closes and navigates to documentation, the keyboard skip link focuses `main`, light/dark color schemes work. Representative light-theme text contrasts range from 4.89:1 to 15.6:1; no broken images in the checked entry page. Fresh browser run after the hydration fix: no warnings/errors. No full WCAG certification, quantified CLS or load evidence. The live site was not changed; local corrections apply there only after a separately approved deployment.

## Checks performed

- Full Composer check sequence: `validate --strict`, `test`, `analyse`, `architecture`, `cs:check`, `test:flex-recipe`, `test:consumer`, `test:release-package`; additionally Flex with Doctrine and `git diff --check`.
- 85 PHPUnit tests / 397 assertions under PHP 8.5.4/Symfony 8.1.6 at the working state. Archive assertions count actual files and can vary with documentation scope.
- The same 85 tests also under PHP 8.4.26 and PHP 8.5.4, each with Symfony 7.4.0, 8.0.15 and 8.1.6; the minimum Symfony line was actually tested with the lowest resolvable dependencies.
- Twelve independent consumer installations: six operating modes without/with Doctrine, both kernels, PHP 8.5.4/Symfony 8.1.8.
- Website: production build with 23 pages, 12 tests, ESLint, Prettier, diff check and static Premium audit; additional actual browser checks as above.

Exact minimum PHP patch versions 8.4.0/8.5.0, Symfony 8.0.0 and fresh PHP 8.4 Flex installations were not run in this step. External prerequisites remain before publication: Packagist/recipe availability, verified private vulnerability reporting, rotation of reused historical test values if applicable, and separately approved website publication. None of these external measures was represented as completed.
