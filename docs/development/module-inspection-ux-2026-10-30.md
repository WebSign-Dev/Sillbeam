# Module inspection UX verification — 2026-10-30

Branch `dev-v0.3.x`, base `403e4a7`. PHP 8.5.4, PHPUnit 12.5.35.

## Changes and boundaries

`ModuleInspectionPresentation` projects the unchanged ModuleInspection result into
short collision-aware identities and one direct relationship row per descriptor.
Outgoing completeness belongs to the selected source; incoming completeness belongs
to each source individually. Declared without complete observations is explicitly
unverified; observed positives stay visible. Declared only never means unused.

`InspectModuleCommand` now renders compact state/scope/owner/Project context, global
architecture status, known counts, DEPENDS ON, USED BY and relevant issues/blockers.
Full technical lists, classes/files, configuration and overrides, activation reasons,
Project states and original messages/severity/context remain additive under `-v`.
Normal paths use existing relative presentation where correct; external paths fall
back to basenames without claiming an invented project root. Existing diagnosis
titles/cautions are reused. Host/key values needed to understand a problem remain
visible. Generic legacy suggestions are retained in verbose original context, not
substituted for existing cautions about cycles, privacy or unknown activation.

ModuleInspection, ArchitectureReport, its public JSON/schema, selection/relevance
rules, analyzer, discovery, graph export, runtime hooks and CI are unchanged.
No Composer requirement changes, commits or remote actions.

## Executed verification

| Command | Actual result |
| --- | --- |
| `git branch --show-current`, `git status --short` initially | `dev-v0.3.x`, clean |
| `vendor/bin/phpunit tests/Integration/InspectModuleTest.php` before implementation | Red: 12 expected failures against the former view |
| `vendor/bin/phpunit tests/Integration/InspectModuleTest.php` after implementation/review fixes | **27 tests, 261 assertions**, no failures/errors/skips |
| `composer test` | **635 tests, 8,787 assertions**, no failures/errors/skips |
| `composer analyse` | PHPStan level 8: no errors |
| `composer architecture` | Deptrac: 0 violations, warnings or errors |
| `composer cs:check` | 0 of 204 files need fixing |
| `composer validate --strict` | Valid |
| `git diff --check` and local Markdown-link check | Clean |
| `php /tmp/inspection-user-example.php` | Real analyzed User fixture: compact output, exit 0 |

The [User example](../reference/module-inspection.md#user-example) is captured from
that last command. User declares Mail and Profile, observes Profile, and is both
declared and observed by Admin. It is a fixture example, not a claim about an
external application's User module. The temporary script/report files were used
for verification only; no application files were generated or rewritten.

Coverage includes both Symfony and Core CLI entry points; normal and verbose views;
help/quiet/invalid options; exit 0/1/2; disabled/unassigned/unknown activation; selected
Project versus global activation; private/shared collisions; multi-Project ownership;
declared/observed/both rows in both directions; missing reference completeness;
source-specific incoming completeness; cycles and incomplete analyses; related and
global fatal findings; host errors; external file paths; original blocker context
even on unavailable selection; unchanged public report JSON before/after rendering.
The Core subprocess still inspects violations without compiling broken services.

## Failures encountered and review

A new test fixture initially had an escaped-backslash syntax error; this was corrected
before the meaningful red test run. A test expectation incorrectly omitted the
existing kebab-case IDs `declared-consumer`/`observed-consumer`; actual identity
normalization was preserved and expectations corrected. Initial PHPStan found a
merged mixed-key array passed to a list parameter; array_values now normalizes the
actual list without suppressions or type overrides.

Independent review found two issues: compact host diagnostics omitted the offending
host, and external path metadata could retain a whole machine path with the leading
slash removed. Both were reproduced with failing tests, corrected and independently
rechecked. An additional red/green test preserves the existing caution that adding
an undeclared dependency would create a cycle; no second assessment was implemented.
Independent rereview reported no further actionable findings (3 focused regressions,
12 assertions passed).

The suite still logs the expected 404 exceptions from existing `/archived` and
`/backoffice` negative HTTP tests. PHP CS Fixer warns about host PHP 8.5 versus the
package's PHP 8.4 minimum. No PHP 8.4 matrix or consumer/Flex/package matrix was rerun
for this presentation-only task. No failing checks remain in the runs above.

## Limits

Counts and success describe the available snapshot/static-reference scope. They do
not certify every dynamic runtime interaction. Native Symfony uses its compiled
snapshot; the Core adapter uses its existing fresh reporter. Unrelated nonfatal
findings are indicated by whole-analysis status and the pointer to the full
architecture command; they are not reclassified as local module issues. Ambiguous
selectors still show full candidate classes when needed to choose an exact identity.
