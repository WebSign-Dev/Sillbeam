# Internal diagnostic communication

The internal `Architecture\Diagnostic` layer explains the existing analyzer's
Findings. It adds no rules and does not change `ArchitectureFinding::suggestion()`,
existing architecture rules, exit codes or JSON schema version 1. The console renderer consumes this layer; Markdown retains its existing format.

```php
$diagnostics = DiagnosticCollection::fromAnalysis($analysis);
$findings = $diagnostics->findingCount();
$diagnoses = $diagnostics->diagnosisCount();
foreach ($diagnostics->diagnoses as $diagnosis) {
    foreach ($diagnosis->details as $detail) {
        // Unchanged original Finding, concise English problem and impact.
        $original = $detail->finding;
        $location = $detail->location(); // Reported file[:line], or null.
    }
    // Deduplicated next steps; original per-Finding details remain accessible.
    $resolutions = $diagnosis->resolutions;
}
```

`FindingExplanation` holds the original Finding, `problem`, `impact` and its
resolutions. `Diagnosis::explanation` combines these accounts and reported
locations. `findingIndexes` and `DiagnosticCollection::assignments` retain exact
attribution, including repeated locations. Counts refer separately to retained
Findings and grouped diagnoses. IDs are deterministic for the input/run, not
persistent issue identities.

`Resolution::kind` is `technical` for recovery/investigation steps or `option` for
a domain choice. Neither means a guaranteed complete fix. `files` lists only
reported files or discovered descriptors; `configuration` lists known logical
addresses such as `sillbeam.modules["project:shop:catalog"].enabled`, never guessed
configuration filenames. Fileless steps are permitted when evidence is missing.
All changes require fresh analysis. Nothing is edited or executed by diagnostics.

## Context-specific cases

| Case | Evidence and resulting communication |
| --- | --- |
| Undeclared direct dependency | Names the actual source descriptor and target descriptor class (`TargetModule::class`). An option to update the `dependencies` attribute is offered only with complete, resolved endpoint/activation evidence, no return path and no private boundary. It warns about changed activation and asks for reanalysis. |
| A proposed declaration closes a cycle | Shows the directed return path and resulting closed chain, with the proven declaring descriptors. Offers a domain decision about dependency direction or moving contracts, never an unconditional declaration fix. |
| Existing dependency cycle | Shows a verified ordered DFS witness and all source descriptors. Sillbeam does not know which coupling is wrong for the domain. Witnesses do not enumerate all elementary cycles. |
| Private access | Names the known owner Project and explains that declaring a dependency grants no access. Disabling/enabling and ownership are distinct: enable advice is suppressed when the requesting Project cannot legally access the private module. |
| Disabled module | Uses resolved enabled state and its **actual owning scope** for the canonical `shared:<id>` or `project:<project-id>:<module-id>` override. The requesting Project is never substituted for the owner. Ambiguous identities provide no supposedly unique override address. |
| Enabled but unassigned module | Explains Project assignment instead of recommending an enabled override. Names source Project descriptors when activation membership proves them; assignment remains a domain decision. Unknown states remain unknown. |
| Unknown dependency/Project module | Names the reported target class and known declaring descriptor, then asks to check declarations and configured discovery roots/excludes. Does not invent a descriptor for the missing target. |
| Host configuration | Names the reported host and known Project configuration addresses. Duplicate-host communication uses the actual configured host snapshot, including all known owners. Host selection is a domain decision. |
| Duplicate Module/Project identities | Shows the known competing descriptors and actual scope. Choosing IDs is a domain decision; ambiguous configuration addresses are not presented as unique. |
| Stale configuration keys | Names the reported module override or Project ID address and asks to compare it with discovered identities, correct it or remove stale configuration. No YAML filename is guessed. |
| Application-owned unknown class | Names the reported class/location. Explains the bounded indexed/loaded/Composer-mapped application check, not complete PHP autoloading, external package validation or Symfony autowiring. |

Group only proven relationships/causes. References to the same disabled module,
multiple declarations of the same scoped identity, or duplicate assignments of
the same configured host can share a diagnosis. Equal rule codes or filenames
alone do not establish one. Identical resolution steps/kind/configuration are
merged within the group and their known file evidence is combined.

## Deliberate fallbacks and incomplete evidence

Discovery/descriptor failures often stop discovery before a usable catalog exists.
Their diagnoses use the original message, reported file/class and rule semantics:
PSR-4 identity, loaded-file mismatch, invalid attribute arguments or derived ID,
multiple descriptors/attributes, boundary, missing ownership or overlapping areas.
They ask to inspect or separate the **reported** declarations/areas. They do not
invent a second descriptor path, an expected namespace not provided as structured
evidence, a missing Project, or a safe activation/graph modification.

`source.parse` uses the reported parser location/message; `source.read` asks to
check the actual reported file/access; `source.duplicate_class` names the symbol
but does not invent the unreported competing file. `report.execution` preserves
the original exception evidence and asks to repair that execution/configuration
failure; it does not classify the architecture as valid or invalid by inference.

Any fatal Finding marks communication **Analysis incomplete** and takes priority
because reliable continuation depends on recovery, not because its rule code has
a fixed rank. Known nonfatal Findings remain intact but their next steps defer
dependency/activation changes until fatal blockers are resolved and analysis is
rerun. Positive declared paths can still be shown. Missing paths, activation and
ownership cannot be inferred from an incomplete or absent context.

Every current rule has dedicated communication, including a context-free fallback.
Missing file/line, module identity, Project scope or configuration data is left
missing. Unknown future rule codes retain their original message and explicitly
state that no rule-specific explanation is available. See the [analysis data
flow](architecture-analysis.md#internal-diagnostic-foundation) for runtime isolation.
