# Core v0.2.0 — final class-analysis consistency audit, 2026-10-24

Reviewed the actual `dev-v0.2.x` working tree, including the existing local
application-coupling extension and preceding `code.unknown_class` correction.
Earlier uncommitted changes were preserved. This audit adds no architecture rule,
configuration option, reporting field or runtime feature.

## Classification and inspected rules

Parser references, the discovery source index, Composer namespace/path ownership,
module ownership, graph compilation and the common report were inspected together.
The code/declaration distinction is intentional:

| Rule | Intended relationship and checked behavior |
| --- | --- |
| `code.private` | Active source module accesses a known private module outside its permitted ownership; a shared source cannot bypass this by declaring the target. |
| `code.inactive` | Active source module accesses a known module outside the active graph, including disabled targets. |
| `code.undeclared_dependency` | Active source accesses a known active other module without a direct declared edge; implicit observations do not grant permission. |
| `code.unknown_class` | An unresolved static application type, with ownership established from Composer PSR-4/discovery. Externals are outside this check; inactive modules retain the previous check/exemptions. |
| `dependency.unknown` | A descriptor explicitly names something that is not a discovered module, even if the named class is a valid external PHP class. |
| `dependency.private` | A declared edge violates private ownership, including shared-to-private and cross-Project private dependencies. |
| `dependency.cycle` | Cycles in declared module edges remain invalid, independently of code observations. |
| `dependency.disabled` | The active dependency closure requires a disabled module; an unused inactive graph is not activated to create an error. |

Known module declarations determine module edges by their actual indexed files,
without a framework namespace blacklist. Indexed nonmodule application targets
remain informational application couplings. External Symfony, Doctrine, PSR and
other package types create no implicit module edges or unknown-application errors.
An external class explicitly inserted into `dependencies` is still an invalid
module declaration; silently accepting it would weaken `dependency.unknown`.

Repeated identical observations on one source line are deduplicated, and logical
targets in reporting are unique. Different source locations retain their evidence.
A declaration error and a code-use error can both be legitimate: for example,
`dependency.disabled` describes the declared active closure while `code.inactive`
identifies the actual use. Tests assert the exact expected rule list to detect
spurious additional findings.

## Issue found and minimal correction

An overlapping Composer-prefix case could hide a real missing application type:

- Runtime prefix `<Application>\Module\Adapter\` pointed to a package directory.
- The more-specific `<Application>\Module\Adapter\Local\` pointed back to the
  application's `src/Module/Adapter/Local` directory.
- A reference to `Local\Missing` was incorrectly treated as external as soon as
  the broader package prefix was encountered.

The new regression failed with no finding before the fix. Ownership selection now
uses the longest applicable runtime prefix, independent of mapping iteration order.
The same regression passes in both mapping orders and reports `code.unknown_class`
with the actual missing application type. The existing broader application/more-
specific vendor exclusion remains intact.

Only this selection inside `CodeDependencyAnalyzer::applicationClass()` changed
in production code for this audit. Correct existing parser, discovery, declaration,
activation, Project, reporting and runtime behavior was left intact.

## Regression and full verification

Root verification used PHP 8.5.4, Symfony FrameworkBundle 8.1.6 and patched
HttpFoundation 8.1.9. Independent consumer/Flex installs resolved Symfony 8.1.9.
PHP 8.4 / Symfony 7.4 matrix combinations were not rerun during this audit.

| Check | Actual result |
| --- | --- |
| Targeted class/rule/report regressions | 51 tests, 228 assertions passed |
| `composer test` | 269 tests, 1,384 assertions, no failures/skips |
| `composer analyse` | No errors |
| `composer cs:check` | Passed |
| `composer architecture` | 0 violations, 0 warnings, 0 errors |
| `composer validate --strict` | Valid |
| `composer test:consumer` | Six layouts, with/without Doctrine, both kernels passed |
| `composer test:release-package` | Real no-dev Composer ZIP and both dev/prod kernels passed |
| `composer test:flex-recipe` | Installation, both kernels and removal passed |
| `SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe` | Same checks with Doctrine passed |
| `git diff --check` | Passed |

Independent read-only review of the focused correction and tests found no important
correctness issue.

New combined tests exercise controller `Response` references, repeated external
uses, Doctrine/PSR constructor interfaces, explicit versus observed module edges,
application service coupling, disabled targets, private code plus private declared
edges, cycles, and duplicate explicit unknown targets. An explicit discovery root
with a vendor-like application namespace verifies that real application modules
are still checked by ownership, rather than mistaken for packages.

Existing regressions also cover custom Composer namespaces, missing own constructor
types, wrapped autoloaders, nonmodule coupling evidence, CLI/JSON/Markdown consistency,
and reporting without a compilable service container. Findings are not filtered
away by renderers; all formats use the same analysis/report result. JSON fields,
schema version and exit meanings 0/1/2 are unchanged by this audit.

The full suite includes the paired production benchmark smoke and production
lifecycle subprocess probes. Warm production HTTP did not load the architecture
analyzer or PHP parser. No HTTP hooks or extra warm-request checks were added.
The full RC timing benchmark was not repeated for this selection-only correction;
previous snapshot timings remain separately recorded in the
[combined extension audit](core-extension-audit-2026-10-24.md).

## Remaining scope and risks

- This is not a PHP linter, signature checker or Symfony autowiring/service-health
  check. File lookup can establish resolution without verifying every external
  declaration or executing target files.
- Empty fallback prefixes cannot prove application ownership. Custom non-Composer
  generated aliases cannot be inferred before they exist; runtime loading remains
  outside the static model. The documented optional-attribute/development helper
  exemptions remain unchanged.
- Application coupling evidence remains bounded to the configured source index;
  unindexed/excluded targets are not recursively scanned to fill it.
- Duplicate findings at genuinely distinct source locations are preserved. Invalid
  explicit descriptors remain errors even when the referenced PHP class is external.
- Local verification covers one root PHP/Symfony combination, with separately
  resolved consumer installations. It does not establish every supported matrix
  combination or real deployment performance.

No unresolved reproduced class-classification or module-rule issue remains in the
covered scenarios. No commit, push, tag or merge was performed.
