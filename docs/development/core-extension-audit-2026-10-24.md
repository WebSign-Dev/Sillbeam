# Core v0.2.0 — creation, reporting and dependency evidence audit, 2026-10-24

Base: `dev-v0.2.x` at `6d9318b`, plus the local application-coupling change. This
checks the combined changes since RC commit `150372d`, without creating a release,
tag, merge, commit or push. No Frame sources or runtime architecture model changed.

## Follow-up: application-class false-positive correction

The `code.unknown_class` rule is now limited to provably application-owned
namespace/path mappings from root Composer PSR-4 (`autoload` and `autoload-dev`)
and configured discovery. Vendor paths and more-specific external Composer
prefixes are excluded without hard-coded framework names. Externals are outside
this existence rule, even when Composer's loader is wrapped. Existing application
PSR-4 files can be recognized without loading their code. The regression reproduces
exactly two incorrect `Response` findings without the ownership guard and none
with it; the application's missing constructor type still produces a finding.

Follow-up verification: **260 tests / 1,336 assertions**, no failures/skips;
PHPStan, coding standards, Deptrac and strict Composer validation passed. All
six consumer layouts with/without Doctrine, release ZIP and both Flex variants
passed again. Existing informational application-coupling evidence and module/
Project validation tests passed. The full suite reran the production benchmark
smoke and lifecycle load probes: no analyzer/parser on warm production HTTP.
The full timing comparison below describes the preceding coupling snapshot;
it was not rerun for this classification-only correction. No new runtime hooks
or new architecture policy was added.

## Implemented behavior and combined impact

1. Minimal creation (`a4d108b`): module, Project and private Project-module commands
   use resolved discovery roots/namespaces and create only a directory/descriptor.
   Existing targets, unsafe paths and ambiguous/disabled roots fail. Discovery
   recognizes the results; configuration remains an explicit application task.
2. Reporting and missing-type evidence (`6d9318b`): compact grouped console output,
   unchanged existing JSON fields/0/1/2 exit meanings, atomic Markdown replacement,
   and the container-independent adapter are covered together. Missing application constructor
   types are reported with locations. Resolution remains bounded to indexed/loaded
   types and Composer file lookup; optional attribute names and development helper
   directories retain their documented exemptions. There is no general PHP,
   service-container or autowiring verification.
3. Application couplings (local change): constructor and existing static PHP
   references use the same source index. Target classes in another module remain
   module edges, with unchanged explicit-dependency/activation/private rules.
   Indexed targets outside modules are separate informational evidence. External
   Composer targets are neither scanned nor promoted to internal dependencies.
   No configuration, architecture profile or inferred activation edge is added.

`SourceFileParser` already supplies supported type/reference positions and resolved
names; `SourceInspector` supplies parsed declaration files once per analysis.
`CodeDependencyAnalyzer` adds file classification and deduplicated application
observations to that existing pass. `ArchitectureCompiler` is unchanged. The
additional data travels through `ArchitectureAnalysisResult` to
`ArchitectureReport`, without changing module registries or the runtime graph.
Text and Markdown group target classes and retain source locations. The optional
module JSON fields are `application_dependencies` and `application_references`.
They never enter existing module-edge fields or findings.

## Local checks

Root runtime: PHP 8.5.4, FrameworkBundle 8.1.6, HttpFoundation 8.1.9. Isolated
consumer/Flex resolution used Symfony 8.1.9. PHP 8.4 and the Symfony 7.4 compatibility
matrix were not rerun in this task.

| Check | Actual result |
| --- | --- |
| `composer test` | 252 tests, 1,320 assertions, no failures/skips |
| `composer analyse` | No errors |
| `composer cs:check` | Passed |
| `composer architecture` | 0 violations, 0 warnings, 0 errors |
| `composer validate --strict` | Valid |
| `composer test:consumer` | All six layouts, without/with Doctrine, both kernels passed |
| `composer test:release-package` | Real no-dev Composer ZIP, manual installation, both kernels, cold/warm dev+prod passed |
| `composer test:flex-recipe` | Installation, both kernels and removal passed |
| `SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe` | Same checks with Doctrine passed |
| `git diff --check` | Passed |

New targeted tests cover constructor references to modules/application/vendor,
existing explicit versus observed dependencies, case-insensitive evidence
uniqueness with multiple source locations, disabled sources/targets, private
Project boundaries, file-based classification with vendor-like namespaces, and
Composer classes outside the source index without executing their files.
Integration tests cover informational data in JSON/text/Markdown and unchanged
exit status. Existing creation, invalid-container reporting, atomic writing,
unknown-class and production-lifecycle regressions all remain in the full suite.

Independent read-only review of the implementation, schema, tests and this audit
reported no important findings. No runtime/registry/Project model changes or warm
production HTTP hooks were introduced.

## Performance comparison

