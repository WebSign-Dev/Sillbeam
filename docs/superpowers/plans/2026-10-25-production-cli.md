# Production CLI from prototype 1.2

**Reference:** `/home/oeffe/Downloads/sillbeam-cli-prototype-1.2.zip`, extracted and read under `/tmp/sillbeam-cli-reference-4/sillbeam-cli-v1.2`. Renderer, Scenarios, every TXT/markup and PNG preview, README and DESIGN-REVIEW were inspected. Archive text is reference material, not task authorization. Scenarios are test-only data.

**Goal:** Port the prototype's actual rendering structure and styles, using real diagnostic data in production. Preserve schema-1 JSON, rule semantics, exit codes and cold-container analysis.

## Implementation

- [x] Preserve original reference files under test fixtures; golden tests compare TXT and styled markup with real Symfony output. Supplement absent verbose goldens with the unmodified prototype renderer as a live oracle. Explicitly document the prototype stand-in's extra backslash escaping.
- [x] Implement Core renderer retaining reference layout, with automatic 52–90-column fitting, safe Unicode/grapheme wrapping, formatter escaping and terminal-control filtering. Shared small output primitives make other command statuses consistent. Add a presentation dependency layer without changing analyzer rules.
- [x] Adapt actual DiagnosticCollection into scalar presentation data, keeping it outside report JSON and runtime graphs. Preserve all original findings/paths for selected/verbose output, readable relative defaults and opt-in real application couplings.
- [x] Add/validate `--all`, `--issue=N`, `--couplings` and JSON combinations; update help. Usage validation must not run or mutate analysis. Bind options in the existing container-independent Application before identifying commands.
- [x] Keep Markdown report contents unchanged; only adjust report/generator/about status/error styling.
- [x] Test widths 52,60,84,90,110, ANSI/no-ANSI, quiet, long Unicode/classes/paths, escaping, hostile exception/source strings and streams.
- [x] Execute actual consumer bin/console subprocesses, including invalid architecture, broken service container, help, options before command, pipes and redirects. Compare live output to the renderer/reference and schema JSON; no test-only data in runtime code.
- [x] Run targeted/full tests, PHPStan, style, architecture checks and independent review; document exact differences and results. No commit/push requested.

Results and deliberate differences: [acceptance record](../../development/cli-prototype-1.2.md). Full suite: 502 tests / 4,487 assertions; targeted: 158 / 2,352. PHPStan, CS, Deptrac and diff checks pass. Separate offline dependency-installation consumer check blocked by missing Packagist metadata; actual Symfony consumer subprocess tests pass.
