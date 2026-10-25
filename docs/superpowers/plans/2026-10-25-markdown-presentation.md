# Markdown presentation implementation plan

Use the writing-plans/executing-plans workflow inline. Scope is the standalone
Markdown view; shared Diagnostic Engine, CLI output, JSON, file writes and exit
codes stay unchanged. The detailed user specification supplies the design.

- [x] Add failing tests for compact named overview, private ownership, duplicate
  names/projects, unknown targets/states, long lists/names and separate evidence.
- [x] Add a small Markdown-only catalog label helper. Derive names from actual
  descriptors, disambiguate with actual IDs then deterministic local ordinals;
  retain full identity mappings in technical evidence. Unknown references stay
  explicitly unresolved, never silently mapped by basename.
- [x] Update ReportRenderer hierarchy: Architecture Summary/Overview,
  Diagnoses & Resolutions, Technical Evidence, optional Couplings. Move all full
  identities/paths/originals into evidence. Expand long lists outside tables;
  use prose lists for long names. Preserve complete engine explanations.
- [x] Run existing report/consumer/CLI/JSON regressions and quality checks.
- [x] Generate realistic analyzer-backed example reports and ordinary Markdown
  renderings; inspect available visual output and document viewer limits.
- [x] Update report documentation and record exact results; no automatic commit
  or push. Known release Major M1 remains outside this presentation-only scope.

Completed. [Verification](../../development/markdown-presentation-verification.md)
records 519 tests / 4,706 assertions, 186 targeted tests / 2,666 assertions,
green PHPStan/CS/Deptrac/diff checks and actual rendered example inspection.
Canonical scalar explanation fields preserve identity for Markdown without
changing CLI output. Referenced-class and coupling-source collision regressions
are covered. No commit or push. Existing Major M1 remains open.
