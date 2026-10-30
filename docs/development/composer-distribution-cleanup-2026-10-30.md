# Composer distribution cleanup — 2026-10-30

Branch: `dev-v0.3.x`. Baseline: `3f891afbff43a0ab48af4ec5991f19aa59fe80b0`.
PHP 8.5.4; Composer archive ZIP inspected as actual file contents, not inferred from
configuration. No commits, remote writes, tags, merges, releases or CI changes.

## Distribution policy

Root `.gitattributes` is now the single archive policy, shared by Composer archive
and Git source exports. The competing `composer.json` archive.exclude section was
removed. The ordered allowlist denies unlisted root/hidden files, opens runtime and
public documentation roots, and excludes common development by-products even within
those roots. Explicit parent-directory entries support Git export; recursive rules
and an explicit hidden-root rule also cover Composer's filtering semantics.

| Group | Distribution |
| --- | --- |
| All 77 non-placeholder runtime files under `src/` | Included, byte-identical to checkout and baseline ZIP |
| `composer.json`, LICENSE, README, CHANGELOG, CONTRIBUTING, SUPPORT, SECURITY | Included |
| `docs/README.md`, architecture, assets, examples, guides, migration, modules, operations, reference, releases | Included: 46 documentation/asset/schema files |
| `docs/development/` | Excluded: all acceptance logs, raw benchmark/measurement streams, environment/result JSON, verification reports and helper scripts |
| `docs/superpowers/` | Excluded: internal designs and implementation plans, as before |
| `docs/adr/.gitkeep`, `docs/packages/.gitkeep` | Excluded: empty scaffolding |
| `src/Kernel/Configuration/.gitkeep`, `src/Kernel/Routing/.gitkeep` | Excluded: empty placeholders, not runtime code |
| tests, tools, apps, skeleton, Flex recipe source, vendor, root development configuration and generated output | Excluded, as before; unlisted future root/doc trees stay excluded |
| Hidden files/placeholders; `*.log`, `*.jsonl`, `*.tmp`, `*.bak`, `*.cache`, generated `reference.php` | Excluded even inside otherwise included trees |

Historical reports and raw data were not removed or rewritten in Git. Tracked files
under `docs/development/` and `docs/superpowers/` remain unchanged; this report and its
small inventory records are new development-only evidence and are excluded too.
No files under `src/`, runtime requirements or diagnostic implementations changed.

## Actual before/after ZIP

| Metric | Before | After |
| --- | ---: | ---: |
| Files | 576 | 130 |
| Uncompressed bytes | 4,782,892 | 2,706,907 |
| ZIP bytes | 2,821,890 | 2,266,792 |
| Runtime files verified identical | — | 77 |
| Included local documentation/image targets validated | — | 187 |

446 entries were removed from distribution: **442 development evidence files and
four placeholders**. The ZIP is 19.67% smaller; uncompressed content is 43.40%
smaller. Existing branding assets are intentionally retained because the public
README/handbook uses them. LICENSE is byte-identical before/after. The remaining
changed files are metadata/documentation, including fixed repository links.

Verified final artifact: `/tmp/sillbeam-distribution-after/sillbeam.zip`.
SHA-256: `d9738fa77e1a2b274c896c152bcb922515cc5f0de4c00c1ff2688a2c6313c726`.
[Full inventories, removed files and group sizes](composer-distribution-2026-10-30/contents.json)
record the inspected artifacts. Sizes describe these files, not a published release.

`git archive --worktree-attributes --format=zip --output=/tmp/sillbeam-distribution-after/git-head.zip HEAD`
produced the **same file manifest** under the same attributes. This comparison tests
filtering only: Git exports HEAD content, while Composer exports the current working
tree with corrected links. No commit was created to change that distinction. Link
validation and package installation used the actual current Composer ZIP.

## Documentation links

46 links across 14 publicly shipped Markdown files were updated to immutable
repository URLs at commit `3f891afbff43a0ab48af4ec5991f19aa59fe80b0`.
`git ls-remote --heads origin dev-v0.3.x` confirmed this exact published commit;
`git cat-file -e COMMIT:path` confirmed every referenced file/directory there.
See the [per-link record](composer-distribution-2026-10-30/links.json).

