# Core v0.3.0 final release acceptance — 2026-10-30

**Recommendation: NO-GO for manual release approval of this commit.** All twenty required Flex smoke-test executions fail because their assertions still expect the retired module-inspection output. The implemented command returns the new documented view successfully, but the release gate is not green. No source correction or publication was performed.

## Exact scope and provenance

- Branch: `dev-v0.3.x`.
- Commit: `0b70c3143acf25cae5082f3ec6007b6d2a31da79`.
- Git tree: `3a6830cc4d7c41497df1892e527784e183c4fd23`.
- Checkout: `/home/oeffe/PhpstormProjects/websign/sillbeam-core`.
- Host: PHP **8.5.4**, Composer **2.9.5**; selected dependencies from the committed lockfile.
- No tracked changes at the start or end. The snapshot status includes only this newly created, untracked evidence directory because it was created before the status capture.
- All **879 tracked files** were fingerprinted. Final comparison reports **zero changed tracked files**, including historical v0.2 documentation, measurements and previous acceptance reports. Matrix copies differ only in `composer.json`/`composer.lock`, intentionally resolved for each Symfony line.

[Snapshot](snapshot.json), [source SHA-256 manifest](source-sha256.json), [final provenance](final-provenance.json), [machine-readable summary](summary.json).

The independent sub-reviews used separate evidence and temporary fixtures. They did not edit implementation, public tests, metadata or CI. The only repository additions are this report and its evidence. Docker QA containers were removed; no unrelated container was changed.

## v0.3.0 scope against implementation

| Area | Implemented and checked |
|---|---|
| Public PHPDoc | English API documentation, deliberate `@api`/`@internal`, phpDocumentor 3.10 as development tooling; HTML build passes |
| Installation and Doctor | Existing Flex integration, conservative static checks, repair diagnostics and first-module guide; no installer command or automatic application-file replacement |
| Doctor JSON v1 | Existing inspector results, text default, stable check IDs/statuses, clean JSON, exit-code priority and both CLI adapters |
| Module inspection | Existing report/identities, compact status/scope/problems, merged direct relationships, verbose evidence/configuration, Project selection and ambiguity errors |
| Architecture graph | Native PHP SVG default, direct module/project filters, recursive output directories, explicit force, raw Mermaid, safe labels and evidence-dependent activation |
| Manual API JSON export | `composer docs:export-json`, one English versioned file, public boundary, deterministic content; no translation/website/publishing hooks |
| Composer distribution | Single `.gitattributes` policy; runtime/public docs retained, development artifacts excluded from actual ZIP |
| Release consistency | Current versions/aliases, guides, release notes and upgrade notes agree with code and the required branch flow |

All four preceding refinement work packages are present in the source. Their shared integration is **not fully accepted**, because the Flex smoke script was not updated together with the inspection UX.

## Confirmed blocker and failed checks

**B1 — `tools/test-flex-recipe.php:470` rejects the current inspection format.**

The generated smoke test checks for `ID: hello` and `State: active`. The successful command actually prints `Module: hello — Active`, Shared scope, valid analysis and merged relationships. It consequently throws `Compiled module inspection failed`; the outer script reports `Consumer boot without package configuration failed` at line 484 and exits **255**.

This occurs in **all 4 matrix jobs × all 5 Flex variants = 20 failures**:

