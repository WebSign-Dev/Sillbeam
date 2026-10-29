# Targeted module inspection (0.3.0)

**Goal:** `sillbeam:inspect:module Billing` explains one existing module from the shared analysis/report pipeline.
**Design:** Consume `ArchitectureReport` produced from the existing `ArchitectureAnalyzer`/`ArchitectureAnalysisResult`. Add only internal scalar inspection metadata (canonical configuration key/provenance and reference completeness) while preserving architecture JSON. Query/reverse existing catalog edges; never discover source, traverse/compile dependencies or create findings. Reuse ConsoleStyle and the existing kernel reporter/adapter and container report registration.
**Selection:** Case-insensitive module ID or fully qualified descriptor class (optional leading slash). --project accepts Project ID/class, filters private ownership, and provides activation context for shared modules, including unassigned ones. Never silently prioritize a private module over a shared module with the same ID. List remaining candidates and request descriptor class.
**States:** Enabled is availability. Active is membership in the validated report graph or selected Project graph. Invalid/incomplete analysis makes actual activation unknown even if a diagnostic candidate exists. Report configured overrides separately from validated effective values. No observed evidence is not proof of absence when reference collection is incomplete.
**Exit:** 0 valid inspection (including disabled/unassigned); 1 architecture violations (whole analysis); 2 usage/selection/execution error or unavailable analysis. Text only, no new JSON option. No commits, pushes, releases or dependencies.

- [x] Observe failing tests for module-only/shared/private/Multi-Project selection, conflicts, configuration, disabled/unassigned/unknown, direct declared/observed/incoming relationships, cycles and partial evidence.
- [x] Implement scalar metadata and targeted query/presentation using existing reports; leave public schema and architecture validation unchanged.
- [x] Register command through the existing container report and container-independent adapter; test invalid containers, help/quiet, bad options and ordinary Symfony registration.
- [x] Document selection, state/evidence limits and exit codes; execute regression/static/style checks and record results.

**Verified:** 17 inspection tests / 154 assertions; full suite 588 / 7,938; PHPStan, style, architecture and docs build pass. Ordinary compiled inspection passes in real SF7.4/no-Doctrine and SF8.1/Doctrine consumers with both kernels and dev/prod cold/warm caches. Public architecture JSON unchanged. Independent read-only review found no material issues. See `docs/development/module-inspection-2026-10-29.md` for exact scope/results.
