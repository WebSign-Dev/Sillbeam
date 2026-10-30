# Architecture graph UX verification — 2026-10-30

Branch: `dev-v0.3.x`. PHP 8.5.4, PHPUnit 12.5.35. No commits, remote actions,
installations, CI trigger changes or Composer runtime requirement changes.

## Implementation

`GraphView` projects existing ArchitectureReport catalogs, findings and direct edges.
`ModuleInspection::select()` and the extracted `selectProject()` supply the same
identity/ambiguity rules. Mermaid and DOT consume that shared view; neither parses
the other's output. Filtering scans direct edges, retaining incoming and outgoing
relationships and incident cross-Project evidence, without dependency traversal.

Labels emphasize IDs, disambiguate scopes/owners and same-owner descriptor suffixes,
retain Project memberships and disabled/unknown/cycle evidence. No Project,
legend, status or finding is presented as an architecture module. Status and legend
are captions; detailed findings remain Mermaid comments/Graphviz tooltips.
The 10-module dense fixture retains all 45 declared edges in both formats.

SVG is now default. Optional Graphviz receives DOT through streams via an argument
array, without shell interpolation. Output is published after successful rendering;
no-clobber hard links or explicit forced rename are same-directory atomic operations.
Known symlink/directory targets are rejected. Concurrent forced replacement is not
compare-and-swap: a directory entry changed just before rename can be replaced,
without following a symlink referent. The command help/reference document this limit.

Full user contract: [architecture graph reference](../reference/mermaid-graph.md).
After successful export, report status maps to 0/1/2; invocation/selection uses 3,
renderer/output failure 4. Quiet SVG writes the file; quiet Mermaid emits no source.

## Executed verification

| Command | Actual result |
| --- | --- |
| `git branch --show-current`, `git status --short` before changes | `dev-v0.3.x`, clean |
| `vendor/bin/phpunit tests/Integration/GraphCommandTest.php tests/Integration/GraphSvgTest.php` | 32 tests, 289 assertions, zero failures/errors, **one skip** |
| `composer test` (final source) | 623 tests, 8,650 assertions, zero failures/errors, **one skip** |
| `composer analyse` | PHPStan level 8: no errors |
| `composer architecture` | Deptrac: 0 violations, 0 warnings, 0 errors |
| `composer cs:check` | 0 of 204 files need fixing |
| `composer validate --strict` | Valid |
| `git diff --check` | Clean |
| `command -v dot`, `dot -V` | No executable on PATH; `dot` not found |

The skipped test is `GraphSvgTest::testRealGraphvizWhenAvailable`. **No actual
Graphviz SVG or visual readability inspection was possible on this host.** No
installation/download was attempted. Real-renderer layout, font-dependent output
and rendered readability remain to be verified on a host with Graphviz. The test
will parse/check real SVG there. Controlled executable tests verify real process
invocation, exact `-Tsvg` arguments and DOT input, successful complete SVG writes,
missing executable, nonzero exit, incomplete/non-SVG output, literal shell characters
in paths, existing files, force/quiet, directories/symlinks and concurrent creation.
These do not substitute for a real Graphviz rendering claim.

Both native Symfony Console and the Core adapter are tested for Mermaid, SVG,
help, quiet, invalid options/selections and STDERR separation. A subprocess test
also verifies the Core entry with a broken container and options before the command.
Tests cover direct/observed dependencies, cycles, disabled/unassigned/unknown states,
invalid descriptors, partial reference evidence, duplicate logical IDs, Shared and
private Modules, Projects, deterministic ordering and unsafe Mermaid/DOT labels.

## Failures encountered and corrected

Initial red tests exposed absent filters/default SVG and old verbose Mermaid labels.
Initial SVG tests could not construct the not-yet-created renderer. An intermediate
Core-adapter implementation tried to register two different `format` definitions;
that was corrected by adding only previously unknown options during command-name
probing. Existing output assertions were adjusted to intentional Mermaid escaping
and the changed compact representation. Initial PHPStan found `getcwd()`'s possible
false result in a test; this is now checked. Coding-standard fixes were applied,
then checks rerun. Final runs have no failing assertions or analysis errors.

The suite logs expected 404 exceptions for its existing `/archived` and
`/backoffice` HTTP negative cases. PHP CS Fixer warns about running PHP 8.5 against
a PHP 8.4 minimum. Neither is a failing check; no PHP 8.4 runtime run was performed
for this work package. Early Composer invocation also warned about an unwritable
user cache and continued without it.

Independent read-only review found the forced-publication concurrency limit above;
the inaccurate blanket symlink guarantee was narrowed in help/PHPDoc/reference.
No other actionable activation, filter, escaping or process findings were reported.

Historical release-acceptance reports remain unchanged. This work does not certify
a new release or repeat the full consumer/Flex/release-package matrix. Generated
SVG bytes are Graphviz/font-version dependent; DOT/Mermaid ordering is deterministic.