```sh
composer test:flex-recipe
SILLBEAM_TEST_FRESH=1 composer test:flex-recipe
SILLBEAM_TEST_EXISTING_ROUTE=1 composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
SILLBEAM_TEST_FRESH=1 SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

The failure is a regression in the test contract, not a demonstrated runtime boot failure: the kernel reaches a successful module inspection. However, later smoke steps (the second module, compiled Mermaid, HTTP route, Doctrine mapping and subsequent configured/kernel/environment passes) are **not reached in those failing runs**. Their success must not be inferred from earlier installation/Doctor repair success or from separate consumer tests.

[First original failure log](logs/php84-sf81-flex-default.log), [recorded-before-correction findings](BLOCKERS.md), [all commands and logs](COMMANDS.md).

**E1 — QA image prerequisite:** the four existing Docker images initially lacked `gpg` (probe exit **127**). GnuPG was installed only in disposable QA containers; subsequent probes and dependency resolution passed with the allow-listed phpDocumentor shim plugin enabled. This is an environment setup issue, not a product dependency change. Original failures and provisioning commands are retained.

No other recorded test/build/audit command failed. Intentional CLI error cases are successful acceptance probes only when their actual exit equals the recorded expected exit; they are not counted as successful architecture analyses.

## Complete local compatibility matrix

All four jobs were executed, without skipping a Flex variant after its predecessor failed. Source copies came from the fingerprinted tracked files, not an unrelated checkout. Each job ran `php tools/prepare-compatibility.php` and `composer update --with-all-dependencies --no-scripts --no-interaction --prefer-dist`; resolved package versions are retained in `php*-dependencies.json`.

| PHP | Symfony | PHPUnit | Assertions | Analysis / architecture / CS | HTML / JSON docs | Consumer / release ZIP | Flex |
|---|---|---:|---:|---|---|---|---|
| 8.4.26 | 7.4.22 | 635 passed | 8,363 | Pass | Pass | Pass / Pass | 5 failed, exit 255 |
| 8.4.26 | 8.1.10 | 635 passed | 8,223 | Pass | Pass | Pass / Pass | 5 failed, exit 255 |
| 8.5.11 | 7.4.22 | 635 passed | 8,363 | Pass | Pass | Pass / Pass | 5 failed, exit 255 |
| 8.5.11 | 8.1.10 | 635 passed | 8,223 | Pass | Pass | Pass / Pass | 5 failed, exit 255 |

No PHPUnit failure or skip is reported in these suite results. Counts above are the actual outputs, rather than a reused assertion count from a different dependency resolution.

Executed in **each** job:

```sh
composer validate --strict
composer check-platform-reqs
composer audit --locked
composer audit --locked --working-dir=apps/test-app/app
composer test
composer analyse
composer architecture
composer cs:check
composer docs:build
composer docs:export-json
# all five Flex variants listed above
composer test:consumer
composer test:release-package
```

Validation, platform checks and both audits passed in all jobs; audits found no advisories. These are also the two tracked Composer lockfiles in this repository. Audits describe the advisory state at execution time, not future security status.

Each consumer run independently resolves **6 layouts × with/without Doctrine = 12 installations**, exercising both Symfony and Sillbeam kernels. Across the matrix: **48 installations and 96 kernel executions**, all passed. Layouts: Symfony-only, modules-only, Project-only, single-Project, multi-Project and hybrid. Doctrine cases resolved DoctrineBundle 3.3.4 / ORM 3.7.5. Each matrix job also passed the real ZIP/no-dev installation test.

The host additionally passed strict root/app validation, platform checks, both audits, the complete **635-test / 8,357-assertion** suite, PHPStan, Deptrac, CS, docs and release-package checks using the original committed root lockfile.

The machine ledger contains **142 executed command records: 118 exit 0, 20 exit 255, 4 initial exit 127**. Sub-review/manual CLI probes have their own retained command records. The Python runner deliberately continues after failures; its process completion is not used as proof of a green test. Individual command exit codes are authoritative.

## Realistic CLI and diagram acceptance

[Independent CLI report](agent-cli/README.md), [fixture source](agent-cli/fixture-runner.php.txt), [expected/actual command results](agent-cli/results.json), [SVG](agent-cli/architecture.svg), [rendered screenshot](agent-cli/architecture-preview.png), [raw Mermaid](agent-cli/mermaid.stdout.txt), [compact inspection](agent-cli/standard.stdout.txt), [verbose inspection](agent-cli/verbose.stdout.txt).

The realistic fixture contains **14 modules, 32 direct declared edges and 2 Projects**, including a shared User and private User modules in both Projects. It uses the existing analyzer/report and observed PHP reference evidence. Actual SVG is XML-valid (1540 × 1599), was rendered in Chrome and visually inspected: readable IDs, scope boxes, Project context, arrowheads and legend, with no observed clipped labels. Chrome was only an acceptance viewer, never a renderer requirement.

Confirmed: default SVG writes the file and confirms its absolute location; module filtering creates nested missing directories; Project/descriptor selection disambiguates private identities; Mermaid is raw STDOUT; quiet SVG emits no confirmation; existing target refusal returns 4, explicit force succeeds, invalid output returns 4 with explanatory STDERR, ambiguous selection returns 3. SVG succeeds with an **empty PATH**, so missing Graphviz is not an error in the current native-renderer implementation. Historical missing-dot requirements are obsolete.

Additional focused graph/inspection/production-console suite: **69 tests / 1,279 assertions**, exit 0. It covers both real CLI adapters, valid/invalid/incomplete analyses, cycles, disabled/unknown activation, Multi-Project collisions, direct filters, escaping, no process functions, help/quiet/options, safe writes and concurrency/no-clobber behavior. The large custom fixture directly uses CommandTester; actual adapters are covered separately by these integration tests.

Dense graphs retain every relationship but can have many crossings and long lanes. No crossing-minimization or unbounded large-graph usability guarantee is claimed; direct module filtering remains the focused view.

## API, JSON and production runtime

[Independent compatibility evidence](agent-api/README.md).

- Reflection comparison of all **20 documented `@api` types** against v0.2.0 `cf2f410`: native public/protected signatures, properties, constants, inheritance/interfaces/traits and flags unchanged. Runtime Composer `require` maps are unchanged.
- Architecture JSON schema is byte-identical to v0.2.0; Doctor JSON schema v1 is byte-identical to its introduction commit `ac8118d`. Tests cover serialization/status/exit behavior; schema equality alone is not treated as proof of every third-party consumer.
- Of the 70 baseline source files, 65 have identical executable token sequences. Changed baseline files are ArchitectureReport (presentation metadata), Console Application, DoctorCommand, InstallationInspector and DI command registrations. Analyzer, compiler, discovery, parser/index, rules, runtime registries/loaders and lifecycle retain identical executable tokens.
- Additional runs: lifecycle/report/Doctor JSON **44 tests / 1,313 assertions**; architecture/rule/report units **45 / 171**; Doctor aggregation **2 / 51**; extended fresh-process HTTP probe **1 / 19**. All exit 0.
- Cold non-debug production HTTP returns 503; separately warmed HTTP returns 200 and uses compiled state even after invalid source is introduced. In both fresh subprocesses ArchitectureAnalyzer, ParserFactory, SourceInspector, SvgGraph and ModuleInspectionPresentation remain **unloaded**. This demonstrates no new source analysis or graph rendering on the tested production HTTP paths.

Current-source benchmark phases were also executed individually for **10, 50 and 100 modules in dev/prod**: all six passed; see [phase data](performance-phases.json). A current 100-module comparison used fresh build processes and three alternating Symfony/Sillbeam HTTP batches of 200 warm requests each. All six batches returned the expected route responses with analyzer/parser unloaded. [Raw measurements](performance-current.json) retain all timings; observed warm medians were approximately 4.499 ms (Symfony) and 4.234 ms (Sillbeam).

These timings were measured alongside other acceptance jobs and are **not a historical regression verdict or improvement claim**. Historical benchmarks are unchanged reference material. No comparable fresh v0.2.0 paired run or formal performance budget was evaluated. The original aggregate `--phases` command exited 0 but its redirected log did not preserve six independently parseable rows; the subsequent six direct `--phase-worker` executions supply the usable evidence. Its original log remains intact.

## Documentation builds and manual JSON export

phpDocumentor **3.10.0** HTML and JSON builds pass on the host and in all matrix jobs. HTML remains under `build/docs/api/index.html`; JSON under `build/docs/api/sillbeam-core.en.json`.

The actual host JSON was parsed and validated with Draft 2020-12 against `docs/reference/phpdoc-api.schema.json`: schema version 1, package `websign-dev/sillbeam-core`, version `0.3.0`, language `en`, **136 public elements**, unique sorted IDs. Public class/method probes succeed; internal renderer/inspection/inspector implementations are absent. Existing export tests additionally cover formatting, signatures, examples, Unicode/escaping and internal exclusion.

Two host exports are byte-identical: **176,199 bytes**, SHA-256 `b14376d08d34a4f35d3d4470b68267c8467e6acb04eafbfe304e46fedc38039b`. [Reproducibility](phpdoc-reproducibility.json), [schema validation](phpdoc-validation.json). The documented phpDocumentor generic-`@example` limitation remains; unavailable source content is not invented. No translation, synchronization, website integration or publishing was attempted.

## Actual Composer distribution and no-dev installation

[Independent package inventory](agent-docs/package-inventory.json), [actual ZIP](artifacts/sillbeam-core-0.3.0.zip), [host installation log](logs/host-release-package.log).

- **130 files**: 77 byte-identical runtime source files, 46 public documentation files and 7 root metadata/license/public-doc files.
- ZIP **2,269,714 bytes**, uncompressed content **2,714,066 bytes**.
- SHA-256 `047c22812451c38757f89a23f6356c83e2a38e192ecbe4c4ed050f471f682f42`.
- All **197 package-local documentation links** resolve against ZIP content alone.
- No development/acceptance reports, raw measurements, tests, tools, apps, CI, cache/log/temp files or root lockfile in the package. Historical artifacts remain unchanged in Git.

`composer test:release-package` passed on the host and all four matrix jobs. It installs the real ZIP with **`composer install --no-dev`**, no Doctrine or development tooling, reads Hello examples from installed documentation and exercises both kernels, cold/warm dev/prod, HTTP route, SVG/inspection and Doctor. This no-dev ZIP case is separate from the with/without-Doctrine consumer matrix. No claim is made that Composer archives are byte-reproducible across timestamps; exact content and source-byte identity were checked.

## Release documentation, CI and remaining limits

[Independent documentation review](agent-docs/README.md): current README, CHANGELOG, CONTRIBUTING, release/upgrade notes and CLI references match implemented behavior. **197 local path targets** resolve, and **16 unique immutable repository targets** exist at their cited Git objects. External HTTP availability and every Markdown fragment anchor were not comprehensively crawled.

Composer aliases `dev-dev-v0.3.x`, `dev-dev`, `dev-main` resolve to `0.3.x-dev`; the compatible Flex recipe remains at 0.2. Required process is **dev-v0.3.x → dev with automatic CI → maintainer approval → main with automatic CI → expressly authorized release**.

The workflow was read-only checked: push branches exactly **`main`, `dev`**, plus manual dispatch, no PR trigger. Git blob remains `fff5249f69ea1107d9811b27056f1c3bd5a86e84`. No CI/workflow edits, commits, pushes, merges, tags, releases or Packagist publication occurred. Remote GitHub Actions was not triggered or verified; a local matrix does not replace Actions on the subsequently approved commit.

Remaining risks are the **blocking outdated Flex test**, unexecuted later Flex steps, dense-graph layout limits, representative rather than exhaustive HTTP/configuration probes, limited external-link checking and absence of a comparable new historical performance run. No additional functional regression was demonstrated by completed checks.

**Required next step for GO:** update only the stale Flex expectations to the current inspection contract, commit that separately when authorized, then rerun all affected Flex variants and release acceptance against the new exact commit. The current report does not certify that hypothetical correction. Final release authorization remains solely with the maintainer.
