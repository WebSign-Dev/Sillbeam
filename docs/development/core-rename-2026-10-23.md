# Core rename verification — 2026-10-23

Starting point: clean `dev-v0.2.0` and local `main`, both at `0600e00`. Changes remain local. No repository rename, remote ref update, publication or release tag was performed.

The new migration tests first failed on the missing Core bundle and old Composer package name. After the mechanical rename they exposed Symfony's derived `sillbeam_core` alias. The extension now explicitly returns `sillbeam`; the bundle overrides extension retrieval to preserve that alias and cache the extension instance.

Observed checks on PHP 8.5.4:

| Check | Result |
| --- | --- |
| Root PHPUnit, locked Symfony 8.1.6 | 161 tests, 724 assertions passed |
| Temporary Symfony 7.4 dependency resolution | Full suite: 161 tests, 723 assertions passed; final migration tests with added instance-cache assertion: 2 tests, 10 assertions passed |
| PHPStan level 8 | No errors |
| Deptrac | No violations, warnings or errors |
| PHP CS Fixer check | No files to fix |
| Composer strict validation | Root and isolated test-app manifests/lockfiles valid |
| Platform requirements | Passed |
| Consumer installations, current Symfony 8.1.9 | All six layouts without/with Doctrine, both kernels passed (12 installations) |
| Local Flex recipe | Install, both kernels, cold/warm environments and removal passed without/with Doctrine on Symfony 8.1.9; additionally without Doctrine on Symfony 7.4 |
| Real Composer ZIP | Documented Hello example, manual install, both kernels, cold/warm dev/prod, no Doctrine or dev dependencies passed |
| Independent diff review | No actionable findings |
| Diff whitespace check | Passed |

The root lockfile changes only its content hash; the test-app lockfile updates its hash and local Core package metadata without dependency version upgrades. Installation checks use simulated local package versions and temporary consumers, not published releases. Historical v0.1 release/verification records retain their original identity.

PHP 8.4 was not rerun for this migration. The configured CI matrix is separate from the results observed here. Expected 404 scenarios in the PHPUnit suite emit Symfony error logs while their assertions pass. The migration adds no database schema changes or Frame/Components functionality.
