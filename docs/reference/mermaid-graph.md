# Mermaid architecture graph

```bash
php bin/console sillbeam:graph --format=mermaid
php bin/console sillbeam:graph --format=mermaid > architecture.mmd
```

`mermaid` is the default and the only supported format. STDOUT contains raw
`flowchart LR` source with a trailing newline, without Markdown fences, CLI
banners, ANSI codes or progress messages. No Mermaid runtime, renderer or new
Composer dependency is installed. The file can be consumed by an existing
Mermaid-capable documentation tool. The syntax uses conventional quoted labels,
decimal entity escapes, subgraphs and class definitions documented in the
[official Mermaid flowchart reference](https://mermaid.js.org/syntax/flowchart.html).

The command consumes the same `ArchitectureReport` produced from
`ArchitectureAnalyzer` / `ArchitectureAnalysisResult` as
[module inspection](module-inspection.md). It performs no discovery, source
parsing, dependency traversal, cycle detection or additional architecture checks.
Existing architecture JSON and its schema are unchanged.

## Identity, scope and relationships

A module's full descriptor class is its identity. Its logical ID is a label,
not a Mermaid node ID. The export sorts full classes and assigns separate ASCII
IDs (`m0001`, `p0001`, `u0001`, and so on). Two private modules with the same ID in
different Projects, or a shared/private ID collision, remain distinct nodes.
Node IDs are deterministic for the same catalog, but are not a persistent external
identifier: adding another descriptor may change their numbering.

Shared modules appear in their own subgraph. Private modules appear in their
owning Project's subgraph, labelled with both the Project ID and descriptor class.
Ownership is catalog evidence, independent of activation. A private owner missing
from the available Project catalog is explicitly labelled unknown. Project nodes
remain visible even without private modules. Each shared module appears only once,
including when several Projects use it.

| Representation | Meaning |
| --- | --- |
| Solid arrow labelled `declared` | Direct dependency recorded in the source module's descriptor, directed from dependent to dependency |
| Dashed arrow labelled `observed undeclared` | Existing observed module reference whose target is absent from that source's direct declarations |
| Dashed arrow labelled `active membership` | Existing validated Project module membership, directed from Project to module |
| Grey module, `enabled=false` | Configuration disables the module; this alone does not establish its runtime state |
| Red outline, `recorded cycle member` | Descriptor is listed in an existing `dependency.cycle` finding |
| Dashed outline, `unknown` fields | Required activation/configuration evidence is unavailable |
| `unresolved module target` | Declared/observed target class is absent from the available module catalog; not an invented module |

Repeated declarations/references are deduplicated for display. A declared edge with
observed references is shown once as declared. No transitive dependency edges are
computed. Project membership may include expanded dependencies because it comes
from the already validated report; it is explicitly labelled as membership rather
than a declaration. Private ownership remains separate from that membership.

Cycle markers use only the participants recorded by the existing findings. They
do not claim that every edge between marked nodes belongs to a cycle, reconstruct
an ordered cycle path or enumerate all possible cycles. The original findings
are also included as standalone diagnostic labels.

## States and incomplete evidence

The graph always includes a visible analysis-status node and relationship legend.
Module labels expose `enabled`, `active` and `state` with the same meanings as
module inspection:

- `enabled=true` is configuration availability, not proof of activation.
- `active=true` / `active=false` are read from the validated architecture report.
- `state=disabled` requires known disabled configuration and known inactivity.
- `state=unassigned` requires enabled configuration and known inactivity.
- `unknown` is retained whenever the required evidence is unavailable.

Any architecture finding suppresses the validated runtime graph. The export still
shows the available module catalog and direct dependency evidence, but clearly
labels it **diagnostic evidence; NOT a validated runtime graph**. It never promotes
diagnostic candidate membership into actual Project activation. Project membership
is labelled `unknown` and no membership arrows are emitted in that case.
A known configuration flag may still be shown while actual activation is unknown.

Fatal blockers/incomplete analyses retain only available evidence. A missing
reference-map entry, missing internal completeness metadata or incomplete analysis
produces `Reference evidence incomplete; missing edges do not prove absence`.
An empty/unavailable module catalog is explicitly labelled; it does not imply that
the application has no modules. No guessed nodes, states, edges or success findings
are added to complete a broken analysis.

Labels are quoted and external punctuation is encoded as Mermaid decimal
entities, including quotes, entity introducers, HTML/Markdown delimiters and
Mermaid directive characters. Controls, line separators and bidi controls are
replaced with spaces. External text never supplies node IDs or graph statements.
Catalogs, findings and direct edges have deterministic ordering; timestamps and
random export IDs are not emitted.

## CLI, failures and limits

| Exit | Meaning |
| --- | --- |
| 0 | Existing architecture report is valid |
| 1 | Existing report contains architecture violations; source is still exported |
| 2 | Analysis unavailable/incomplete, provider failure or invalid command usage |

Analysis/provider failures become an unavailable Mermaid diagnostic graph through
the existing guarded report collector. Incidental provider output is suppressed.
Unsupported formats, unknown options and missing option values return exit 2
before analysis; ordinary console usage errors go to STDERR and leave STDOUT empty.
`--quiet` suppresses source and diagnostics while retaining the exit status.
`--help` shows command help without running analysis in the Core adapter.
The application bootstrap itself must not print text before running the console.

For a broken container, select `Sillbeam\Core\Console\Application` in the existing
console bootstrap as described in the [CLI reference](cli.md). The exact command
`sillbeam:graph` then uses the existing kernel reporter before container compilation
or kernel boot. No executable or installation system is added. Configuration and
descriptor hooks follow the same execution and side-effect boundaries as existing
architecture reporting.

Ordinary Symfony Console registers the command through the bundle and uses its
compiled architecture snapshot. A warm production container therefore reflects
that snapshot; this is not a live service-availability check. Use the Core adapter
when fresh analysis must remain possible despite architecture violations.
