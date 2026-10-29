# Mermaid architecture export

Implement `sillbeam:graph --format=mermaid` from the existing ArchitectureReport only.
Descriptor classes are node identity, as in module inspection; logical IDs remain
labels. No discovery, traversal, cycle detection, validation or renderer is added.

Design: deterministic `flowchart LR`, sorted catalogs and direct edges, generated
ASCII node IDs independent of labels. Shared scope and private ownership use
separate subgraphs. Project nodes show validated membership with separate edges;
unknown membership is explicitly labelled, never inferred from candidate graphs.
Declared edges, observed undeclared edges, disabled configuration and recorded
cycle members are distinguishable by labels/styles. Missing dependency targets
use explicitly unresolved placeholders. An always-visible evidence/status node
and legend explain non-runtime and incomplete snapshots. Quote and entity-escape
all external labels, including controls; output raw Mermaid with no CLI decoration.
Exit status follows the existing report (0/1/2); usage errors return 2 on STDERR.

- [x] Write failing integration tests for deterministic module edges, observed-only
  evidence, cycles, configuration states, multi-project identity, missing targets,
  unsafe labels and incomplete catalogs.
- [x] Implement internal exporter and command; register both CLI paths.
- [x] Test adapter before boot with invalid architecture/broken services, native
  Symfony registration, option errors, quiet/help and clean redirected STDOUT.
- [x] Document semantics/limits and run suite, PHPStan, style and architecture.

Review focus: colliding logical IDs; incomplete references mistaken for absence;
cycle membership mistaken for proof every edge is cyclic; injected Mermaid syntax;
invalid options/container failures contaminating redirected source.

Verified: 17 graph tests / 130 assertions; full suite 605 / 8,074. PHPStan, style,
architecture, docs build, Composer validation and diff checks pass. Independent
review found no actionable issues. No dependencies, commits, pushes or releases.
See `docs/development/mermaid-graph-2026-10-29.md` for scope and limits.
