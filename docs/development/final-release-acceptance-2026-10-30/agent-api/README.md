# Independent API, JSON and runtime acceptance

Commit: `0b70c3143acf25cae5082f3ec6007b6d2a31da79`. Host PHP 8.5.4. No production files changed.

## Executed checks

- Separate-process PHP reflection of the 20 currently documented `@api` types against v0.2.0 commit `cf2f410`: identical native declared public/protected method parameters (including defaults, references, variadics), return types, property types/flags, constants, inheritance/interfaces/traits and class flags. Baseline source extracted with `git ls-tree` and `git show` into `/tmp/sillbeam-api-v020`; both processes share installed third-party vendor versions, but a prepended source autoloader resolves Core from the selected version. Helpers and both JSON outputs are retained here. Runtime Composer `require` maps are identical.
- Token comparison of all 70 source files already present at the baseline, ignoring whitespace/comments/PHPDoc: 65 executable token sequences identical. The five changed files are ArchitectureReport, Console Application, DoctorCommand, InstallationInspector, and SillbeamCoreExtension. Analyzer/compiler/discovery/dependency rules, source parser/index, runtime registries, context, route loaders, validation lifecycle and compiler passes retain identical executable tokens. ArchitectureReport adds inspection presentation metadata only; serialization still uses its schema-data field. DI adds graph and inspection command registrations; neither renderer nor inspection presentation is instantiated on HTTP paths.
- Architecture JSON schema bytes identical to `cf2f410`; Doctor JSON schema bytes identical to introduction commit `ac8118d`. SHA256 values in `schema-comparison.json`.

Commands (run from repository root):

```sh
vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Integration/ValidationLifecycleTest.php tests/Integration/ArchitectureReportingTest.php tests/Integration/DoctorJsonTest.php
# Exit 0: 44 tests, 1313 assertions
vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Unit/ArchitectureTest.php tests/Unit/ArchitectureFindingsTest.php tests/Unit/ArchitectureReportTest.php tests/Unit/ClassAnalysisConsistencyTest.php tests/Unit/ApplicationCouplingTest.php tests/Unit/ClassReferenceTest.php
# Exit 0: 45 tests, 171 assertions
vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Unit/DoctorReportTest.php
# Exit 0: 2 tests, 51 assertions
vendor/bin/phpunit --configuration=phpunit.xml.dist --filter testProductionHttpNeverLoadsTheSourceAnalyzerWithColdOrWarmCache /tmp/AcceptanceHttpProbeTest.php
# Exit 0: 1 test, 19 assertions
```

The additional standalone test is a copy of the existing lifecycle integration test with extra `class_exists(..., false)` probes and an absolute vendor path. Its source is retained as `AcceptanceHttpProbeTest.php.txt`; production code and checked-in tests were not edited. Cold non-debug HTTP returns 503; a separately compiled cache yields warm HTTP 200 even after invalid sources are introduced. In both fresh subprocesses ArchitectureAnalyzer, ParserFactory, SourceInspector, SvgGraph and ModuleInspectionPresentation remain unloaded. This verifies absence of new analysis/rendering on these runtime paths, not a timing improvement. No performance improvement is claimed.

The 44-test integration run covers valid/violations/unavailable architecture JSON, unknown activation, schema keys, usage failures, normal Symfony and Core-adapted console, Doctor exact default text, read-only static checks, all three statuses and their priorities, quiet/help, malformed configuration, repeated format options, output/error-handler cleanup and subprocess STDOUT/STDERR. The unit runs cover architecture rule/JSON regression and Doctor aggregation.

Logs and machine-readable command/exit/time records are in sibling `../logs/api-*.log` and `../results/api-*.json`. All executed test commands above passed. Exploratory file searches with outdated glob names returned an expected no-such-file diagnostic; no test result was inferred from those searches.

## Limits and risks

Reflection comparison proves native API shape, not all behavioral equivalence. The unchanged executable architecture-rule code plus current regression tests give separate behavioral evidence. PHPDoc additions can change documented descriptions/types without changing native signatures. The extended HTTP probe covers representative cold and warm Symfony lifecycle paths rather than every consumer configuration. Compatibility matrix and realistic graph acceptance are audited separately by the parent task. Public schema bytes are unchanged; tests validate documented fields rather than proving arbitrary third-party JSON consumers.
