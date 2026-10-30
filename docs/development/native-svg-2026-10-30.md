# Native SVG export verification — 2026-10-30

Branch `dev-v0.3.x`, based on `0e8129e`. Workspace is now
`/home/oeffe/PhpstormProjects/websign/sillbeam-core`. User clarification supersedes
the prior optional-Graphviz design: the graph must work immediately after Composer
installation without manual system-tool installation, and create missing parents.

## Implementation and rationale

`SvgGraph` produces self-contained SVG directly from the existing `GraphView`.
A Composer Graphviz wrapper would still require the external executable (see
[graphp/graphviz documentation](https://github.com/graphp/graphviz)); it does not
solve this installation issue. No additional Composer dependency is needed.
Obsolete internal `DotGraph` and `GraphvizRenderer` classes were removed.

Each scope uses a catalog-ordered column. Every direct edge receives a routing
lane beside nodes; cross-scope edges use channels above the columns. This is
presentation only, without dependency traversal, cycle inference, discovery or a
second analyzer. Evidence, selectors, activation and report JSON schemas are
unchanged. XML escaping prevents markup/attribute injection. Bounded SVG text
advance and Unicode-aware wrapping keep labels within nodes; full identities stay
in tooltips. SVG contains no external assets, scripts, links or fonts.

`SvgOutput` recursively creates missing parent directories, then retains atomic
publication, no-clobber protection, symlink rejection and explicit `--force`.
The existing documented concurrent forced-rename limitation still applies.
`--format=mermaid` remains explicit raw STDOUT. Default/module/Project graph
invocations write actual SVG. Existing files require `--force`; invalid analyses
still yield diagnostic graphics and analysis exit 1/2. File errors return 4,
invocation/selection errors 3. Quiet mode still writes SVG.

## Executed commands and results

PHP 8.5.4, PHPUnit 12.5.35. The renamed checkout had no vendor directory;
`composer install --no-interaction --prefer-dist` restored its 91 already-locked
packages successfully. Composer files and runtime requirements were not changed.

| Command | Actual result |
| --- | --- |
| `vendor/bin/phpunit tests/Integration/GraphSvgTest.php` before implementation | Red: default SVG/no-tools and recursive-directory cases failed; SvgGraph absent |
| `vendor/bin/phpunit --filter testWideLabelsStayWithinTheNodeTextArea` before width fix | Red: missing bounded SVG text width |
| `composer test` after the width fix | **625 tests, 8,670 assertions**, no failures/errors/skips |
| `vendor/bin/phpunit tests/Integration/GraphCommandTest.php tests/Integration/GraphSvgTest.php` after additional module-filter assertions | **34 tests, 317 assertions**, no failures/errors/skips |
| `composer analyse` | PHPStan level 8: no errors |
| `composer architecture` | 0 Deptrac violations, warnings or errors |
| `composer cs:check` | 0 of 203 files need fixing |
| `composer validate --strict` | Valid |
| `git diff --check` and local Markdown link validation | Clean |

The last targeted run adds module-filter SVG assertions for both CLI entries to
the already-passing complete suite; production source was unchanged between these
runs. Tests render and parse real SVG, including recursive paths, both entry
points, filters, safe replacement, literal shell characters, concurrent creation,
Unicode/markup escaping, determinism, all three analysis statuses, observed edges,
cycles, disabled/unknown states and unresolved targets. A subprocess explicitly
disables `proc_open`, `exec`, `shell_exec`, `system`, `passthru` and `popen` and still
writes a valid SVG. An empty PATH is tested too. No Graphviz-dependent test is skipped.

Real dense (10 modules, 45 edges) and Multi-Project SVG samples were generated in
`/tmp` and visually inspected using an already-installed Chrome with a separate
temporary profile. Chrome merely opened completed native SVG for development QA;
it is not part of runtime rendering, installation, or test-suite requirements.
Node labels, arrowheads, routing lanes, captions and scope boundaries were visible.
The review found wide proportional-font labels could overflow the box. A failing
regression test reproduced that; bounded `textLength`/`spacingAndGlyphs` resolves
it. Independent rereview found no outstanding actionable issues.

## Failures, limits and scope

Initial test-edit quoting/syntax issues were corrected before the red tests ran.
Initial PHPStan flagged four nullable/false XPath results in new tests; explicit
array assertions now validate those results. No errors are suppressed/baselined.
The suite retains expected logged 404 exceptions from its existing negative HTTP
cases. PHP CS Fixer warns that the host is PHP 8.5 while the package floor is 8.4.
No PHP 8.4 matrix rerun or consumer/Flex/release-package certification was attempted.

Dense diagrams remain large and may contain crossing lines. The deterministic
column layout does not promise Graphviz-style crossing minimization. Use direct
module filtering for focus. Viewer fonts may affect glyph appearance; SVG byte
ordering/layout coordinates remain deterministic for identical report evidence.
The exporter saves a file and confirms the path; it does not launch a viewer.

No CI-trigger changes, system installs, automatic downloads, public API/schema
changes, commits, pushes, tags, merges or releases. Historical Graphviz/release
reports remain intact as records of their original runs.
