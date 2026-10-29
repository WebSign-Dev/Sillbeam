# Targeted module inspection (0.3.0)

```bash
php bin/console sillbeam:inspect:module Billing
php bin/console sillbeam:inspect:module billing --project=shop
php bin/console sillbeam:inspect:module 'App\Project\Shop\Module\Billing\BillingModule' -v
```

The command explains one module using the existing `ArchitectureAnalyzer` →
`ArchitectureAnalysisResult` → `ArchitectureReport` pipeline. It reads the same
catalog, configuration, validated activation graphs, static-reference evidence
and findings as architecture reporting. It neither discovers modules nor analyzes
dependencies a second time. Incoming relationships reverse the existing direct
edges; no transitive graph or additional validation rules are computed.

## Module and Project selection

Pass a module ID or the **fully qualified** descriptor class. Matching is
case-insensitive, so `Billing` selects the conventional `billing` ID. An optional
leading backslash on a descriptor class is accepted. Folder names, fuzzy matches
and abbreviated class names are not selectors; a descriptor with a custom ID must
be selected by that ID or its full class.

If several modules share an ID, the command lists their classes, scope and owning
Project rather than selecting the first. IDs are scoped identities: for example,
`billing` can legitimately exist in both Shop and Admin as a private module.

`--project` accepts a Project ID or full descriptor class. It:

- restricts private-module candidates to that owner;
- provides a Project activation context for shared modules, including shared
  modules that are disabled or unassigned there.

It does not assign or enable anything. A shared module remains a candidate even
when unassigned; otherwise its unassigned state could not be inspected. If a
shared and private module still collide in that Project, pass the full descriptor
class. There is no implicit preference for private or shared scope. Ambiguous
Project IDs likewise require their descriptor class. A private module from a
different owner is not selected in the requested Project context.

Use the command-first spelling above with ordinary Symfony Console. The Core
adapter additionally recognizes `--project shop sillbeam:inspect:module Billing`.

## Output

The text view uses the existing Sillbeam console style and contains:

- Module ID, descriptor class/file, Shared/Private scope and owning Project ID/class.
- Canonical configuration key, the actual override key when present, configured
  enabled override and effective enabled/active values.
- Global and selected-Project activation, plus each applicable Project's state.
- Declared direct module dependencies, observed direct module targets, incoming
  declared dependencies and incoming observed references, as separate sections.
- Related original rule findings, source locations and available remediation hints;
  fatal analysis blockers are identified separately.

`-v` adds the source class/file/line evidence for outgoing and incoming observations.
References remain informational evidence: observing a dependency neither declares
it nor activates the target. Application couplings outside modules, dynamic names
and unused imports retain the existing analyzer's scope; this command does not
extend it. See [architecture analysis](architecture-analysis.md).

The configuration key is normally `shared:billing` or `project:shop:billing`.
Shared-module legacy overrides such as `modules.billing` remain supported; the
canonical scoped key takes precedence, as in the compiler. The displayed override
source distinguishes a configured key (including an ID literally named `none`)
from no override. If logical identity is ambiguous, a canonical key is shown as
`unknown`. Unvalidated input is not presented as effective activation.

## Availability and activation

| Field/state | Meaning |
| --- | --- |
| `Enabled: true` | Effective configuration permits this module; it does not establish activity. |
| `Enabled: false` | Effective configuration disables the module. |
| `Active: true` | Membership in the validated application/Project union, or in the selected Project graph. |
| `Active: false` | Proven absence from that validated graph. |
| `State: active` | Enabled and active. |
| `State: disabled` | Disabled and inactive in the validated graph. |
| `State: unassigned` | Enabled but absent from the validated graph/context. |
| `unknown` | The required validated graph or effective configuration is unavailable. |

Without `--project`, activation is the union of application/Project graphs. With
it, `Active` and `State` describe that Project and `Global active` still describes
the union. A shared module can therefore be active globally but unassigned to the
selected Project. Private modules list only their owning Project as applicable.

Any architecture finding prevents `ArchitectureAnalysisResult` from publishing a
runtime graph. The command deliberately does not promote diagnostic candidate
membership into actual activation. A cycle can therefore show:

```text
Enabled: true
Active: unknown
State: unknown
```

That means configuration is known but actual graph activation is not validated.
An incomplete analysis can also leave `Enabled` unknown. A configured input
`enabled: false` may still be displayed separately; it does not turn an unknown
actual state into a proven disabled runtime state.

Missing reference evidence is not a negative result. The command warns when the
selected module's observed references or incoming evidence are incomplete, and
qualifies empty lists as observations from the available evidence/catalog. If
source parsing prevents discovery altogether, it explains that the catalog is
unavailable and shows the existing blockers; it does not claim the module is absent.

Findings related to the selected module include direct source/target identity,
module source locations, disabled direct declared dependencies, recorded cycle
membership and applicable Project host problems. Unrelated nonfatal findings are not listed as module findings. The
footer always reports the **whole analysis** status and points to
`sillbeam:architecture --all`; an error elsewhere can invalidate the global graph.

## Execution, exit codes and limits

| Exit | Meaning |
| --- | --- |
| 0 | Selection succeeded and the architecture snapshot is valid; disabled/unassigned modules are not failures. |
| 1 | Selection succeeded but the whole analysis has architecture violations. |
| 2 | Invalid usage, unknown/ambiguous module or Project, execution failure or incomplete/unavailable analysis. |

For 0.3.0 this is **text only**. There is no `--format` option or JSON inspection
mode. `--quiet` suppresses text without changing the exit code. `--help` describes
usage without running the report provider in the Core adapter.

Ordinary Symfony Console obtains the existing report stored with its compiled
container, as architecture reporting does. In production a warm container is a
snapshot; rebuild it when deployment inputs change. Activation means membership
in the Sillbeam graph, not an audit of every Symfony service, route or explicit
application override.

To inspect current source even when architecture violations or broken services
prevent container compilation, use the existing
[Core Console adapter](cli.md#enable-reporting-before-container-compilation).
It obtains a fresh report through `KernelArchitectureReporter`, using the same
Symfony configuration preparation and analyzer as other reporting commands.
Normal Symfony startup can fail before a container-backed command becomes available.

The command does not change module settings, generate descriptors, repair files
or prepare runtime services. Existing reporting preparation can initialize bundle
hooks and execute application configuration/descriptor code; their behavior is
unchanged. No new discovery, parser, dependency analyzer or runtime hook is added.
