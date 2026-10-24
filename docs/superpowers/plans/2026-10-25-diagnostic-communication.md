# Diagnostic communication implementation plan

**Goal:** Concise English explanations and evidence-based next steps for all existing rules, using the Auftrag 2 diagnostic foundation and actual analyzer evidence.

**Scope:** No architecture-rule, Finding, exit-code, JSON, CLI, renderer or Markdown-report changes. Preserve all earlier local work. No remote writes.

## Design

A `FindingExplanation` holds the unchanged Finding, problem, impact and resolutions. An internal `DiagnosticRuleCatalog` provides each existing rule's semantics and graph/state-aware advice. `Diagnosis` retains per-Finding details and a combined explanation; its resolutions are deduplicated. Resolutions distinguish domain options from technical recovery/investigation, allow missing file evidence explicitly, and separately expose known logical configuration addresses. DiagnosticContext supplies canonical unambiguous module/project addresses and configured host evidence, without guessing config filenames. Fatal blockers prohibit dependency/activation recommendations but retain concrete blocker recovery steps.

## Tasks

- [x] Add failing table-driven catalog tests for every existing rule, missing context and precise English semantics.
- [x] Implement the catalog and detail data; extend context evidence and Resolution/Diagnosis only as needed.
- [x] Add analyzer-backed reference scenarios A–D, graph/private counterconditions, duplicate/config/host examples and original-location preservation.
- [x] Connect catalog to DiagnosticCollection, deduplicate identical resolutions, count Findings/diagnoses separately, and put actual fatal analysis prerequisites before consequences without a rule-code ranking.
- [x] Execute useful technical repair steps on modified source fixtures and assert analyzer findings clear; preserve JSON before/after diagnostic generation.
- [x] Document context-specific cases and deliberate fallbacks. Run targeted/full tests, analysis, style, architecture checks and independent review.

## Review focus

No add-dependency advice with a return path/private boundary/incomplete evidence. Configuration keys use actual target ownership, never the requesting Project. Cycles retain actual order and all proven descriptor paths. Fatal discovery does not invent a missing catalog, activation or fix guarantee. External Composer types/autowiring remain outside the bounded unknown-class check. All Findings, including repeated locations, remain attributable exactly once, with separate counts and no duplicate resolutions within a group.

## Verification and review

- Targeted `vendor/bin/phpunit tests/Unit/DiagnosticCommunicationTest.php tests/Unit/DiagnosticRuleCatalogTest.php tests/Unit/DiagnosticCollectionTest.php tests/Unit/DiagnosticGraphTest.php`: 68 tests, 593 assertions, green.
- `composer test`: 344 tests, 2121 assertions, green.
- `composer analyse`: no errors.
- `composer cs:check`: passed.
- `composer architecture`: no violations, warnings or errors.
- `git diff --check`: clean.
- Independent review found an ineffective enable option for a private module requested by a foreign Project. Fixed after reproducing it with a failing assertion: owner address remains evidence, enable advice is suppressed in favor of resolving the forbidden requirement.
- Actual repairs were applied to temporary fixtures and verified in fresh PHP processes: a direct descriptor dependency, enabled overrides, unique host assignments, restored PHP syntax and restored application source. Fresh processes avoid treating already loaded executable descriptors as fresh metadata.

## Deliberate limits

No CLI/renderers, existing JSON/suggestions/exit codes, architecture rules or runtime wiring were changed. Missing discovery/source metadata uses the documented honest fallback. Domain choices are options, not guaranteed fixes. Only the installed PHP/Symfony combination was tested. Prior local changes remain; no commits, pushes or other remote writes were performed.
