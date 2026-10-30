# Module inspection UX implementation plan

Use unchanged ModuleInspection selection/activation/relevance results. Add an internal
presentation-only projection for short unambiguous labels, merged direct relationship
rows and readable findings. Render concise default output; retain original evidence,
configuration, activation reasons, Project states, paths and separate lists under -v.
Existing report presentation supplies known relative reference paths. Unknown roots
are never guessed. No discovery, checks, public JSON/schema or graph behavior change.

- [x] Pin compact/default/verbose, merged evidence/completeness, Project/global states,
  collisions and fatal blockers with failing integration tests.
- [x] Implement projection and output using the existing ConsoleStyle; preserve exits.
- [x] Verify both CLI entries, help/quiet, malformed selection and incomplete reports.
- [x] Run targeted/full PHPUnit, PHPStan, Deptrac, standards, Composer validation.
- [x] Document the actual User-module example and executed results; independent review.

No commits, pushes, releases, CI changes or dependency changes. Only dev-v0.3.x.
