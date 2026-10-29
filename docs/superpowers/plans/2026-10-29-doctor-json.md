# Doctor JSON v1

**Goal:** Add stable machine-readable output to the existing read-only installation Doctor.
**Design:** Inspector results gain explicit stable category IDs; no second inspection. A small internal report object maps the same rows to schema version 1 (`status`, `exit_code`, `summary`, `checks`, `errors`). Check descriptions are the Inspector's labels; `detail` retains its concrete evidence/remediation text. Repeated IDs preserve independent observations. Usage/execution failures have no fabricated checks, zero summary counts and an explicit error plus exit 2.
**Constraints:** Text default and existing statuses/exit priority unchanged; no runtime dependencies, automatic consumer edits, commits, pushes or releases. Quiet suppresses JSON too. Command help remains Symfony help rather than a Doctor result.

- [x] Write failing tests for status mapping, combined observations, Inspector equivalence/IDs, schema contract, UTF-8/raw output, options, quiet/help and native/adapter entry points.
- [x] Add explicit Inspector IDs and shared report serialization/exit calculation; retain existing text rendering.
- [x] Add --format, binding/execution error handling and Doctor-specific adapter failure routing; do not emit architecture JSON for Doctor errors.
- [x] Publish the JSON schema and CLI documentation, including native Symfony pre-command boot/help boundaries.
- [x] Run targeted and regression PHPUnit, static analysis, formatting and architecture checks; validate JSON with the published schema and record results.

**Verified:** 571 PHPUnit tests / 7,775 assertions; targeted 74 / 2,247; PHPStan, coding standards, architecture and docs build clean. Seven serialized reports plus example validated against Draft202012 schema. Text unchanged byte-for-byte against bb6f423 across all states. Real SF7.4/no-Doctrine fresh and SF8.1/Doctrine existing consumers pass. Review format-resolution finding fixed with red/green tests; preparation noise also covered. Details: `docs/development/doctor-json-2026-10-29.md`.