The full unchanged paired benchmark was run after the quality/consumer jobs had
finished, with no parallel test/quality jobs. See [raw samples](benchmarks/core-0.2.0-2026-10-24.jsonl)
and the [RC method/reference](core-benchmark-2026-10-23.md). Tool and root-lockfile
SHA-256, PHP/Symfony/HttpFoundation versions, CLI OPcache/JIT settings match the RC
metadata. The same 10/50/100-module workload uses 3 build samples and 150 warm HTTP
samples per row. Host workload and OS caches are not isolated. Numbers describe
this run; they do not establish statistical significance or attribute changes to
one implementation step.

All expected routes/responses were verified. Analyzer and PHP parser were absent from **every production HTTP subprocess** in both variants. The existing lifecycle tests also confirm generic 503 for cold unbuilt production HTTP and successful prebuilt warm requests without analysis. No production HTTP hooks were added.

### Container builds (milliseconds, p50)

| Modules | Env | Variant | RC cold | Current cold | RC cached warmup | Current cached warmup |
| ---: | --- | --- | ---: | ---: | ---: | ---: |
| 10 | dev | symfony | 433.94 | 442.29 | 66.57 | 69.41 |
| 10 | dev | sillbeam | 762.57 | 753.15 | 259.82 | 263.31 |
| 10 | prod | symfony | 353.65 | 350.38 | 56.31 | 56.90 |
| 10 | prod | sillbeam | 653.87 | 647.96 | 235.73 | 241.80 |
| 50 | dev | symfony | 1075.02 | 1045.63 | 91.76 | 93.55 |
| 50 | dev | sillbeam | 2601.66 | 2623.71 | 783.59 | 802.30 |
| 50 | prod | symfony | 894.86 | 858.52 | 60.94 | 56.99 |
| 50 | prod | sillbeam | 2309.54 | 2259.80 | 705.40 | 707.68 |
| 100 | dev | symfony | 1944.79 | 1958.15 | 121.66 | 125.15 |
| 100 | dev | sillbeam | 5045.22 | 5020.27 | 1453.89 | 1489.42 |
| 100 | prod | symfony | 1589.24 | 1559.95 | 57.88 | 59.36 |
| 100 | prod | sillbeam | 4409.46 | 4493.31 | 1285.34 | 1343.43 |

### Production HTTP (milliseconds)

| Modules | Variant | RC warm p50 | Current warm p50 | RC warm p95 | Current warm p95 | RC / current HTTP peak MiB |
| ---: | --- | ---: | ---: | ---: | ---: | ---: |
| 10 | symfony | 0.716 | 0.705 | 0.907 | 0.825 | 10 / 10 |
| 10 | sillbeam | 0.713 | 0.705 | 0.890 | 0.791 | 10 / 10 |
| 50 | symfony | 1.627 | 1.434 | 3.321 | 2.840 | 18 / 18 |
| 50 | sillbeam | 1.488 | 1.481 | 3.210 | 3.009 | 18 / 18 |
| 100 | symfony | 3.397 | 3.626 | 5.052 | 5.238 | 28 / 28 |
| 100 | sillbeam | 3.404 | 3.625 | 5.031 | 5.273 | 28 / 28 |

At 100 production modules, the Sillbeam cold build changed by +1.9% and cached warmup by +4.5% in this run. Warm HTTP p50/p95 were 3.625/5.273 ms versus RC 3.404/5.031 ms. The native 100-module production HTTP p50/p95 also rose, from 3.397/5.052 ms to 3.626/5.238 ms. Sillbeam 100-module production allocated peaks were unchanged at 60 MiB for cold build, 20 MiB for cached warmup, 6 MiB for warm boot and 28 MiB for the HTTP batch. These are descriptive differences within the stated workload and sample limits, not a general performance claim.

## Remaining limits and risks

- The benchmark has no Project/Doctrine/database workload and no substantial
  nonmodule coupling graph. It checks aggregate cost on the same RC workload;
  large application-coupling catalogs can add analysis/report size and container
  build storage. Measure a representative application before capacity claims.
- Only application target declarations visible to discovery are classified.
  Excluded/generated sources, runtime aliases and application Composer mappings
  outside discovery may have no application-coupling evidence. Vendor sources are
  not scanned to fill those gaps.
- JSON schema version 1 is extended with optional fields only, retaining all old
  required keys/types. The updated schema accepts old reports. Consumers validating
  new coupling reports against the old pinned `additionalProperties: false` schema
  must update that schema. Existing module-edge fields retain their meaning.
- Project-owned application code outside private modules is informational coupling;
  this change adds no new policy for access to that code. Existing private-module
  rules, disabled-module checks and explicit dependency declarations still apply.
- Build/report validation may execute descriptor metadata as before. Composer
  lookup does not execute external target files, and file presence does not prove
  every external declaration or runtime service is correct.
- Multi-Project creation still needs manual host configuration; commands never
  generate it. Markdown replacement still requires an existing writable parent
  directory and successful same-directory temporary writing/rename.

