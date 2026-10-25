# Markdown presentation verification — 2026-10-25

This task improves the standalone Markdown document. It does not supersede the
independent release acceptance or fix its known Major M1.

## Changes

- `src/Architecture/Console/MarkdownCatalog.php`: Markdown-only display labels
  from real descriptor identities; private Project qualification, actual ID
  disambiguation and deterministic local ordinals for invalid duplicate IDs.
  Unresolved targets remain explicit. Referenced classes are distinguished from
  architecture labels with `(class)` when necessary. Display labels are never
  used as executable PHP references.
- `src/Architecture/Console/ReportRenderer.php`: Architecture Summary, compact
  Architecture Overview, all Diagnoses & Resolutions, separate Technical Evidence
  and opt-in informative coupling appendix. Long lists expand outside tables;
  only individual very long labels use list blocks, keeping other rows compact.
  Known relative locations serve the explanations; original classes, paths,
  messages, severities, rule codes and complete finding contexts remain technical
  evidence. The explanation is not repeated there.
- `src/Architecture/Console/ArchitecturePresentation.php`: additional internal
  scalar fields retain canonical engine prose, ordered cycle module identities
  and coupling source identity. Markdown can therefore disambiguate equal names
  without trying to reverse the CLI's shortened text. Existing CLI fields,
  resolution steps and CLI renderer output are unchanged. These fields never
  enter `ArchitectureReport::toJson()`.
- `tests/Unit/MarkdownReportTest.php`: overview, collision, long-list/name,
  unknown-state, original-evidence and label-safety regressions. Includes actual
  analyzer evidence for two `ShopProject` classes and a private access finding.
- `tools/generate-markdown-examples.php`: developer-only actual analyzer fixtures
  and example generation; `.php-cs-fixer.dist.php` includes this new tool.
- CLI reference, reporting guide and example README document the document's
  organization and label semantics. Seven generated example Markdown files are
  checked in under `docs/examples/architecture-reports`.

`ArchitectureReport`'s JSON contract, the Diagnostic Engine/rules, ReportCommand,
its options/exit codes/path handling/atomic writing, and the terminal renderer
were not changed. Invalid/unavailable states still produce a report when writing
is possible. The overview never claims approved activation for invalid or
incomplete results.

## Test execution

Local environment: PHP 8.5.4 and the existing installed dependencies. No Composer
dependency or lockfile changed.

| Command | Final result |
| --- | --- |
| `php vendor/bin/phpunit tests/Unit/MarkdownReportTest.php tests/Integration/ArchitectureReportingTest.php tests/Integration/ProductionCliTest.php tests/Unit/ArchitectureRendererTest.php --no-progress` | 186 tests, 2,666 assertions passed |
| `composer test` | 519 tests, 4,706 assertions passed |
| `composer analyse` | No errors |
| `composer cs:check` | No fixable files (184 files checked) |
| `composer architecture` | 0 violations, warnings or errors |
| `git diff --check` | Clean |
| `php tools/generate-markdown-examples.php` | All six analyzed scenarios had their asserted expected status; generated seven documents |

Existing executed integration tests include actual Symfony Console subprocesses,
reporting without a compilable container, custom paths, default paths, complete
originals, atomic replacement/failure cleanup and permissions. The full suite
also covers JSON schema 1 and CLI prototype output. No fresh independent Composer
installation or extra PHP/Symfony version matrix is claimed by this task.

The read-only review found a referenced-class label collision with module/Project
names. A failing regression reproduced it; qualifying those class labels fixed
it, and the reviewer verified the correction. Subsequent targeted/full checks
above passed. A further regression ensures private coupling sources also use
the same unambiguous labels as the overview.

## Actual example reports

See [the example index](../examples/architecture-reports/README.md).

| Document | Actual analysis |
| --- | --- |
| `valid.md` | Seven modules, two Projects, valid |
| `valid-with-couplings.md` | Same result, informative Mail coupling opt-in |
| `single-error.md` | One finding, one diagnosis, violations |
| `multiple-errors.md` | Three findings, two diagnoses, violations |
| `incomplete.md` | One fatal parse finding, unavailable, incomplete catalog |
| `large.md` | Twenty modules, long label, twelve declared dependencies expanded fully; known active/disabled/unassigned states |
| `duplicate-names.md` | Two real ShopProject descriptors with different IDs and unambiguous private module names |

Latest source fixtures are retained under
`/tmp/sillbeam-markdown-examples-b4544524`; its `generation.json` records the
actual source roots, statuses and counts. Every scenario has a separate namespace
so loaded PHP descriptor classes cannot resolve to another scenario's file.
The original absolute paths in technical evidence are actual fixture paths,
not invented application locations. A new generation changes source paths and
UTC metadata, so these documents are examples, not byte-stable golden fixtures.

## Rendered inspection

All seven documents were rendered by the ordinary `markdown-it-py` CommonMark
renderer with tables enabled. Structural checks confirmed headings, valid tables,
no FQCNs or absolute temporary paths in the Architecture Overview and no HTML
`br`/script/image elements. Lists are complete, not truncated.

For visual inspection, the resulting HTML was typeset locally with WeasyPrint
and rasterized with pypdfium2. The temporary QA packages were installed only into
`/tmp/sillbeam-report-typesetting`, not the application or Composer dependency
set. The run used:

```bash
PYTHONPATH=/tmp/sillbeam-report-typesetting \
/home/oeffe/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/bin/python \
/tmp/render-sillbeam-markdown.py
```

HTML/PDF/PNG outputs and `render-checks.json` are under
`/tmp/sillbeam-markdown-layout-review`. Visual inspection covered the valid
summary/overview page, multiple-error explanations and locations, incomplete
analysis, duplicate Project names and the large catalog. An initially wasteful
all-list fallback was replaced by the table/list hybrid after viewing the large
example. The valid example's main overview fits on the first A4 preview page;
full technical evidence remains on subsequent pages. PDF pagination is only a
QA view, not a new report/export feature.

## Remaining limits

- Native GitHub and PhpStorm renderers were not inspected pixel by pixel. The
  source uses their ordinary supported headings, tables and lists; actual font,
  table overflow and print pagination remain viewer-dependent.
- Extremely long single identifiers may still depend on viewer wrapping. They
  are isolated from overview table columns instead of shortened or corrupted.
- Incomplete evidence remains incomplete. Unknown state does not mean disabled
  or unassigned. Invalid graphs are not approved runtime graphs.
- The existing Major M1 shortening of descriptor expressions in shared resolution
  text remains open. Resolution steps were intentionally not rewritten by this
  presentation task. This is not release approval or a claim that those concrete
  remediation expressions now execute correctly.
- No changes were committed or pushed as part of this task.
