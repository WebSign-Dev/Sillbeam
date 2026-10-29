# Mermaid graph verification — 2026-10-29

Implemented `sillbeam:graph --format=mermaid` for Core 0.3.0. No commits, pushes,
tags, releases or dependency changes were made for this task.

## Scope

The exporter reads only the existing ArchitectureReport catalog, direct declared
and observed module relationships, Project memberships and findings. Descriptor
classes remain identities, matching module inspection. No discovery, parser,
compiler, cycle detection, dependency traversal or architecture rule changed.
Public architecture JSON and its internal presentation metadata are unchanged.

Both console entry points register the command. The Core adapter uses the existing
KernelArchitectureReporter before container compilation. The ordinary Symfony
entry point reads the compiled report snapshot. No additional runtime or renderer
is used; Mermaid syntax/escaping was checked against the official flowchart
reference and reviewed statically, not executed in a Mermaid parser/renderer.

The [reference](../reference/mermaid-graph.md) explains the representation, state
semantics, determinism, escaping, CLI errors and limits.

## Checks actually run

Environment: PHP 8.5.4, Composer 2.9.5, root Symfony 8.1 dependencies.

| Check | Result |
| --- | --- |
| `vendor/bin/phpunit --filter GraphCommandTest` | 17 tests, 130 assertions, successful |
| `composer test` | 605 tests, 8,074 assertions, successful |
| `composer analyse` | PHPStan level 8, no errors |
| `composer cs:check` | Successful |
| `composer architecture` | Zero violations, warnings or errors |
| `composer docs:build` | Successful |
| `composer validate --strict` | Valid |
| `git diff --check` | Successful |

The initial tests failed because GraphCommand and its CLI registration did not
exist. Further red/green coverage caught a misleading validated-snapshot label
for an otherwise valid result supplied without a runtime graph. The label now
identifies catalog evidence and limits runtime activation to known values.

During verification, a test incorrectly assumed kernel configuration preparation
could not create a cache directory. Existing Symfony preparation can create that
directory before compilation. The corrected test checks the actual boot flag;
subprocess tests also prove the command runs despite unresolvable service wiring.
Two missing test-helper array value PHPDoc types were fixed after PHPStan reported
them. No errors were suppressed or production contracts weakened.

## Behavioral coverage

- Direct declared edges, their direction, absence of synthesized transitive edges,
  duplicate declarations and references, and stable catalog/edge ordering.
- Recorded observed references without a declaration use a separate dashed edge;
  a declared-and-observed relation is not rendered twice.
- Cycles retain original direct edges and recorded participant markings/findings,
  without recomputing cycle paths or assuming all edges between marked nodes cycle.
- Disabled and enabled-but-unassigned modules retain their validated state.
- Shared/private Billing collisions and private Billing modules in two Projects
  retain distinct IDs, correct ownership subgraphs and validated memberships.
- Missing target descriptors are explicitly unresolved placeholders, not modules.
- Invalid descriptors, duplicate logical identities, partial catalogs, unavailable
  reference maps and missing metadata preserve unknown states/incomplete evidence.
- External quotes, brackets, newlines, directives, HTML/entity/Markdown syntax and
  terminal escapes cannot become Mermaid statements or ANSI output.
- Provider output and exceptions produce clean unavailable Mermaid source.
- Unsupported/empty formats and unknown options fail before invoking analysis;
  STDERR is separate from redirected STDOUT. Quiet retains the report exit code.
- Native Symfony registration and help, and Core adapter help/format options before
  and after the command. Real subprocesses with invalid architecture and broken
  service wiring still export raw source, including with `--ansi` requested.

An independent read-only review found no actionable issues and independently ran
all 17 graph tests (130 assertions). No Mermaid runtime/parser/renderer was invoked;
no visual rendering claim is made. Additional PHP/Symfony consumer installations
were not run specifically for this export; existing full-suite regressions ran.
