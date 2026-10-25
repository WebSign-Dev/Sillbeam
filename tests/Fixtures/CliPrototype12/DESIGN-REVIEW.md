# Design review – Sillbeam CLI 1.2

## Intent

Keep the calm, Symfony Console-based look of prototype 1.1, while making multi-error output far easier to scan. The presentation takes precedence over introducing new package dependencies.

## Design decisions

1. **No errors:** One green confirmation and a compact count; no tables or informational couplings.
2. **One diagnosis:** Render the full context immediately; avoid making a developer invoke another option to understand a single failure.
3. **Many diagnoses:** Show every group in a short index and expand the first diagnosis below. The selected group can be switched via `--issue=N`, or all groups expanded via `--all`.
4. **Always actionable:** Expanded diagnosis consistently provides *What is wrong*, *Important context* when grounded, and *Possible next steps*. It does not claim the first suggestion is always the correct architectural choice.
5. **Information hierarchy:** Short module names and relative locations in default view; full references and codes in verbose view. Application coupling is available only on explicit opt-in.
6. **Terminal fit:** 52–90 columns; narrow view prioritizes wrapping, not truncation. No colorful full-width banners or UI-box borders.

## Questions for product review

- Should multi-error output display only one diagnosis in full, or expand the first two?
- Is `--issue=N` intuitive enough, or should the future command use `--details=2` / `--all`?
- Is the reference path `User/Command/ImportLegacyUserCommand.php:18` the right level of context?
- Should a standalone successful check omit even the module/project counts?
- Should `--couplings` live on `sillbeam:architecture` or eventually on a dedicated inspect command?

## Must be handled during integration

- The sample diagnosis order is manually supplied; a real priority sort requires explicit rules.
- Combining multiple errors is only allowed if each raw finding remains available in JSON/verbose views.
- Graph-aware recommendations must never be emitted based on guesses.
- Present one consistent failure layout for invalid-container startup; ensure the developer can still access the analysis.
- Symfony Console color stripping, width fallback, verbosity and non-interactive shells need live integration tests.
- Do not confuse a presentation prototype with release-ready Core code.
