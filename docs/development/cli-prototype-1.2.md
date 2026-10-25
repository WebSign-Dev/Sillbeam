# Production CLI acceptance: prototype 1.2

The reference archive was extracted and read before implementation, including
both PHP sources, all TXT/markup/PNG previews, README and DESIGN-REVIEW.
Archive SHA-256:
`9b53e89f6c21b20273fe6c29e78821f8302dc3a90a381aaf676640d110af94e0`.
The 23 preserved reference files under `tests/Fixtures/CliPrototype12` are
byte-identical to the extracted originals. Scenario data is loaded only by tests.

## Data flow and boundaries

The existing analyzer supplies its findings and diagnostic evidence snapshot.
`ArchitecturePresentation` reads `DiagnosticCollection` and projects its real
explanations, resolutions, locations and original findings into scalar data.
`ArchitectureReport` holds that presentation separately from its unchanged
schema-version-1 JSON data. `ArchitectureRenderer` renders it through Symfony
Console; `ConsoleStyle` supplies the shared status primitives. The container
stores scalar report/presentation arrays, never an invalid compiled candidate.
Legacy custom report providers without a snapshot receive a conservative
finding-based presentation fallback.

The Console Application adapter binds reporting options before identifying the
command and prepares configuration without compiling the service container.
Help and invalid option combinations do not run analysis. Validation failures
still keep `ArchitectureAnalysisResult::architecture` null. No runtime registry,
routing, activation, architecture rule or warm HTTP analysis behavior changes.
Markdown content retains its existing renderer; only command status styling
changes for report, generators and about.

## Reference comparisons

`ArchitectureRendererTest` compares all seven stored TXT and markup previews:
success, conflict, multiple, focused diagnosis and all diagnoses. Both plain text
and actual Symfony ANSI bytes are compared. Additional cases execute the original
prototype renderer as an oracle for all scenarios, applicable modes (including
verbose), widths 52/60/84/90/110 and ANSI settings: 130 combinations.

The prototype's preview-writing formatter stub doubles literal backslashes in
markup JSON. Tests decode that transport escape only; original fixture files
remain untouched. Stored TXT comparisons are exact. PNGs were inspected as the
reference's approximate visual previews; exact spacing, text and style checks
use its TXT/markup rather than comparing rasterized fonts.

`ProductionCliTest` launches real PHP `bin/console` subprocesses in temporary
Symfony consumers with their own kernel, configuration and Composer namespace
mapping, using the installed workspace dependencies. It compares actual output
for broken architecture and an uncompilable service container, all five widths,
ANSI/plain output, selectors, verbose originals, help, JSON, pipes/redirects,
quiet, hostile exception text, couplings and the related commands. These are
executed Symfony applications, not Console mocks.

## Deliberate differences and limits

- Production text and group ordering come from the actual diagnostic catalog and
  findings. Manually written demo explanations are never substituted. Missing
  descriptor line numbers are not invented.
- Original findings are additionally shown for selected/all/verbose production
  diagnoses. The prototype's scalar demo fixtures have no original findings.
- Couplings contain observed real classes and locations, not the demo renderer's
  fixed example. They appear only with `--couplings`.
- Fatal/unavailable analysis gets an explicit unavailable status and incomplete
  evidence warning; the prototype has no fatal scenario.
- Long compact-index entries wrap. Long Unicode words wrap at grapheme display
  widths; unsafe controls are removed and formatter tags remain literal. These
  safety cases exceed the prototype fixtures. Short fixture output is identical.
- Automatic terminal detection falls back to 80 columns when unavailable and is
  clamped to 52–90; the demo explicitly defaults to 84. Equal supplied widths
  produce equal fixture layouts. Extremely narrow terminals below 52 columns
  therefore retain the prototype's minimum width.
- `--quiet` suppresses JSON as well as text while preserving status. Normal JSON
  remains complete and unchanged, including when ANSI/verbose are requested.
- The separate `composer test:consumer` dependency-installation check could not
  start offline: Packagist metadata was unavailable with network disabled.
  The real-consumer subprocess tests above ran with installed dependencies.
  No fresh dependency-resolution or cross-version compatibility claim is made.

## Verification

Local environment: PHP 8.5.4, installed Symfony dependencies.

| Command | Result |
| --- | --- |
| `php vendor/bin/phpunit tests/Unit/ArchitectureRendererTest.php tests/Unit/ArchitectureCommandOptionsTest.php tests/Unit/ConsoleStatusTest.php tests/Integration/ProductionCliTest.php --no-progress` | 158 tests, 2,352 assertions passed |
| `composer test` | 502 tests, 4,487 assertions passed |
| `composer analyse` | No errors |
| `composer cs:check` | No fixable files |
| `composer architecture` | 0 violations, warnings or errors |
| `git diff --check` | Clean |
| `COMPOSER_DISABLE_NETWORK=1 composer test:consumer` | Blocked before execution by unavailable Packagist metadata; exit 255 |

A separate read-only code review found no actionable correctness/security issues.
The hostile Unicode/control case was subsequently expanded to ANSI and plain
output, and the complete checks above passed. No dependency was added and no
changes were published.
