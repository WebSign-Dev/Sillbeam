# Independent release audit for v0.1.0 — 2026-10-12

## Source state and method

Starting point: `21e75cb3fad489882822faa7003f5e770dd3e6eb`, clean working tree. Verification covers this commit **plus the audit corrections listed below**, not the unchanged commit. Website: `c9e36e40f7796d39d48b5a56a4cacb58625672c0`. No publication, tags, pushes, merges or deployments. Audit corrections are delivered as a working-tree diff.

SHA-256 of the final runtime state (`src/**/*.php`, `composer.json`, `composer.lock`; sorted relative paths plus NUL-separated file contents): `8af0e44790b107d818a4e64a0686cd3ab9416d8388302a6bb37652bf6413087e`.

All 37 production PHP files, their integration, tests and release documents were reread. Two separate reviewers checked architecture/discovery/dependency analysis and DI/routing/Doctrine/kernel without the earlier success reports; corrections were also reviewed again. The second run uses an isolated copy without copied vendor directories or test caches, with a fresh `composer install` from the lockfile. Shared Composer download caches do not replace installed dependencies. Production code, Composer manifest and lockfile are byte-identical in both runs.

## Newly found and fixed

| Finding | Impact and correction | Evidence |
| --- | --- | --- |
| P1: Excluded discovery roots reentered | `excludes: [Module, Project]` was respected when scanning `src`, then bypassed by custom/overlapping roots. `SourceInspector` now checks ancestors within configured discovery boundaries and preserves excluded paths for downstream imports. | Four new tests initially red, then green. Descriptors, invalid excluded PHP files, services, routing and Doctrine checked. An additional regression test protects relative exclusions for applications under system directories of the same name. |
| P2: First architecture configuration does not invalidate the development container | After zero-config boot, the first `config/packages/sillbeam.yaml` with `enabled: false` was ignored in the warm development container. `SillbeamExtension` now monitors the configuration directory, including additions. | Reproduced in a standalone ZIP consumer; new integration test in separate PHP processes initially red, then 18 assertions green. File creation, modification and removal checked. |
| P2: Exactly excluded entity subdirectories included in parent mapping | Without `/**`, an excluded subdirectory was discarded during parsing but still loaded by the parent Doctrine mapping. Generated mappings now filter using the same source index as services and routing. Native overrides remain authoritative. | New integration test initially red, green after correction. Additional test confirms intentional native mapping of an excluded directory. |
| P2: Analysis with Symfony 7.4.0 fails | Its `TreeBuilder` has no default generic type. The return of `Configuration::getConfigTreeBuilder()` now documents the actual generated type `TreeBuilder<'array'>`. No runtime/signature change. | `composer analyse` with current PHPStan and Symfony 7.4.0 initially failed with `missingType.generics`, then passed. The type checker serves as the regression check here. |

Affected implementation: `src/Architecture/SourceInspector.php`, `src/DependencyInjection/SillbeamExtension.php`, `src/DependencyInjection/Configuration.php`. Tests: `tests/Unit/FlexibleDiscoveryTest.php`, `tests/Integration/SymfonySkeletonTest.php`, `tests/Integration/ModuleLayerTest.php`, new `tests/Integration/ContainerCacheTest.php`. `CHANGELOG.md` updated. No new required dependencies, product features or public runtime API changes.

## Two full acceptance runs

Environment: PHP **8.5.4**, Composer **2.9.5**, Symfony FrameworkBundle **8.1.6**, PHP-Parser **5.8.0**, DoctrineBundle **3.3.2**, ORM **3.7.0**, PHPUnit **12.5.35** from the repository lockfile.

| Command actually executed | Run 1 | Fresh run 2 |
| --- | --- | --- |
| `composer validate --strict` | Exit 0 | Exit 0 |
| `composer check-platform-reqs` | Exit 0 | Exit 0 |
| `composer test` | 93 tests / 432 assertions | 93 tests / 432 assertions |
| `composer analyse` | no errors | no errors |
| `composer architecture` | 0 violations / warnings / errors | identical |
| `composer cs:check` | 0 changes, 131 files | identical |
| `composer test:flex-recipe` | Exit 0, without Doctrine | identical |
| `SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe` | Exit 0 | Exit 0 |
| `composer test:consumer` | 12 installations, both kernels | identical |
| `composer test:release-package` | actual ZIP, documented Hello, both kernels, cold/warm dev/prod | identical |
| `composer audit` | no advisories | no advisories |
| `git diff --check` | Exit 0 | Exit 0 |

Deptrac additionally reports 104 references not covered by its layer rules; this is neither test coverage nor a claim of complete static architecture validation. Sillbeam module validation is supported by its own tests.

