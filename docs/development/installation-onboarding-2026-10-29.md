# Installation and onboarding verification — 2026-10-29

This records the 0.3.0 onboarding changes against the existing 0.2 installation
mechanisms. Verification was performed on the working tree. No package tag,
public Flex submission, remote write or release was created.

## Scope

The standard path now selects the existing Core Console adapter before Doctor,
then uses Doctor's manual integration steps and the existing module creation
command. The guide ends with a real autowired Greeting service and `/hello`
controller, not only a descriptor. The recipe manifest, route payload, runtime
service registration, architecture validation and Console dispatch are unchanged.
Only Doctor's diagnostics/static path handling and installation guidance change.
No dependencies or new command/JSON mode were added.

Doctor names missing bundle/route integration, duplicate Sillbeam imports and
conflicting service import paths. Conventional single finite brace groups in
exclusions are recognized without filesystem enumeration, including before module
directories exist. Wildcards (including character classes), nested/incomplete
braces, unknown parameters, PHP configuration, imports, environment overlays,
custom kernel hooks and ambiguous bootstraps remain unverified. Exit 2 continues
to take precedence over action items. Existing tests still cover Runtime callbacks,
standard kernels, independent routing uncertainty and service conflicts.

## Environment and results

Host: PHP 8.5.4, Composer 2.9.5; SQLite enabled for Doctrine integration tests.
Core is installed from a copied local path package with simulated version 0.3.0;
the existing minimum recipe directory is `0.2`. No root development autoloader is
used by the consumer processes. Versions below are the ones actually resolved,
not a claim about every supported PHP/Symfony combination.

| Symfony | Consumer before Core | Doctrine | Result |
| --- | --- | --- | --- |
| 8.1.10 | Fresh, one Composer installation | Absent | Passed |
| 8.1.10 | Installed and booted Symfony application | Absent | Passed |
| 8.1.10 | Fresh, one Composer installation | DoctrineBundle 3.3.4 / ORM 3.7.5 | Passed |
| 8.1.10 | Installed and booted Symfony application | DoctrineBundle 3.3.4 / ORM 3.7.5 | Passed |
| 7.4.22 | Fresh, one Composer installation | Absent | Passed |
| 7.4.22 | Installed and booted Symfony application | Absent | Passed |
| 7.4.22 | Fresh, one Composer installation | DoctrineBundle 3.3.4 / ORM 3.7.5 | Passed |
| 7.4.22 | Installed and booted Symfony application | DoctrineBundle 3.3.4 / ORM 3.7.5 | Passed |

Each Composer/Flex run checks:

- The actual recipe output, bundle/route registration and `symfony.lock` entries;
  pre-existing services, native routes, comments and console bootstrap remain
  byte-for-byte unchanged by installation.
- Doctor through the ordinary Symfony console reports the missing Core adapter.
  The test explicitly changes only its Application import, as a developer would.
- With the Core adapter selected, missing bundle and routing plus an overlapping
  native service import are diagnosed without boot. All consumer files are hashed
  before/after inspection and remain unchanged, including cache files.
- Explicit test-authored corrections restore the bundle/route and append discovery
  areas to existing exclusions. Doctor then returns 0 from static evidence only.
  An added dynamic PHP configuration returns 2 and is never executed or modified.
- The existing `sillbeam:make:module Hello` creates the descriptor, architecture
  checking succeeds, and the actual guide's Greeting/HelloController snippets are
  copied into the disposable application.
- HTTP `/hello` returns 200 and `Hello from Sillbeam`; a native service survives;
  a disabled module leaks no automatic service. With Doctrine, the active module's
  entity metadata is mapped; without Doctrine, its bundle is genuinely absent.
- Both ordinary Symfony and optional Sillbeam kernels boot with cold/warm caches
  in dev/prod. Removing the package preserves manually maintained application
  files and options; ordinary Symfony boots after explicit application cleanup.

The separate pre-existing route-destination check passed on Symfony 8.1.10 without
Doctrine: the application-authored comment and file contents survive installation.
Flex still tracks that filename for removal; the guide documents this ownership.

The first attempt to select Symfony 7.4 used `7.4`, which Composer treats as exact
7.4.0 and rejected under its security policy. The successful matrix uses `7.4.*`
and the latest eligible patches. Security blocking was never disabled. Two fixture
issues found while building the matrix (empty JSON objects and preservation of
literal `class::class` bundle syntax) were corrected in the test harness, rather
than weakening Doctor's static evidence requirements.

## Checks actually run

| Check | Result |
| --- | --- |
| `composer test` | 558 tests, 6,658 assertions, successful |
| `vendor/bin/phpunit --filter 'DoctorTest\|FlexRecipeTest'` | 37 tests, 1,005 assertions, successful |
| `composer analyse` | PHPStan level 8, no errors |
| `composer cs:check` | No files requiring formatting |
| `composer architecture` | 0 violations, 0 warnings, 0 errors |
| `composer docs:build` | phpDocumentor 3.10.0, successful |
| `composer validate --strict` | Valid |
| `composer test:release-package` | Local Composer ZIP/manual consumer, actual documented Hello, both kernels and dev/prod cold/warm caches, successful |
| `git diff --check` | Successful |

Regression tests were observed failing before the implementation for brace
exclusion verification, contextual repair instructions, duplicate route imports
and glob-character-class uncertainty. They pass with the implementation. Doctor
and recipe tests assert read-only behavior and unknown-state precedence. An
independent read-only review found no material issues; its namespace typo in the
Flex guide was corrected. All 67 local file/heading links in changed documentation
were checked successfully.

## Reproduce the Composer/Flex matrix

From the repository root, with Composer network access and permission to open a
local HTTP port:

```bash
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
SILLBEAM_TEST_FRESH=1 composer test:flex-recipe
SILLBEAM_TEST_FRESH=1 SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

Repeat those four commands with `SILLBEAM_SYMFONY_VERSION='7.4.*'`. Set
`SILLBEAM_TEST_FRESH=1 SILLBEAM_TEST_EXISTING_ROUTE=1` for the preserved route-file
case. These flags affect only disposable test applications. The local endpoint
uses HTTP on loopback; Composer's public package downloads and security policy
remain enabled. The test cleans up its consumers/endpoints afterwards.

This verifies local installation behavior, not publication of Core 0.3.0 or
acceptance of the recipe on a public endpoint. PHP 8.4 was not executed in this
session. Dynamic configurations intentionally remain a manual review boundary.
