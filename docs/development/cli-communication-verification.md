# Diagnostic communication verification

Verified on 2026-10-25 against local `dev`, based on `645b35f`, with the
communication changes still uncommitted. PHP 8.5.4; Symfony Console/Framework
8.1.9. This is a focused communication verification, not a new release acceptance.

## Changes and limits

The central `DiagnosticRuleCatalog` uses direct English for all existing rule
codes. CLI and Markdown consume the same explanations and resolutions. The
ordinary undeclared dependency case states that no conflicting dependency was
detected only when the available graph, relationship and activation evidence
justify that statement. Explicit internal caution metadata preserves the warning
block for incomplete evidence, privacy, existing/prospective cycles and activation
constraints. Disabled, unassigned and unknown remain distinct.

`ArchitecturePresentation` retains executable fully qualified `::class`
expressions. Direct dependency advice and Project module-selection advice include
a leading backslash, so copying them inside another PHP namespace resolves the
intended descriptor. This addresses the previously recorded M1 shortening defect
for both tested workflows. Choosing whether the dependency belongs in the domain
remains an option, not an automatic repair or guarantee.

Renderer layout, colors, indentation, spacing, flags, analyzer rules, original
findings, JSON schema/version and exit-code behavior were not redesigned or
changed. The seven checked-in Markdown examples were regenerated from actual
analyses, retaining their existing report structure.

## Executed checks

| Command | Result |
| --- | --- |
| `php vendor/bin/phpunit tests/Unit/DiagnosticCommunicationTest.php tests/Unit/DiagnosticRuleCatalogTest.php tests/Unit/DiagnosticGraphTest.php tests/Integration/ProductionCliTest.php --no-progress` | Passed: 71 tests, 1909 assertions before final regex-test type narrowing |
| `php vendor/bin/phpunit tests/Unit/DiagnosticCommunicationTest.php tests/Integration/ProductionCliTest.php --no-progress` | Passed after narrowing: 23 tests, 1442 assertions |
| `php vendor/bin/phpunit --no-progress` | Passed: 523 tests, 5451 assertions; includes report, original prototype golden masters and integration coverage |
| `composer analyse` | Passed, no PHPStan errors |
| `composer cs:check` | Passed, 184 files checked |
| `composer architecture` | Passed: no violations, warnings or errors; 184 uncovered dependencies remain reported by the existing configuration |
| `php tools/generate-markdown-examples.php` | Passed: six analyzer scenarios, seven documents, expected valid/violations/unavailable statuses |
| `git diff --check` | Passed |

The first style check found import ordering in a new test; corrected. The first
PHPStan check found three insufficiently narrowed regex-match offsets in new
tests; explicit failed-match branches corrected them. No suppressions were added.

## Concrete regression evidence

- Portrait uses Admin without a direct dependency: plain problem/reason/action,
  no needless IMPORTANT CONTEXT block, identical recommendation in Markdown,
  and the literal displayed descriptor expression applied successfully in a
  fresh PHP process. The resulting architecture has no findings.
- A temporary real Symfony consumer executes its own `bin/console` using the
  production container-independent adapter. The literal Project-selection class
  expression is applied first. The next process reports the missing direct
  dependency, whose literal class expression is then applied. A further process
  returns exit 0 and an empty JSON findings array. The test uses the checkout's
  installed vendor dependencies, not a separately Composer-installed Core copy.
- Ten evidence/graph variants cover active, disabled, unassigned, unknown,
  incomplete, unresolved targets, return paths, privacy, already-declared edges
  and activation only in another Project. Positive wording is restricted to the
  ordinary fully justified case. Existing rule-table tests cover every rule.
- Text wrapping is checked at 52, 60, 84, 90 and 110 columns, with ANSI and
  no-ANSI. Existing prototype TXT/markup and live renderer comparisons remain
  part of the full suite. The renderer itself is unchanged.
- The full suite also checks original findings, multiple diagnoses, JSON,
  unavailable analysis, Markdown escaping and container-independent access with
  a broken Symfony service container.

## Remaining limitations

No new independent release acceptance, fresh Composer path installation, complete
Dev/Prod HTTP/cache matrix, or visual PNG comparison was performed for this
language-only change. The previous release acceptance remains historical evidence;
this verification does not retrospectively change its result. Future unknown
rule codes still use the original finding and a deliberately general fallback.
