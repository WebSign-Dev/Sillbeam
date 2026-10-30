# Architecture Graph UX implementation plan

Goal: SVG by default, compact deterministic Mermaid, shared report evidence and direct-neighbour filters.

Architecture: GraphView projects ArchitectureReport catalogs/edges without analysis or traversal. ModuleInspection supplies identical selector semantics. MermaidGraph and DotGraph consume the same view. GraphvizRenderer invokes optional dot using an argument array; SvgOutput publishes a complete artifact with same-directory atomic operations.

Constraints: existing branch dev-v0.3.x; no runtime requirements, CI trigger changes, installations, commits or remote actions. Report schemas and activation semantics unchanged.

- [x] Add failing integration tests for compact labels, direct filters, ambiguous IDs and Project context.
- [x] Implement shared view and both renderers; retain every recorded direct edge in the unfiltered view. Status/legend belong to graph captions, never nodes.
- [x] Add failing process/filesystem tests for SVG default, explicit paths, missing/failing renderer, overwrite protection, quiet/help and both adapters.
- [x] Implement SVG invocation and atomic publication. Exit 0/1/2 reflect analysis after successful export; usage/selection 3, renderer/filesystem 4.
- [x] Run targeted/full PHPUnit, PHPStan, Deptrac, coding standards, Composer validation. Run actual Graphviz if available; explicitly report unavailable integration.
- [x] Update CLI/reference/README and record exact verification results and limits. Review changes and ensure no unrelated changes.

Review focus: duplicate logical IDs never merge descriptor nodes; Project filters retain incident cross-boundary evidence; enabled does not imply active; external labels cannot inject DOT/Mermaid; concurrent creation and symlink targets never get silently overwritten.

Outcome: implemented and independently reviewed. The real Graphviz integration
remains conditional and was skipped locally because dot is absent; this is recorded
in the verification report, not counted as a passing real-renderer check. Forced
replacement's concurrent-directory-entry limit is explicitly documented.
