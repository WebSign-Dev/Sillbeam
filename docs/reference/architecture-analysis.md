# Architecture analysis

`Sillbeam\Core\Architecture\ArchitectureAnalyzer` is the reusable entry point for the existing Module, Project and static dependency rules. It accepts `DiscoveryOptions` and the resolved architecture configuration already used by `ArchitectureCompiler`. It does not require a Symfony container or console application.

```php
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\DiscoveryOptions;

$result = (new ArchitectureAnalyzer())->analyze(
    new DiscoveryOptions($projectDir.'/src', 'App\\'),
    ['modules' => [], 'projects' => []],
);

foreach ($result->findings as $finding) {
    // Scalar data suitable for a future CLI or report serializer.
    $data = [
        'rule' => $finding->rule,
        'severity' => $finding->severity,
        'message' => $finding->message,
        'context' => $finding->context,
    ];
}
$result->throwIfInvalid();
$architecture = $result->architecture;
```

`ArchitectureAnalysisResult` exposes immutable findings, discovered input and observed code references. `isValid()` means no findings. `isComplete()` means no fatal blocker prevented further reliable checks. A completed run may still contain errors. `architecture` is available only for valid analysis; invalid or incomplete runs never expose a runtime graph. References accompanying an invalid result are evidence, not an approved dependency graph.

Each `ArchitectureFinding` contains `rule`, `severity`, `message` and a scalar `context` map. Available context includes descriptor/module/project class, file, dependency target, host, or source-reference line. Descriptor graph checks have no line in their existing discovery metadata; no line number is invented. File/line locations are present for parser errors and static code references when available. Configuration-only failures may have no file or module.

## Existing checks and identifiers

| Rule identifiers | Existing checks |
| --- | --- |
| `module.unknown_override`, `project.unknown_configuration` | Configured module/project must exist |
| `module.duplicate_key`, `project.duplicate_id`, `module.owner_missing` | Unambiguous logical identities and project ownership |
| `dependency.unknown`, `dependency.private`, `dependency.cycle`, `dependency.disabled` | Declared targets, private boundaries, acyclic graphs, enabled activation closure |
| `project.unknown_module`, `project.private_module` | Project activation must reference existing and permitted modules |
| `project.host_invalid`, `project.host_required`, `project.host_duplicate` | Exact, required and unique Project hosts |
| `code.private`, `code.inactive`, `code.undeclared_dependency` | Existing static cross-module access/dependency checks for active modules |
| `discovery.module_boundary`, `discovery.project_boundary`, `discovery.owner_missing`, `discovery.project_inside_module`, `discovery.module_overlap`, `discovery.project_overlap` | Existing discovery and ownership boundaries |
| `discovery.descriptor_class`, `discovery.descriptor_file`, `discovery.multiple_descriptors`, `discovery.multiple_attributes`, `discovery.descriptor_metadata`, `discovery.descriptor_id` | Usable, unambiguous PHP descriptors |
| `source.parse`, `source.read`, `source.duplicate_class` | Readable PHP and an unambiguous source-symbol index |

Identifiers label existing checks and analysis failures; they add no architecture model or new architectural restrictions.

## Continuation and ordering

- `error`: a rule violation. Independent graph, host and code checks continue. Unknown edges are reported at the declaring module and skipped during traversal; cycles terminate through visited-node tracking. Disabled or inaccessible nodes are not activated to continue the analysis.
- `fatal`: source or ownership/identity ambiguity makes subsequent checks unreliable. All independently parseable files are examined for parse/read/attribute failures first; if any fail, discovery does not continue. Discovery-area ambiguities stop discovery. Identity failures still permit independent declared-dependency and cycle checks, but prevent ambiguous configuration/activation and code analysis. Duplicate symbols stop code analysis. Earlier findings are preserved.
- Only expected parser, descriptor and discovery failures are converted. Arbitrary exceptions from application descriptor execution and internal programming errors propagate. Configuration/option resolution before the analyzer retains its existing Symfony/PHP exceptions.

Findings are sorted by rule, file, numeric line and canonical remaining context, then severity/message. Context keys are sorted before deduplication; identical rule/severity/message/context tuples appear once. Repeated references to the same PHP symbol on the same source line are deduplicated case-insensitively. Separate source lines and separate rule violations remain distinct. Cycle checks traverse sorted classes/dependencies and report detected cycles without enumerating every possible path. Default analyzer instances use fresh source indexes and collectors on each run.

## Strict entry points and compatibility

The bundle's container build now uses the same analyzer and throws `ArchitectureValidationException` containing the complete available result in `$exception->result`. The exception extends `LogicException`, preserving existing catches and descriptive message fragments. Its message contains one `[rule] description` per finding; fatal parser/descriptor causes are retained through `getPrevious()`.

`ArchitectureCompiler::compile()` and `CodeDependencyAnalyzer::analyze()` remain strict and aggregate their own findings before rejecting invalid input. The `collect()` methods and mutable collector are internal implementation details; candidate graphs from them must not be used at runtime.

The shared result also powers [CLI architecture reporting](cli.md), including the entry point that works without a compiled container. CLI names, configuration alias, services, routing and Doctrine behavior remain unchanged.

## Verification for this change

On PHP 8.5.4, the full suite passes with 186 tests / 829 assertions against locked Symfony 8.1.6 and an isolated Symfony 7.4 installation. The 25 new test cases cover single/multiple violations, cycles and unknown edges, stable ordering/deduplication, inactive/private modules, fatal source/descriptor/identity failures and strict kernel boot with/without Doctrine. PHPStan, Deptrac, coding standards, strict Composer validation, twelve isolated consumer installations (both kernels, with/without Doctrine) and Flex installation/removal with Doctrine also passed. PHP 8.4 was not rerun in this step.