These links cover excluded historical acceptance/verification reports, developer
testing/test-app guides and recipe sources. Four recipe links were already broken
inside the baseline ZIP; they are corrected as part of this cleanup. Historical
release notes only had link destinations adjusted; recorded findings/results were
not rewritten. Links between shipped guides, schemas, examples, images and license
remain relative and work from the ZIP itself.

The shared test helper checks Markdown inline links, reference definitions and HTML
href/src targets against ZIP entries, including URL-decoding and rejecting traversal
outside the archive. It never falls back to files from the checkout. Literal fenced
examples and remote URLs are not mistaken for local targets. Fragment anchors and
every external HTTP endpoint were not independently crawled; the focus is actual
packaged file targets and verified immutable repository destinations.

## Executed checks

| Command | Actual result |
| --- | --- |
| `composer archive --format=zip --dir=/tmp/sillbeam-distribution-before --file=sillbeam` | Baseline inspected: 576 files |
| `vendor/bin/phpunit tests/Unit/PackageDistributionTest.php` before cleanup | Red: internal scaffolding/evidence still exported |
| `vendor/bin/phpunit tests/Unit/PackageDistributionTest.php` final | 1 test, 152 assertions; passed |
| `composer archive --format=zip --dir=/tmp/sillbeam-distribution-after --file=sillbeam` plus shared ZIP inspector | 130 files, 77 identical runtime files, 187 local links; passed |
| `composer test:release-package` final | Passed with Symfony 8.1.10: actual ZIP install, packaged Hello guide, no-dev/no Doctrine, both kernels, cold/warm dev/prod, SVG and inspection/Doctor checks |
| `SILLBEAM_SYMFONY_VERSION='7.4.*' composer test:release-package` | Passed with Symfony 7.4.22; same ZIP-content/link/runtime checks |
| `composer test:consumer` | 12 independent installs / 24 kernel scenarios with Symfony 8.1.10: six layouts, with/without Doctrine |
| `SILLBEAM_SYMFONY_VERSION='7.4.*' composer test:consumer` | 12 independent installs / 24 kernel scenarios with Symfony 7.4.22 |
| `composer test` final | **635 tests / 8,357 assertions**, no failures/errors/skips |
| `composer analyse` | PHPStan level 8 passed, including the new test helper |
| `composer architecture` | Deptrac: 0 violations, warnings or errors |
| `composer cs:check` | 0 of 205 files need fixing |
| `composer validate --strict` | Valid, unchanged lockfile remains consistent |
| `composer check-platform-reqs` | Passed |
| `composer audit --locked` | No security vulnerability advisories found |
| `git diff --check` | Clean |

Consumer installs additionally execute Composer strict validation, platform checks
and optimized strict-PSR autoload generation in each isolated application. Doctrine
scenarios resolved DoctrineBundle 3.3.4 and ORM 3.7.5. All runs used host PHP 8.5.4;
this task does not claim a PHP 8.4 matrix rerun. No Flex workflow or release automation
was changed or independently re-certified.

The assertion count is lower than the preceding feature task because the archive
test makes one exclusion assertion per actual packaged entry: 446 entries no longer
ship. No test was dropped/skipped; new corruption and link probes add coverage.

## Failures found and corrected

The first unmodified `composer test:release-package` failed with exit 255 at
`Packaged module inspection failed`: its old `State: active` expectation no longer
matched the already-implemented compact view. `consumer-smoke.php` likewise still
expected `ID:`. The harness now checks the current module header; no command changed.
The release smoke's former default graph call could also reuse an existing SVG;
it now writes explicitly inside the temporary consumer with --force for repeat boots.
Quickstart examples are now read from the **installed ZIP documentation**, not the
checkout, to test the artifact being distributed.

Early consolidation probes exposed Composer's special handling of hidden root
names and directory matching, then remaining runtime `.gitkeep` placeholders. These
were fixed in the one attribute policy and verified with real archives. Independent
review found no current-content leaks, but noted that logs/generated helpers inside
public documentation trees could bypass the allowlist. A real-file regression
reproduced this; final suffix/helper exclusions and negative probes now cover it.
Negative archives with corrupted source, excluded inline/reference/HTML link targets
and an injected internal log are all rejected by the shared inspector.

Expected 404 logging from existing `/archived` and `/backoffice` HTTP negative tests
remains. PHP CS Fixer notes that the host is PHP 8.5 while the minimum is 8.4. These
are not failing checks. No unresolved failure remains in the completed runs above.
