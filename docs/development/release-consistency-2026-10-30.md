# Core v0.3.0 release consistency — 2026-10-30

Branch: `dev-v0.3.x`. Starting HEAD: `88add025006aa6328b1e588deda2e2223c1f1596`;
that merge was already present when this task started. The uncommitted Distribution
Cleanup from the preceding task was retained. This task adds documentation and
metadata corrections only. No source/API/CI changes or Git remote-write operations.

## Corrections

- Root Composer aliases now use **`dev-dev-v0.3.x`**, `dev-dev` and `dev-main`, each
  targeting `0.3.x-dev`. The obsolete `dev-dev-0.3.x` key is removed. Composer
  prefixes a non-numeric Git branch with `dev-`; its own VersionParser/ArrayLoader
  confirmed the spelling and all three aliases against `^0.3@dev`. See the official
  [branch alias contract](https://getcomposer.org/doc/articles/aliases.md).
  Keeping numeric aliases preserves development version constraints on the current,
  integration and main branches; aliases are not stable releases.
- `composer update --lock --no-install --no-interaction --no-scripts --no-plugins`
  updated only the root lock content hash. A targeted update of Core in the current
  test-app lock refreshed only its local Core package metadata: current aliases,
  path reference to the starting HEAD, the already-implemented JSON script and the
  preceding cleanup's archive-filter removal. No dependency versions changed, no
  requirements were bumped, and nothing was installed into that application.
- README, current CHANGELOG section, CONTRIBUTING, release notes, upgrade guide and
  current testing guide consistently state: **development → dev automatic CI →
  maintainer approval → main automatic CI → separately authorized release**.
  Integration PRs target `dev`. The source branch is `dev-v0.3.x`. The workflow has
  push triggers only for `main`/`dev`, plus manual dispatch, no PR/development-branch
  automatic trigger. It does not furnish an automatic pre-merge PR check.
- Current release descriptions say SVG is built directly in PHP, normally saved as
  `architecture.svg`, with automatic parent creation, direct module/Project filters
  and explicit replacement. Historical optional Graphviz setup is obsolete, not a
  current requirement or supported renderer selection. Mermaid remains explicit
  `--format=mermaid` raw STDOUT. Graph exits are analysis 0/1/2 after successful
  export, invocation/selection 3, output failure 4.
- Compact inspection is described as state/scope/Project context plus merged direct
  DEPENDS ON/USED BY rows; `-v` retains configuration, classes/paths, activation,
  references and original findings/context. Text layout compatibility is no longer
  promised to scripts. Unknown evidence stays unknown and Project/global activation
  remains distinct. No inspection JSON format was invented.
- CHANGELOG and release notes now include manual `composer docs:export-json`, its
  English `build/docs/api/sillbeam-core.en.json` output, schema v1 and shared
  phpDocumentor data. Contributor/reference/upgrade guidance confirms manual use,
  independent HTML generation, no translation/synchronization/website integration
  or release/production-export hook. CI tests can export temporary fixtures; they
  do not publish the production JSON artifact.
- Public guides label the original 2026-10-29 acceptance record as initial evidence,
  with the current release notes defining scope. Existing immutable repository
  links remain valid historical snapshots; current testing guidance is available
  directly in CONTRIBUTING rather than implying a frozen old guide is current.

## Historical evidence and workflow preservation

A dated block was prepended to `release-0.3.0-acceptance/README.md`. It clearly bounds
the original GO statement, the old branch/alias names and the subsequently removed
CI trigger, then points to current feature contracts and required approval order.
Removing this block reproduces the original report **byte-for-byte**. Original
counts, failures, performance measurements, commit IDs and raw logs/results remain
unchanged. Existing historical v0.2 files and the v0.2-and-earlier CHANGELOG sections
are byte-identical to their state at this task's start. Previously pending public
link corrections from Distribution Cleanup were retained, not silently reverted or
presented as new historical edits.

Workflow `.github/workflows/ci.yml` is byte-identical to task start:
Git blob `fff5249f69ea1107d9811b27056f1c3bd5a86e84`. Symfony YAML parsing confirmed
`on.push.branches == [main, dev]`, manual dispatch present and no PR trigger.
`src/` and public schemas/APIs are untouched. No CI, branch-protection or release
workflow was changed or triggered.

## Actual verification

| Check/command | Result |
| --- | --- |
| Composer VersionParser + ArrayLoader/AliasPackage for current/dev/main branches | All map to `0.3.x-dev` and satisfy `^0.3@dev` |
| Isolated path consumer: `composer update --no-install --no-interaction --no-scripts --no-plugins` | `^0.3@dev` resolves actual `dev-dev-v0.3.x` without a synthetic version override |
| Root and app lock semantic comparison | Root only content-hash; app only Core metadata; dependency versions unchanged |
| `composer validate --strict` | Valid |
| `composer validate --strict --working-dir=apps/test-app/app` | Valid |
| `composer check-platform-reqs` | Passed on PHP 8.5.4 |
| `composer test` | **635 tests, 8,357 assertions**, no failures/errors/skips |
| `composer analyse` | PHPStan level 8: no errors |
| `composer cs:check` | 0 of 205 files need fixing |
| `composer test:release-package` | Passed: actual ZIP, 77 identical runtime files, **197** packaged local links; Hello from installed docs, both kernels/cold-warm dev-prod, graph/inspection/Doctor |
| `composer docs:export-json` twice + `cmp` | Both successful, identical JSON bytes |
| `composer docs:build` | Successful; HTML index present, JSON unchanged after HTML |
| JSON decode + metadata check | Schema 1, package websign-dev/sillbeam-core, version 0.3.0, English, 136 public elements |
| Current handbook/root Markdown/HTML path check, including corrected acceptance README | **241** local targets checked, no broken paths |
| Workflow/hash and historical-data comparison | Passed |
| `git diff --check` | Clean |

CLI examples were compared with the actual command definitions and exercised by
existing tests: SVG default/explicit format/output/force/module/project, raw Mermaid,
normal/verbose/quiet module inspection, ambiguous selection, unknown evidence,
pre-container Core adapter and regular Symfony. Installation/generator/Hello guide
examples remain exercised by the full suite and real ZIP smoke. The documented
manual PHPDoc commands were also run directly. No new feature/test implementation
was required for this documentation task.

Independent read-only review found no actionable metadata/feature-contract
inconsistency. Its optional wording nit in the current testing guide was corrected
to call the 2026-10-29 report an initial record rather than a current matrix result.

## Remaining scope and limits

No unresolved contradiction remains in the current user-facing descriptions.
Old branch/trigger/renderer statements still occur intentionally in historical
records, now explicitly contextualized; raw evidence was not relabeled as current.
The initial historical GO does not certify refinements or grant release permission.
A new complete PHP-8.4/8.5 × Symfony-7.4/8.1 matrix and successful remote CI on a
final maintainer-approved commit were **not** claimed or triggered here. This task's
actual executions used PHP 8.5.4. No branch promotion, tag, publication, automatic
translation, website integration, commit, push, merge or release was performed.
External URLs and individual fragment anchors were not comprehensively crawled;
checks validate actual packaged file targets and current relative paths.

The first historical-preservation script used an incorrect newline boundary when
removing the added block and failed its equality assertion; correcting that check
confirmed the original body was always intact. Expected 404 logs from existing
negative HTTP tests and the PHP-CS-Fixer host-version warning remain, without test
failures. Earlier task reports retain their own measured snapshots and outcomes.
