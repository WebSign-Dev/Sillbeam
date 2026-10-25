# Markdown report and developer documentation

Implement the user's specified standalone hierarchy using the existing scalar diagnostic presentation, enriched with structured original evidence. No new rules, JSON fields, CLI layout or dependencies. Share coupling evidence with the CLI, opt in via report `--couplings`. Preserve all file validation and atomic replacement code.

1. Add failing report tests for all diagnoses, original evidence, incomplete status, opt-in couplings, Unicode/escaping and unchanged JSON.
2. Render metadata/summary, conservative catalog overview, full diagnoses and structured original evidence; add report option/help.
3. Exercise real consumer report commands and existing path/atomic-write tests.
4. Update Getting Started, CLI reference and architecture/report guide with working examples and adapter boundaries.
5. Run targeted/full tests and quality checks; render a representative Markdown document with an available ordinary Markdown renderer and inspect it. Record results and limits. Leave changes local.

Completed. Results, review correction, rendering checks and the blocked browser
visual inspection are recorded in
[verification](../../development/markdown-report-verification.md).
Full suite: 507 tests / 4,626 assertions. Targeted: 36 / 906. PHPStan, CS,
Deptrac and diff checks pass. No dependency or JSON/rule/layout change.
