# Installation doctor verification — 2026-10-25

Implementation stays on `dev-v0.2.x`. No push, merge or release tag.

## Checks run

- `vendor/bin/phpunit tests/Integration/DoctorTest.php`: 15 tests, 470 assertions,
  passed before the final full-suite run.
- `vendor/bin/phpunit --configuration=phpunit.xml.dist`: 540 tests, 6002 assertions,
  passed (PHP 8.5.4).
- `vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug`:
  passed, no errors. Debug mode avoids the sandbox's blocked parallel TCP listener.
- PHP CS Fixer check of the two Doctor files, Application, extension and DoctorTest:
  no fixable files.
- `vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress`:
  zero violations, warnings or errors; 226 uncovered dependencies reported.
- `git diff --check`: passed.

The full suite prints debug logs and warnings from existing negative tests
(nonexistent source, failed report replacement, temporary-file fallback and
nonempty documentation directory cleanup); PHPUnit reports no failures.

## Covered behavior

A conventional Symfony project, missing adapter, missing Sillbeam route import,
critical service resource overlap, custom discovery roots configured in services
YAML, missing bundle, explicit environment bundle deactivation, service globs,
unresolved parameters/imports, dynamic PHP configuration, unused adapter
construction, comments, unloaded files and other environments.

Regular Symfony registration is exercised against a booted real temporary
consumer. File-content snapshots are compared after boot and command discovery,
before/after command execution. The framework's ordinary boot can write cache
files before the Doctor command runs; the command itself does not write.

The existing Sillbeam Application is tested both directly with forbidden Kernel
initialization hooks and through a real bin/console subprocess with invalid
module architecture. Doctor, help and quiet output work without compilation;
all consumer files retain their hashes, and no application files are added.
PHP configuration containing a write instruction is never executed.

## Limits

Only static evidence is certified. Dynamic or custom configuration is explicitly
not verified and returns 2, taking precedence over detected action items (1).
Runtime callback bootstraps, unresolved imports/overlays and unsupported patterns
require manual review. This test run covers the locally installed dependency
versions; it does not establish the full PHP/Symfony compatibility matrix.
No changes to the Flex recipe, architecture rules, Diagnostic Engine, JSON schema
or HTTP runtime are included.
