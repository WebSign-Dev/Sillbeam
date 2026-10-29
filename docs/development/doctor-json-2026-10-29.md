# Doctor JSON v1 verification — 2026-10-29

Verified against the existing installation/onboarding behavior on PHP 8.5.4.
No commits, pushes, tags, releases or dependency changes were made in this task.

## Contract and implementation

The [contract](../reference/doctor-json.md) and normative
[Draft 2020-12 schema](../reference/doctor-report.schema.json) define the envelope,
seven stable check-category IDs, the unchanged literal statuses and exit priority,
summary counts, original diagnostic details and command error codes. Repeated IDs
preserve independent Inspector observations rather than overwriting them.

`InstallationInspector` remains the only implementation of installation checks.
Its rows gain explicit IDs. `DoctorReport` serializes those rows and shares the
existing status aggregation with text output. Neither format executes application
configuration or repairs files. Unknown/dynamic evidence remains unverified.

`DoctorCommand::run()` handles binding failures before inspection. Both the command
and the Core adapter route Doctor failures to its own schema, never architecture
JSON. The adapter defers kernel-derived path resolution until command preparation
is inside that error boundary. JSON scopes discard incidental PHP output, restore
output/error handlers and use raw JSON writes. The adapter also covers output from
Doctor preparation failures. JSON escaping and invalid-UTF-8 substitution preserve
a parseable document without active ANSI bytes on STDOUT.

A review identified a mismatch between Symfony's last repeated `--format` value
and `getParameterOption()` returning the first. A shared resolver now honors the
last exact long option, equals/separate spellings and `--`. An explicit empty
`--format=` remains empty, as Symfony parses it. Repeated flags, empty values and
format tokens after `--` have regression coverage. The defect and the preparation
output leak were both observed failing before their fixes.

## Tests actually run

| Check | Result |
| --- | --- |
| `vendor/bin/phpunit --filter 'DoctorJsonTest\|DoctorReportTest\|DoctorTest\|ArchitectureReportingTest\|ArchitectureCommandOptionsTest'` | 74 tests, 2,247 assertions, successful |
| `composer test` | 571 tests, 7,775 assertions, successful |
| `composer analyse` | PHPStan level 8, no errors |
| `composer cs:check` | No formatting changes required across 190 files |
| `composer architecture` | Zero violations, warnings or errors |
| `composer docs:build` | Successful |
| `composer validate --strict` | Valid |
| `git diff --check` | Successful |

The PHPUnit tests cover all three individual statuses and mixed observations,
summary/exit priority, duplicate IDs with separate statuses, malformed configuration
file diagnostics, Inspector-to-JSON equivalence and read-only snapshots. They also
cover default/explicit text equivalence, invalid options/arguments/format values,
missing values, repeated format flags, quiet/help, invalid UTF-8 and literal markup,
warning/exception conversion, output-buffer/error-handler restoration and adapter
preparation failures.

Real subprocess tests use both ordinary Symfony and the Core Console adapter,
checking STDOUT/STDERR separately. They cover JSON with `--ansi -vvv`, usage failures,
`--quiet`, `--help`, help's own `--format` semantics and supported option placement.
A kernel that refuses boot confirms the Core adapter's container-independent path.
Existing architecture command/reporting tests continue to pass.

An independent Draft 2020-12 validator (`python3-jsonschema`, verification tool only)
validated the schema itself, seven actual serialized Inspector/error reports and
the documented command-error example. Invalid schema versions, states and mismatched
exit codes were rejected. This added no project dependency. PHPUnit separately
checks the schema's fields, types, ID/status catalogs and error shapes against
serialized command results.

The default text renderer and exit codes were compared byte-for-byte with the
Doctor command from the pre-change commit (`bb6f423`) on verified, action-required,
unverified and mixed fixtures. All four outputs and exit codes are identical.
The old command was loaded under a test-only class name in `/tmp`; no legacy or
second Inspector implementation was added to the repository.

## Real Composer/Flex consumers

The existing consumer test now also decodes Doctor JSON through native Symfony
and the Core adapter, before and after manual corrections and with dynamic config.
It checks schema version, stable IDs, state/exit agreement and no writes. Existing
HTTP, service activation, mapping, both-kernel, cold/warm dev/prod and uninstall
checks still run.

| Consumer | Resolved components | Result |
| --- | --- | --- |
| Fresh, without Doctrine | Symfony 7.4.22 | Passed, repeated after format-resolution fix |
| Existing Symfony app, with Doctrine | Symfony 8.1.10, DoctrineBundle 3.3.4, ORM 3.7.5 | Passed |

Reproduction:

```bash
SILLBEAM_SYMFONY_VERSION='7.4.*' SILLBEAM_TEST_FRESH=1 composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

These use copied local Core source with a simulated 0.3.0 version and a loopback
Flex endpoint. They install real external dependencies but publish nothing.
Composer security blocking stays enabled. PHP 8.4 was not run in this session.

## Deliberate CLI boundaries

Native Symfony startup happens before Doctor is created; an application boot
failure or application output at that stage is outside Doctor JSON. The Core
adapter handles Doctor without that boot and includes preparation failures in its
schema. Native debug events can appear on STDERR at high verbosity; STDOUT remains
the result, so JSON consumers must not merge streams.

Help is Symfony command metadata rather than an inspection. Standalone `--help`
works in both entries. The adapter preserves text help; native Symfony's HelpCommand
owns help `--format` (`txt`, `json`, etc.). The contract documents that distinction
and recommends command-first Doctor arguments. These are explicit boundaries, not
additional inspection logic or claims that unknown configuration is verified.
