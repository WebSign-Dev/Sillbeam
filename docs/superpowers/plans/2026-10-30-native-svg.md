# Native SVG implementation plan

User clarification: Composer-only installation or a self-contained implementation,
with no manual system-tool installation. Existing graph filters must produce real
graphics directly. Missing output parents must be created automatically.

Decision: use a minimal PHP SVG renderer over GraphView. graphp/graphviz would still
require dot; a wrapper does not solve installation. Remove obsolete internal DOT/
Graphviz classes. Preserve Mermaid opt-in, selectors, evidence, statuses and atomic
publication. No Composer requirements, CI triggers, automatic downloads or remote
operations change.

- [x] Write and run failing tests for real default SVG with no dot, and recursive parents.
- [x] Implement catalog-order scope columns and direct-edge routing lanes, XML escaping,
  Unicode-aware wrapping and complete internal marker definitions without dependencies.
- [x] Replace fake-renderer tests with actual SVG XML/edge/status checks, empty PATH,
  disabled process functions, both CLI entries and safe overwrite/concurrent creation.
- [x] Run PHPUnit, PHPStan, Deptrac, coding standards and Composer validation.
- [x] Visually inspect real dense and Multi-Project SVG output; update user docs/report.

Limits: layout is a deterministic catalog presentation, never an additional analyzer
or dependency traversal. Dense graphs can remain large. Existing forced-rename
concurrency limitation remains documented. No commits or pushes.
