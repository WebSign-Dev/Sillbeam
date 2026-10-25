# Analyzer-backed Markdown report examples

These are actual `ArchitectureAnalyzer` results from disposable PHP/Composer
source fixtures, rendered by the production `ReportRenderer`. Module names,
Projects, dependencies, activation and findings are not hardcoded report rows.
UTC check times and original absolute source paths identify each generation.
The paths point to disposable fixtures, never another user's application.

| Example | Content |
| --- | --- |
| [Valid](valid.md) | Seven modules, two Projects, shared/private AgeGroup names, compact overview |
| [Valid with couplings](valid-with-couplings.md) | Same analysis, opt-in informative Mail reference appendix |
| [Single error](single-error.md) | One undeclared direct dependency, complete diagnosis and original evidence |
| [Multiple errors](multiple-errors.md) | Three source findings in two diagnoses; grouped reference locations and separate originals |
| [Incomplete](incomplete.md) | Fatal PHP parse failure, unavailable status and incomplete-evidence limits |
| [Large](large.md) | Twenty modules, long module name, twelve dependencies expanded outside tables, active/disabled/unassigned states |
| [Duplicate names](duplicate-names.md) | Two `ShopProject` descriptors with different IDs; private modules use unique Project labels |

Regenerate from a development checkout with its dependencies installed:

```bash
php tools/generate-markdown-examples.php
```

An optional first argument selects the output directory. The command writes a
new unique temporary source directory, retains it for inspection, prints its path
and verifies the expected status of every scenario before writing that report.
`generation.json` in that temporary source directory records the outcomes. Each
scenario has its own namespace so loaded PHP descriptor identities do not leak
between analyses. Re-running updates the checked-in example documents with new
UTC times and their new actual fixture paths.

The Markdown uses ordinary headings, lists and tables, with no HTML line breaks.
The current examples were rendered with CommonMark/table support and local HTML
PDF typesetting; overview, diagnoses, duplicate identities and long-list layout
were visually inspected. This is not a pixel-level test of GitHub or PhpStorm.
See [verification and limitations](../../development/markdown-presentation-verification.md).

These examples demonstrate presentation, not release readiness. The communication
refinement preserves fully qualified executable descriptor expressions in shared
resolution text. Regression tests apply the literal CLI suggestions in fresh PHP
processes for both Project selection and direct dependencies. This corrects the
previously identified M1 shortening issue for those flows; it does not replace a
new independent release acceptance. See the
[communication verification](../../development/cli-communication-verification.md).