The six consumer layouts are Symfony-only, Modules-only, Project-only, Single-Project, Multi-Project and Hybrid, each with/without Doctrine and the standard/optional Sillbeam kernel. Consumers resolve their own dependencies (here Symfony 8.1.8, ORM 3.7.5), have their own Composer autoloaders and contain no PHPUnit/repository test classes.

Additionally, standalone applications were freshly installed **twice** from the actual Composer ZIP with a normal `App\` import and required area exclusions. Checked: zero-config, documented Hello service/controller, dev/prod cold/warm, actual CLI execution, activation/deactivation without service/routing/mapping leaks, understandable diagnostics for forbidden direct code access and persistence with Doctrine. Documentation commands actually executed: `cache:clear`, `sillbeam:about`, `sillbeam:architecture` (text/JSON), `debug:container`, `debug:router`, `debug:router hello` and `doctrine:mapping:info`.

Further independent processes twice check actual addition/modification/removal of modules, Projects and controllers, host changes, route name prefixes and request Project context. Development refreshes automatically; production receives the documented explicit cache rebuild. Local Flex endpoints validate installation, both kernels and removal, including preservation of application configuration.

## Version matrix

Each of the six combinations was checked **twice**:

| PHP | Symfony FrameworkBundle in quality checks | Per run | Fresh consumer version |
| --- | --- | --- | --- |
| 8.4.26 / 8.5.4 | 7.4.0 | 93 tests / 431 assertions, analysis/architecture/CS/validation/platform passed | 7.4.20 |
| 8.4.26 / 8.5.4 | 8.0.15 | identical | 8.0.15 |
| 8.4.26 / 8.5.4 | 8.1.8 | identical | 8.1.8 |

72 local quality commands and 48 additional installation commands were executed: per combination and run, Flex without/with Doctrine, twelve independent layout/Doctrine consumers and one actual ZIP/manual installation. All exited 0. Consumers check both kernels; Flex/ZIP also check cold/warm dev/prod caches. DoctrineBundle 3.3.2 / ORM 3.7.5. The initial blanket minimum resolution additionally functionally checked DoctrineBundle 3.3.0 / ORM 3.6.0, but used unsuitable older development tools.

Symfony components with independently released patch versions remain within the selected minor line; for example Config 8.1.5 with FrameworkBundle 8.1.8. PHP-Parser is 5.9.0 in freshly resolved matrix installations; 5.8.0 in the main lockfile.

Matrix archives do not contain the audit report added later: the archive test makes one assertion per entry, hence 431 rather than 432 assertions there. In the first main/copy comparison, this file was also missing from the copy; after alignment and another full run, both yield 432. There are no differing runtime results.

Historical Symfony 7.4.0 patch versions were used exclusively for functional minimum-version testing. `composer audit` of this deliberately old temporary installation reports **9 advisories in 6 Symfony packages**; it is not an approved production stack. Its fresh resolution explicitly requires `--no-security-blocking` in the disposable matrix copy. Release manifest, repository lockfile and normal consumer installations remain without this exception. The clean audit of the current lockfile must not be extrapolated to arbitrary older patch versions allowed by Semver.

## Performance and resources

Each measurement uses its own process, 10/50/100 modules with 22 PHP files each (220/1100/2200 files), chained dependencies and controller routes. PHP 8.5.4. Values from two current runs in milliseconds; local measurements, not guaranteed production budgets.

| Modules | Dev container cold / warm, run 1 → run 2 | Prod container cold / warm, run 1 → run 2 | Peak MiB dev / prod, run 1 → run 2 |
| --- | --- | --- | --- |
| 10 | 462.1 / 10.33 → 444.8 / 10.00 | 353.7 / 0.28 → 362.3 / 0.31 | 28 / 24 → 26 / 24 |
| 50 | 1405.6 / 45.43 → 1205.6 / 43.73 | 1052.2 / 0.36 → 1039.0 / 0.32 | 42 / 36 → 42 / 34 |
| 100 | 2244.7 / 90.67 → 2246.6 / 90.23 | 1922.5 / 0.35 → 1907.5 / 0.34 | 62 / 50 → 60 / 48 |

10 modules: parsing 87.12–92.02 ms, discovery after index 5.83–6.02 ms, compile 0.56–0.67 ms, analysis 0.76–0.81 ms, routing cold/warm 9.47–13.51 / 3.62–5.91 ms; 50 modules: parsing 372.30–394.04 ms, discovery after index 26.37–28.15 ms, compile 0.75–0.91 ms, analysis 5.17–5.35 ms, routing cold/warm 34.26–58.60 / 15.61–34.08 ms; 100 modules: parsing 726.78–750.12 ms, discovery after index 51.71–53.72 ms, compile 1.06–1.10 ms, analysis 15.17–16.04 ms, routing cold/warm 85.15–134.44 / 38.98–92.54 ms. The warm source index is 0.010–0.017 ms across all cases. Benchmark without Doctrine; warm boots run in the same process after shutdown with classes already loaded. These are not HTTP/FPM end-to-end latencies.

Parsing dominates discovery cost; the warm source index retained per build avoids repeated parsing. Analysis looks up modules linearly per file/reference and scales disproportionately, but remains small compared with parsing and container build in this measurement. Development freshness costs more as file counts grow; warm production containers remain under 1 ms in these measurements. No demonstrated reason for a speculative cache/compiler redesign. Complete individual values including discovery, compile, analysis and routing are in the local evidence archive.

## Documentation, package and website

README, changelog, security, MIT license, release notes, Composer metadata, public API and migration were compared with the code again. 118 local documentation links have existing targets. Foundation remains removed; Strata is neither an available replacement nor a required dependency. The actual ZIP is installable; internal plans, vendor/test caches and development applications are not shipped.

Website commit `c9e36e4` was installed in a fresh copy with `npm ci`: `npm test` passed twice (build with 23 pages, 12/12 tests); `npm run lint` and `npm run format:check` also passed. Browser navigation, German/English installation, language switching and canonical/OG metadata were rechecked; no browser warnings/errors in the prepared build. All 23 local routes return HTTP 200; internal page, asset and anchor targets exist.

The public website still serves the older state: generic `github.com` links, incorrect `benjaminoeffinger/sillbeam:@dev`, empty package configuration, an outdated renaming claim and a hydration error. These points are already corrected in the prepared website commit; its deployment was explicitly not performed here. This is an open external release coordination issue, not a new defect in the prepared website source state.

## Deviations, open prerequisites and limitations

- Initial sandbox attempts failed on network/localhost sockets. Repeated with appropriate execution permissions and passed. An initial source copy without `.git` could not run `git diff --check`; a local temporary Git baseline was then created and the actual check passed. A code style deviation in the new loop was corrected and both full runs repeated. The additional CLI harness needed to normalize Symfony line breaks; the expected dependency diagnostic was already correct. The temporary minimum matrix needed to separate exact resolution pins from valid Composer constraints; both validations passed after correcting lock metadata.
- A blanket `--prefer-lowest` installed PHPStan 2.0.0 and CS-Fixer 3.69.0. The old fixer cannot run PHP 8.4/8.5 checks; PHPStan also showed older inference issues. The Symfony minimum-version matrix therefore uses compatible current development tools. The original development-tool minimum is not represented as fully passed.
- GitHub metadata confirms: repository still **private**, no published releases. Packagist metadata and the Recipes Contrib package path return HTTP 404. Public access, package publication and recipe merge are pending. Private vulnerability reporting remains unverified.
- The current [GitHub CI run](https://github.com/WebSign-Dev/Sillbeam/actions/runs/38220380806) for `21e75cb` is `failure`; six jobs have **no executed steps**. The exact quota/limit cause was not reconfirmed by an annotation; no code defect is inferred from it. A successful public CI run for the final state remains unverified and is separately required after enabling public access.
- PHP 8.4.0/8.5.0, Symfony 8.0.0, Windows/macOS, FPM/OPcache deployment and external database servers were not tested. SQLite persistence was exercised. No claim of full WCAG compliance or production load capacity.
- Static validation covers supported actual PHP class references between module areas; dynamic class strings, PHPDoc, string service IDs and ordinary Symfony/direct Project areas remain outside it. All Projects share container, database and event system; no tenant/security isolation or automatic host scoping of global handlers.
- Broad native service/routing/Doctrine imports can intentionally bypass activation. Documented area exclusions and explicit Symfony overrides remain necessary. Production requires a cache rebuild for code, routing, Composer or configuration changes.

## Decision

**GO WITH LIMITATIONS for the corrected source state verified here.**

All mandatory repository checks passed in both final runs. The version matrix and independent installations confirm the technical core. No confirmed open P0/P1/P2 defects remain in the prepared production Sillbeam state. The four findings have been fixed and their evidence documented.

External prerequisites remain: public access, package publication, separate recipe merge, a verified confidential security reporting channel, public CI evidence and deployment of the already corrected website state. The old 7.4.0 simulation environment with known upstream advisories is explicitly not approved for production; the approved current lockfile audit is clean. This decision does not authorize publication as part of this assignment.

Local raw logs, exit codes, reproduction scripts, version lockfiles, source manifest and regression red/green evidence are delivered as `sillbeam-final-audit-evidence.zip` outside the release package. They substantiate the results stated here; historical reports were not reused as test evidence.
