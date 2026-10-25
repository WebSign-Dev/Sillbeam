# Module descriptor recommendation verification

Checked on 2026-10-25, PHP 8.5.4. Current Core branch: `dev`, commit
`809a276310cdc634000cf8d936067682c00d8365`. This investigation changes no production
code: the current commit already corrects the reported behavior. Two regression
tests and this report are the only additions.

## Installed consumer versus local Core

The consumer inspected read-only was:

`/home/oeffe/PhpstormProjects/fmz-player-image-generator/backend`

| Evidence | Package | Version/reference |
| --- | --- | --- |
| Consumer composer.json requirement | websign-dev/sillbeam | dev-dev |
| Consumer composer.lock | websign-dev/sillbeam | dev-dev, 67aa651ba34833e3337e4e3186f522cfe9c9947b |
| Consumer vendor/composer/installed.json | websign-dev/sillbeam | dev-dev, 67aa651ba34833e3337e4e3186f522cfe9c9947b, source installation |
| Installed vendor Git HEAD | vendor/websign-dev/sillbeam | 67aa651ba34833e3337e4e3186f522cfe9c9947b |
| Current local Core | websign-dev/sillbeam-core | 809a276310cdc634000cf8d936067682c00d8365 |

The installed DependencyAssessment, DiagnosticRuleCatalog,
ArchitecturePresentation and ReportRenderer files were compared byte-for-byte
with `git show 67aa651:<path>`; all four match. This is an actual version
difference, not merely an assumption based on the development version label.
The installed package name also differs from the current local package name;
updating the old name must not be assumed to install this local commit.
No remote publication or package availability was checked.

The consumer PortraitModule imports both the User Entity and UserModule, but
its dependencies list contains `User::class`. Its UserModule descriptor exists
at `src/Module/User/UserModule.php`. No Composer operation, application command,
cache build, or source edit was performed in the consumer. Tests and reproduction
writes use disposable directories under `/tmp`.

## Root cause and existing fix

The source analyzer assigns an Entity reference to its owning module. The
resulting finding's target is the descriptor `App\Module\User\UserModule`, not
the Entity. DiagnosticContext assesses that descriptor's declared graph,
privacy and activation evidence. DependencyAssessment produces a concrete edit
only when the existing assessment permits offering it as a domain option.

At installed commit `67aa651`, that edit still names the descriptor correctly
before presentation. ArchitecturePresentation.friendly then replaces descriptor
class names with display labels everywhere, including inside PHP `::class`
expressions. It turns `App\Module\User\UserModule::class` into `User::class`.
The Markdown report shares these presented resolution steps and inherits the
same defect. Inside PortraitModule, PHP resolves `User::class` through the Entity
import. The compiler correctly rejects that Entity as an unknown module target.

Commit `809a276` already fixes both concrete expression paths:

- DependencyAssessment.resolution emits `\App\Module\User\UserModule::class`,
  including the leading namespace separator required for copying inside another
  namespace without adding an import.
- DiagnosticRuleCatalog's Project module-selection recommendation uses the same
  rooted descriptor expression.
- ArchitecturePresentation preserves `::class` expressions instead of replacing
  them with labels. Relations and explanatory prose can still display `User`.
- ReportRenderer escapes the same recommendations for Markdown; the rendered
  PHP reference remains the rooted descriptor expression.

`645b35f`, between the installed version and `809a276`, refined Markdown
presentation but did not fix this shortening issue. No new fix is necessary on
the current development stand.

## Executed isolated version comparison

The comparison loaded each version's actual Core sources using the current
installed dependencies. It analyzed disposable `App` fixtures, rendered real CLI
and Markdown output, extracted the literal token following `add` from rendered
CLI output, copied it into PortraitModule with the conflicting Entity import,
and analyzed again in a fresh PHP process.

| Core source | Displayed/copied token | Findings after applying it |
| --- | --- | --- |
| Installed 67aa651 | User::class | code.undeclared_dependency and dependency.unknown |
| Current 809a276 | \App\Module\User\UserModule::class | None; status valid |

