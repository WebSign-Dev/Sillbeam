# Architecture graph: SVG and Mermaid

```bash
php bin/console sillbeam:graph
php bin/console sillbeam:graph --format=svg --output=docs/architecture.svg
php bin/console sillbeam:graph --module=Billing
php bin/console sillbeam:graph --module=Billing --project=shop --output=billing.svg
php bin/console sillbeam:graph --module='App\Module\Billing\BillingModule'
php bin/console sillbeam:graph --output=architecture.svg --force
php bin/console sillbeam:graph --format=mermaid > architecture.mmd
```

The default creates **architecture.svg in the current working directory**, then
prints its full absolute path. `--format=svg` selects the same behavior explicitly.
The parent directory of `--output` must already exist and be writable. Existing
files require `--force`; directories and symbolic links (including dangling links)
are rejected during destination checks, even with `--force`. SVG data is staged only after successful rendering.
Same-directory rename replaces an explicitly permitted existing file atomically
where supported. Without `--force`, atomic hard-link publication prevents replacing
a file created concurrently. Filesystems without these operations fail safely;
there is no unsafe copy fallback. With `--force`, destination checks and rename
are separate operations: a concurrent change just before rename can replace the
new directory entry (including a newly introduced symlink). The symlink referent
is never followed or changed. Serialize forced exports when sharing a destination;
`--force` authorizes replacement, not compare-and-swap against the original file.
Temporary files are removed on handled failures.

## Optional renderer

