# Sillbeam Core v0.2.0 technical migration

Starting point: local main and dev-v0.2.0 at 0600e00, clean tree.

1. Add migration regression tests for Composer identity, renamed bundle/extension discovery and the explicit sillbeam alias. Run them against the old identity to establish failure.
2. Rename all runtime/test namespaces, bundle and extension files, package/repository references, consumer fixtures and local Flex recipe (0.2). Keep configuration, routing type, commands and named service IDs unchanged.
3. Document breaking PHP/Composer changes, bundle registration and upgrade steps; mark v0.2.0 as unreleased and preserve historical release records.
4. Run PHPUnit, Composer validation, PHPStan, Deptrac, coding standards, isolated consumer and Flex installation/removal tests (with and without Doctrine), and distribution archive tests. Inspect the final diff for changes beyond the rename.

Review focus: Symfony derives sillbeam_core from the renamed extension unless explicitly overridden; Bundle also validates its extension alias. Preserve both discovery and sillbeam configuration. Check escaped namespaces in generated PHP/JSON/regex patterns, recipe directory/version metadata, runtime package version lookup, and isolated consumers without root development autoloading.

No remote writes, repository rename, publication, tagging, Frame or Components changes.
