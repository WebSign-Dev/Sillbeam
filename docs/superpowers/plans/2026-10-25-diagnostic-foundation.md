# Diagnostic foundation implementation plan

**Goal:** Internal, immutable diagnostic evidence, directed declared-graph queries and complete deterministic Finding attribution.

**Architecture:** Keep Finding and runtime/report semantics unchanged. Copy diagnostic-only declaration/reference/state evidence before the analyzer discards an invalid candidate. No candidate graph is retained in the context. Derive ordered graph evidence and conservative direct-edge assessments; group only evidenced directed module relationships or a verified cycle. No rule catalog, renderer, runtime wiring or dependencies.

**Tech stack:** Existing PHP 8.4+, PHPUnit, PHPStan and CS Fixer.

**Spec:** User request “Auftrag 2/6: Fundament der Diagnostic Engine”. Work inline in the current local branch; preserve Auftrag 1 changes. No commits, remote writes or integration/publishing actions required.

## Tasks

- [x] Add failing unit tests in `tests/Unit/DiagnosticGraphTest.php` for directed shortest paths, deterministic tie breaking, ordered multi-node/self cycles, missing return paths, descriptor evidence, direct-edge assessment, privacy and state boundaries, incomplete evidence.
- [x] Implement `Architecture/Diagnostic/{DeclaredDependencyGraph,DependencyAssessment,DiagnosticContext,Resolution}.php`. Keep absence and uncertainty distinct. A candidate is copied only into data and never published as runtime architecture.
- [x] Add failing tests in `tests/Unit/DiagnosticCollectionTest.php` for complete attribution, original Finding preservation, direction-sensitive grouping, verified cycle evidence and deterministic ordering.
- [x] Implement `Architecture/Diagnostic/{Diagnosis,DiagnosticCollection}.php`. Resolutions are explicit technical steps or domain options; no automatic generic fixes.
- [x] Add narrow analyzer regression tests proving invalid results retain only diagnostic evidence, fatal results remain unknown and JSON schema version 1/data stay unchanged. Append an internal context field to the result and build it in the analyzer without changing checks or runtime consumers.
- [x] Document actual data flow, incomplete-state limits, cycle query scope and run-local IDs in `docs/reference/architecture-analysis.md`.
- [x] Run targeted tests, full `composer test`, `composer analyse`, `composer cs:check`, `composer architecture`, and `git diff --check`. Review the diff against the user requirements.

## Review focus

- Fatal discovery/identity/code failures must suppress negative graph and activation claims, including after compilation succeeded.
- Unknown endpoints and unresolved declared targets must never yield an apparently safe dependency recommendation.
- Private ownership and activation membership can differ across Projects; assessments must retain that distinction.
- Findings sharing a rule or unverified context must remain separate; each original entry has exactly one assignment.
- Cycles preserve actual directed edges; DFS witnesses do not claim exhaustive elementary-cycle enumeration.

## Verification

- Targeted PHPUnit run (DiagnosticGraphTest, DiagnosticCollectionTest, ArchitectureAnalysisTest, ArchitectureFindingsTest, ArchitectureReportTest): 50 tests, 260 assertions.
- `composer test`: 294 tests, 1631 assertions, green.
- `composer analyse`: no errors.
- `composer cs:check`: 0 fixable files.
- `composer architecture`: 0 violations, warnings or errors.
- `git diff --check`: clean.
- Independent code review: no actionable correctness issues; no files changed by reviewer.

## Limits retained by design

Cycle witnesses are deterministic DFS back-edge witnesses, not exhaustive elementary cycles. Dependency options check local constraints and require a domain decision plus fresh analysis. No automatic technical rule fixes, productive CLI integration or persistent issue identities. Only the local PHP/Symfony environment was tested. Auftrag 1 changes were preserved; no commits or remote writes were performed.