The old Markdown also says `add User::class`; the current Markdown retains the
full descriptor reference with appropriate Markdown escaping.

Executed command: `python3 /tmp/sillbeam-descriptor-version-comparison.py`.
Artifacts, including both versions' before/after TXT, Markdown and schema-1 JSON,
are retained at `/tmp/sillbeam-descriptor-comparison-awqtawez`; `comparison.json`
records the asserted outcomes. These are temporary local investigation artifacts,
not shipped fixtures. The installed consumer Core was read, never edited.

## Regression coverage and checks

The new ProductionCliTest case uses the exact classes
`App\Module\User\UserModule`, `App\Module\User\Entity\User` and
`App\Module\Portrait\PortraitModule`, plus a same-named User service. Its real
Symfony consumer executes its own bin/console in fresh processes. It checks
standard output, `--all`, `--issue=1`, `-v`, and the generated Markdown report.
Every concrete dependency suggestion names the rooted UserModule descriptor.
It copies the displayed token while retaining the Entity import: the next JSON
analysis has no findings and exit 0. It separately demonstrates that copying the
old short token produces dependency.unknown.

The new DiagnosticCommunicationTest case covers private User modules in both
Shop and Office. Both CLI and Markdown preserve each distinct descriptor class.
Applying the intended same-Project dependencies passes a fresh analysis. A
subsequent cross-Project reference is still rejected as code.private. Existing
coverage also checks that private-boundary recommendations do not propose adding
a forbidden dependency.

All concrete `::class` edit-producing paths in the diagnostic catalog were
reviewed: direct dependencies and Project module selection. Other descriptor
recommendations ask developers to check declarations, ownership, identities,
paths, or cycles; they do not propose substituting display names as PHP tokens.
The existing Project-selection integration test applies its literal recommendation
and subsequent dependency edit in fresh processes, ending with no findings.

| Actually executed command | Result |
| --- | --- |
| `php vendor/bin/phpunit tests/Integration/ProductionCliTest.php --filter testDescriptorAdviceCannotResolveToTheImportedUserEntity --no-progress` | 1 test, 59 assertions passed |
| `php vendor/bin/phpunit tests/Unit/DiagnosticCommunicationTest.php --filter testSameNamedPrivateDescriptorsKeepDistinctExecutableRecommendations --no-progress` | 1 test, 19 assertions passed |
| `php vendor/bin/phpunit --no-progress` | 525 tests, 5529 assertions passed |
| `php vendor/bin/phpunit tests/Integration/ProductionCliTest.php tests/Unit/DiagnosticCommunicationTest.php tests/Unit/DiagnosticRuleCatalogTest.php tests/Unit/DiagnosticGraphTest.php tests/Unit/MarkdownReportTest.php tests/Unit/ArchitectureRendererTest.php --no-progress` | 234 tests, 3895 assertions passed after formatting |
| `composer analyse` | PHPStan passed, no errors |
| `composer cs:check` | Passed, 184 files checked |
| `composer architecture` | Passed; 0 violations/warnings/errors, 184 uncovered dependencies under existing configuration |
| `git diff --check` | Passed |

The first new private-module test invocation used the renderer's `all` argument
on render instead of its constructor; corrected in the test. The first style
check required global qualification of sprintf in the new integration test;
formatted and checked again. No production workaround or suppression was added.

## Limits and next action

The local Core defect is already corrected. The inspected consumer still contains
the old package and the Entity dependency token; this investigation deliberately
leaves both unchanged. The intended descriptor expression for that token is
`\App\Module\User\UserModule::class`. Replacing it is not a guarantee that all
other independent findings in the actual backend disappear: no full backend
analysis was run, and its other dependencies still require their normal checks.

The version comparison uses the installed old sources with current test
libraries, not a recreated historical dependency environment. New regressions run
against the current installed Symfony libraries; no extra version matrix or fresh
release acceptance was performed. Production CLI design, graph/privacy/conflict
rules, Diagnostic Engine, JSON schema and exit-code contracts remain unchanged.
No commit, push, merge or release tag was created for this investigation.
