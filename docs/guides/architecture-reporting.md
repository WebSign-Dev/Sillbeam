# Architecture checking and reports

Use an explicit check after editing descriptors, activation configuration or PHP
references. It reads the actual configured discovery roots and source evidence;
it does not edit modules or propose automatic code changes.

```bash
php bin/console sillbeam:architecture
```

A valid result shows a short success status. A single diagnosis is explained
fully. Several diagnoses show a complete compact index and the first diagnosis
in full. Findings are individual rule violations; diagnoses group only findings
with a supported common cause or module relationship. Their counts differ.

```bash
php bin/console sillbeam:architecture --all
php bin/console sillbeam:architecture --issue=1
php bin/console sillbeam:architecture --issue=1 -v
```

`--all` expands every group. `--issue=N` expands one group, including its original
findings; N is a positive number present in that run's index. Numbers apply only
to the current run, so recheck the index after changes. `-v` adds rule codes,
original messages, context and full paths. Default locations are readable relative
paths. `--all` and `--issue` cannot be combined.

```bash
php bin/console sillbeam:architecture --couplings
php bin/console sillbeam:architecture --format=json --no-ansi
```

Application couplings are observed references to application code outside modules.
They are information, not architecture violations, and do not affect exit codes.
They appear in text only with `--couplings`. JSON retains the complete schema-1
report, including available coupling evidence, independently of presentation.
It cannot be combined with `--all`, `--issue` or `--couplings`. Verbosity and ANSI
options add no headers or ANSI bytes to JSON. `--quiet` suppresses either output.

## Export a document

```bash
php bin/console sillbeam:report
php bin/console sillbeam:report --output=docs/architecture-report.md
php bin/console sillbeam:report --couplings --output=architecture-report.md
```

The default is `architecture-report.md` in the kernel's project root. Relative
paths use that root even when the working directory differs. Absolute local paths
are accepted. The parent directory must exist. Existing path validation remains
in force; no directories are created. A complete temporary file in the same
directory replaces the destination atomically. Failure preserves the old report
and removes temporary files. `--quiet` hides status output but still writes.

The document includes version, UTC check time, status, completeness, separate
finding/diagnosis counts, catalog overview and every complete diagnosis. Original
findings retain their rule, severity, message, context and locations. Recommendations
come from the same engine as the CLI, with domain choices labeled explicitly.
The document separates **Architecture Summary**, **Architecture Overview**,
**Diagnoses & Resolutions** and **Technical Evidence**. Overview tables contain
readable module/Project labels, not absolute paths or FQCNs. Private modules include
their owning Project; name collisions receive IDs and, when needed for invalid
duplicates, local ordinals. Those labels are not PHP class expressions. Long
lists remain complete below the tables; unusually long names get their own list
entry. Technical evidence maps labels to full original identities and files.
Diagnosis locations use the known readable relative paths; original full paths
remain in the evidence section. Each diagnosis keeps the engine's reason and
next steps without repeating the explanation in the technical section.

See the [generated example reports](../examples/architecture-reports/README.md)
for small, large, incomplete and colliding-name catalogs.

The optional `--couplings` appendix uses the same source/target evidence as CLI
couplings. Without it the document contains no coupling appendix.

## Read the status before relying on the graph

| Exit | Meaning |
| --- | --- |
| 0 | Valid architecture; no general container-health guarantee |
| 1 | Completed analysis found violations |
| 2 | Unavailable/incomplete analysis, invalid command usage or report write failure |

A violation does not produce an approved compiled graph. An `unavailable` result
means fatal discovery, source/configuration or execution blockers prevented a
complete check. Available declarations and findings remain useful evidence, but
unknown activation is not disabled or unassigned. An empty incomplete catalog
is not proof that the application contains no modules. Resolve blockers and rerun.
When technically possible, violations and unavailable results still write a
clearly marked report. A failed write cannot produce a report.

## Report without a compiled container

In the application's existing `bin/console`, replace the Symfony Console
Application import; retain its existing Runtime/Dotenv bootstrap and kernel:

```php
use Sillbeam\Core\Console\Application;

return function (array $context) {
    $kernel = new App\Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new Application($kernel);
};
```

Then the implemented commands remain usable when architecture violations or
unrelated service definitions prevent container compilation:

```bash
php bin/console sillbeam:architecture --all --env=prod --no-debug
php bin/console sillbeam:report --output=architecture-report.md --env=prod --no-debug
php bin/console sillbeam:report --help
```

Use the exact command names. The adapter prepares effective configuration without
booting/compiling the service container. It cannot bypass a broken configuration
loader or an unsupported kernel preparation hook; those yield `unavailable`.
Ordinary commands retain Symfony's normal boot behavior. Without the adapter,
reporting commands require a compilable container. No new source analysis runs
in warm production HTTP requests.

See the [CLI reference](../reference/cli.md), [diagnostic boundaries](../reference/diagnostics.md)
and [analysis API](../reference/architecture-analysis.md) for the contracts.