SVG requires the **Graphviz `dot` executable on PATH**, with SVG support, and PHP's
`proc_open` function enabled. Install Graphviz yourself using your operating
system's package manager (for example `apt install graphviz`, `dnf install graphviz`
or `brew install graphviz`). Check availability using `dot -V`.
See [Graphviz installation](https://graphviz.org/download/) and its
[SVG output documentation](https://graphviz.org/docs/outputs/svg/).

Core does not install or download a renderer. Graphviz is not a Composer runtime
requirement. There is no Node.js/browser renderer. Missing or failed `dot` produces
a helpful error on STDERR and exit 4; it never silently returns Mermaid instead.
The process receives an argument array (`dot`, `-Tsvg`), never an interpolated shell
command. DOT input and renderer output/diagnostics use separate temporary streams;
output paths never become process arguments. Rendering has a 60-second limit.
An unsuccessful render leaves the previous SVG untouched.

## Identity and presentation

The graph consumes the same `ArchitectureReport` as
[module inspection](module-inspection.md). A common presentation view supplies
both Mermaid and DOT directly. DOT is never recovered by parsing Mermaid. No new
discovery, parser, dependency traversal, cycle detector or architecture rules run.
The normal Symfony command uses the compiled snapshot; the Core adapter uses the
existing guarded reporter before container boot. Architecture JSON schemas and
activation semantics remain unchanged.

A module's **descriptor class is its identity**. The first visible line is its
module ID. Colliding IDs receive shared/private owner context; collisions within
one owner also receive the shortest unique descriptor suffix. Duplicate Project
IDs receive descriptor context. Full descriptor classes remain DOT/SVG tooltips.
Sorted descriptor classes receive generated ASCII node IDs (`m0001`, `u0001`).
Filtering preserves these IDs within the same catalog. Adding descriptors can
change numbering; use descriptor classes as persistent external identities.

Shared modules appear once in a shared cluster; private modules are grouped by
owning Project. Shared labels list proven Project memberships. Unknown ownership
and unresolved target classes remain explicit. Project membership is separate from
dependency edges; no Project-as-module nodes or membership arrows clutter the graph.
Projects with no private modules in the view appear in the caption. Status and
legend are graph captions, never disconnected architecture nodes. Detailed existing
findings are retained in Mermaid comments and the Graphviz graph tooltip.

| Representation | Meaning |
| --- | --- |
| Solid arrow, no repeated label | Direct declared dependency: **dependent → required module** |
| Dashed orange arrow | Recorded observed reference absent from direct declarations; Mermaid also labels it `observed undeclared`, SVG has a tooltip |
| Gray module | Known disabled configuration |
| Red border / recorded cycle member | Participant in an existing `dependency.cycle` finding |
| Dashed node / state unknown | Activation evidence unavailable |
| Unresolved target cluster | Target class absent from the available module catalog |

Duplicate declarations/references are deduplicated; a declared relationship is not
shown a second time as observed. All direct relationships remain visible in the
unfiltered view. No transitive edges, reduction or dependency pruning are performed.
The left-to-right layout uses spaced, compact nodes and Project clusters. Dense
architectures still require space; use the module filter for a focused diagram.

## Module and Project filters

`--module` accepts an ID or descriptor class, case-insensitively, with the same
selection rules as module inspection. It retains the selected module and every
recorded **direct outgoing and incoming** declared/observed relationship, including
unresolved targets. It does not include edges between neighbours unless incident
to the selected module, or follow further dependencies. Whole-analysis status and
findings remain whole-analysis evidence, even when an unrelated node is filtered.

`--project` accepts a Project ID or descriptor class. It resolves private ownership
and selects the activation context for shared modules. Shared modules remain
selectable even when unassigned there. If a shared/private collision remains,
pass the exact module descriptor class. Unknown/ambiguous selectors fail explicitly.

Used alone, `--project` includes that Project's private modules and shared modules,
including disabled/unassigned modules. **Incident cross-boundary relationships are
retained with their endpoints**, even if they reveal a forbidden dependency on
another Project's private module. Unrelated private modules from other Projects
are omitted. With both filters, module selection uses the Project context, then
retains all incident edges. Scope/ownership never changes because of filtering.

## States and incomplete analysis

Visible states follow module inspection: `active` requires proven activation;
`disabled` requires known disabled configuration and known inactivity;
`unassigned` requires enabled configuration and known inactivity; otherwise the
state is `unknown`. Known disabled configuration is still indicated independently
when activation is unknown. Enabled configuration never establishes activation.
With a Project context, activation is read from that Project's validated membership.

Invalid architecture remains **diagnostic evidence; NOT a validated runtime graph**.
Diagnostic candidates never become runtime membership. Existing cycle findings
mark participants only, without inventing ordered cycle paths or cycle edges.
Incomplete analysis or missing reference completeness metadata is explicitly
marked `Reference evidence incomplete; missing edges do not prove absence`.
An empty/unavailable catalog does not prove that the application has no modules.

## CLI streams, errors and reproducibility

`--format=mermaid` writes raw `flowchart LR` source plus a trailing newline to
STDOUT: no banners, Markdown fences, ANSI codes or confirmation. It requires no
renderer. `--output` and `--force` are SVG-only; combining them with Mermaid is an
invocation error. Redirect Mermaid STDOUT with your shell instead.

`--quiet` suppresses source, confirmation and diagnostics, retaining exit status;
SVG **still renders and writes**. `--help` displays the command reference. Successful
SVG rendering can produce a diagnostic diagram and still return analysis exit 1/2.
A confirmation means the artifact was written, not that the architecture is valid.

| Exit | Meaning |
| --- | --- |
| 0 | Successful export; valid architecture |
| 1 | Successful export; architecture violations |
| 2 | Successful diagnostic export; unavailable/incomplete analysis |
| 3 | Invalid invocation, unsupported format, missing value, unknown/ambiguous selector |
| 4 | Renderer failure/unavailable, timeout, invalid SVG or output filesystem failure |

Errors use STDERR when the output exposes a separate error stream; STDOUT remains
empty on invocation/render/write failure. A renderer/output failure takes priority
over analysis status. Incidental provider output is suppressed by the existing
report collector. Application bootstrap output is outside the command's control.
The Core adapter accepts these options before or after the exact command name and
works even when container compilation fails; see the [CLI reference](cli.md).
Ordinary Symfony requires a bootable container and uses its existing snapshot.

Node/edge/finding ordering and DOT/Mermaid source are deterministic for identical
report evidence. External labels are safely quoted/escaped (DOT quoted strings,
Mermaid decimal entities); input cannot supply node identifiers, attributes, HTML
labels or graph statements. Unicode is preserved, invalid UTF-8/control characters
are made safe. SVG layout/bytes may vary between Graphviz versions and installed
fonts; no byte-level cross-platform SVG reproducibility is promised.
