# Finalize Flex recipe

Goal: finalize the existing 0.2 recipe without changing production code or publishing.

- [x] Inspect Symfony recipes/contrib documentation, current QA workflow and official checker.
- [x] Add regression tests for source manifest, separate instructions and applicable formatting rules; observe RED.
- [x] Move instructions to post-install.txt and retain only bundle/copy configurators; observe GREEN.
- [x] Extend the existing local endpoint to encode instructions as the official endpoint generator does.
- [x] Run real Flex auto-scripts and assert actual output; preserve pre-existing services, console and routes.
- [x] Validate fresh consumers with/without Doctrine and both supported Symfony lines.
- [x] Run official manifests/YAML/package checks, remaining quality checks and fresh review.
- [x] Document submission prerequisites, local results and public availability limits.

Review focus: source vs generated endpoint format; actual printed instructions;
pre-existing application files; removal ownership; no production source changes.

Results: ../../development/flex-recipe-verification.md. Official manifest/YAML
checks passed; public package validation fails because Packagist has no package.
Fresh review found no implementation issue; verification document added.
Decision: retain standard Flex ownership and explicitly test/document that an
existing recipe destination is preserved on installation but tracked for removal.
No production changes, dependency changes or external write actions.
