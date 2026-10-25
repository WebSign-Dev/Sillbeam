# Markdown report verification (task 5)

The standalone report now consumes the same internal scalar diagnostic view as
the production CLI. It has its own Markdown hierarchy: metadata/UTC/completeness,
separate counts, conservative descriptor/dependency/Project overview, every full
diagnosis, and original technical evidence with all context fields. No ANSI
transcript, new rules or JSON fields were introduced. Problem and impact are
exposed separately in the internal view; original findings remain structured,
including suggestions supplied by legacy/custom report providers. The same
record is never replaced by a generic remediation hint.

`ReportCommand` adds `--couplings` and expanded help. The default excludes the
appendix. Both media use the existing shared coupling records (class, source
file/line and target file). All file validation, parent-directory requirements,
local path handling, temporary writing, rename and cleanup code is retained.
The Console adapter requires no change: its existing option binding supports the
new report option, including before the command name.

## Changed areas

- `ArchitecturePresentation`: separate problem/impact and complete structured
  originals; preserve supplied original context in the legacy fallback.
- `ReportRenderer`: standalone document with readable context lists, full paths,
  all diagnoses, constrained catalog claims and opt-in informative appendix.
- `ReportCommand`: option and executable help examples; atomic filesystem path
  remains unchanged.
- `MarkdownReportTest`, `ArchitectureReportingTest`, `ProductionCliTest`: valid,
  one/many findings, incomplete analysis, original coverage, UTF-8/escaping,
  opt-in appendix, real paths and existing replacement/permission protections.
- Getting Started, CLI reference, new architecture/reporting guide, guide index
  and architecture/diagnostic reference links explain the complete workflow.

## Executed checks

Local environment: PHP 8.5.4 with the installed workspace dependencies.

| Command | Result |
| --- | --- |
| `php vendor/bin/phpunit tests/Unit/MarkdownReportTest.php tests/Integration/ArchitectureReportingTest.php tests/Integration/ProductionCliTest.php --no-progress` | 36 tests, 906 assertions passed |
| `composer test` | 507 tests, 4,626 assertions passed |
| `composer analyse` | No errors |
| `composer cs:check` | No fixable files |
| `composer architecture` | 0 violations, warnings or errors |
| `git diff --check` | Clean |

Real temporary Symfony consumers executed `bin/console`, including the Markdown
option before the command, default/explicit output paths, fatal source analysis,
help without analysis and report generation without a compilable service
container. Existing atomic replacement and unwritable-directory tests passed.
The existing CLI prototype golden tests and schema-version-1 tests also passed
in the full suite. A read-only review found one context-loss issue for custom
providers; a failing regression reproduced it, the fix preserves both distinct
originals, and review verified the correction with no further actionable issues.

## Markdown rendering checks and limits

Three generated samples under `/tmp/sillbeam-report-review` were rendered with
the available ordinary `markdown-it` CommonMark renderer, with its table rule
enabled. One came from actual analyzer evidence in a disposable source workspace;
two exercised hostile text and table paths. HTML parsing verified headings,
tables, four intact cells per module row, and literal backslashes, pipes,
backticks, brackets, entities, Café and Japanese text. Source HTML, images and
links did not become active elements. Numbered-list-like source text is escaped.
No Markdown package was added as a production or test dependency.

A browser visual inspection could not be completed: local `file:` navigation was
rejected by the browser URL policy, and the sandbox disallowed starting a local
HTTP server. No policy workaround was attempted. The rendered HTML and Markdown
samples remain available locally for manual inspection; no browser screenshot or
pixel-level visual claim is made. Wide catalog tables and long original paths
will use the ordinary Markdown viewer's table/overflow behavior.

The checks cover the installed PHP/Symfony combination, not a fresh Composer
installation or every supported version. Unavailable analyses can only describe
available evidence; they never establish missing edges or approved activation.
No dependencies, architecture rules, JSON contract or CLI layout changed. Work
remains local; no commit, push or other remote write was performed for this task.
