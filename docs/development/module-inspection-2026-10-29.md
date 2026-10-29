# Targeted module inspection verification — 2026-10-29

This records the text-only `sillbeam:inspect:module` addition for Core 0.3.0.
No commits, pushes, tags, releases or dependency changes were made in this task.

## Implementation boundary

The command consumes the existing `ArchitectureReport`, produced from
`ArchitectureAnalyzer` and `ArchitectureAnalysisResult`. Container-backed execution
uses the existing compiled report; the Core Console adapter uses the existing
`KernelArchitectureReporter` provider without compiling the container.

The only new report data is internal scalar presentation metadata for canonical
configuration keys/provenance and reference completeness. Canonical keys reuse
`DiagnosticContext::moduleConfigurationKey()`. Public architecture JSON and its
schema remain unchanged, covered by a direct serialization-equivalence assertion.
No analyzer, discovery, source parser, graph compiler or validation rule changed.
Incoming relationships reverse existing direct declarations/reference evidence;
cycle membership is read from the original finding context, not recomputed.

The [reference](../reference/module-inspection.md) documents case-insensitive ID/full
class selection, private ownership and shared Project context, ambiguity handling,
configuration precedence, evidence limits and whole-analysis exit codes. The
existing ConsoleStyle supplies headings, escaping, rules and wrapping. No JSON
inspection mode or `--format` option was introduced.

## Checks actually run

Environment: PHP 8.5.4, Composer 2.9.5, root Symfony 8.1 dependencies.

| Check | Observed result |
| --- | --- |
| `vendor/bin/phpunit --filter InspectModuleTest` | 17 tests, 154 assertions, successful |
| `composer test` | 588 tests, 7,938 assertions, successful |
| `composer analyse` | PHPStan level 8, no errors |
| `composer cs:check` | No formatting changes required |
| `composer architecture` | Zero violations, warnings or errors |
| `composer docs:build` | Successful |
| `composer validate --strict` | Valid |
| `git diff --check` | Successful |

An initial PHPStan error was in a test's attempted call to `isCompiled()` on the
container interface. The test now verifies the kernel's boot flag and command
output instead; no production type contract was widened or error suppressed.

## Scenario coverage

- Modules-only ID and descriptor-class selection, including case-insensitive IDs
  and a leading backslash on the class selector.
- Separate configuration availability and validated activation; disabled shared
  and private modules, enabled-but-unassigned modules and canonical override
  precedence over legacy shared IDs. A legacy ID literally named `none` remains
  distinguishable from no configured override.
- Shared modules active globally but unassigned to a selected Project; Multi-Project
  contexts show actual per-Project memberships without changing assignments.
- Private modules with the same ID in different Projects; `--project` ID/class
  disambiguation, wrong-owner rejection and fully qualified class selection.
- Shared/private collisions remain ambiguous even with a Project; descriptor class
  selection resolves them without implicit scope priority.
- Duplicate logical identities retain unknown configuration identity/activation.
- Separate declared and observed direct dependencies, incoming declarations,
  incoming observed references and verbose source locations.
- Cycles are inspectable from every recorded member, including a member that is not
  the finding's primary module. Actual activation remains unknown because an
  invalid candidate graph is never published as runtime state.
- Partial catalog evidence and fatal source errors: no invented activation or
  reference absence; unavailable discovery explains the existing blockers rather
  than claiming a module does not exist.
- Unrelated nonfatal findings stay out of module findings; global failure and the
  exit code still reflect the whole analysis.
- Invalid usage never invokes the report provider. Quiet retains the exit code;
  unknown modules/Projects, missing option values and unsupported JSON flags fail
  with exit 2. Ordinary Symfony registration and help are covered.

Real Core Console subprocesses inspect a fixture with both invalid architecture
and an unresolvable Symfony service. They verify target output, Project options
before/after the command name, quiet/help, text-only failure handling and empty
STDERR at normal verbosity. The adapter path also verifies that the kernel did
not boot. No separate discovery or fallback dependency engine is used.

The initial feature tests were observed failing before implementation. A further
regression test exposed ambiguity between a real legacy override named `none`
and the no-override presentation sentinel; null now represents absence internally
and configured keys are labelled explicitly. The corrected test passes. Another failing regression test showed that an existing
`dependency.disabled` finding must also appear when inspecting its direct declaring
module. The filter now includes that existing finding using the recorded declaration;
it performs no additional validation and leaves actual activation unknown.

## Real Composer/Flex consumers

The existing consumer smoke test also runs the new command through ordinary
Symfony Console against compiled reports. It checks active Hello and active/disabled
Off states while preserving the existing service, HTTP, mapping and uninstall
checks.

| Consumer | Result |
| --- | --- |
| Symfony 7.4.22, fresh, no Doctrine | Passed |
| Symfony 8.1.10, pre-existing application, DoctrineBundle 3.3.4 / ORM 3.7.5 | Passed |

Both consumers exercise standard Symfony and optional Sillbeam kernels, dev/prod,
and cold/warm caches. These are temporary Composer path-package installations with
a simulated 0.3.0 version and a loopback Flex endpoint; nothing is published.

```bash
SILLBEAM_TEST_FRESH=1 SILLBEAM_SYMFONY_VERSION='7.4.*' composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

An independent read-only review found no material issues in selection, ownership,
configuration provenance, activation/evidence handling, finding attribution, adapter
routing or JSON isolation. It independently reran the 16 inspection tests successfully.

## Limits

Native production inspection uses the compiled architecture snapshot. The Core
adapter obtains fresh analysis, and existing configuration/descriptor hooks can
execute application code during preparation. No live service-availability audit,
transitive relationship analysis or automatic configuration change is claimed.
Unknown states remain unknown when the required validated evidence is unavailable.
PHP 8.4 and additional dependency combinations were not executed in this session.
