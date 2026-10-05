# Execution ledger — plan: docs/superpowers/plans/2026-10-01-nexum-evolution.md

Ruling: The user's explicit request to implement the whole reviewed plan supplies execution authorization; no repeated design approval round. Execute inline on codex/nexum-evolution in the existing checkout, preserving unrelated local files. Branch creation needed sandbox escalation and succeeded. No publishing, merging or Strata product creation.

Pre-flight: Tasks 2/3 share SourceFile/SourceInspector; tasks 3/4/5 share CompiledArchitecture metadata. Retain old constructor positions and add defaulted evidence fields to keep registries and test builders compatible during migration.

Baseline: Previous audit: PHPUnit 48 tests/445 assertions, PHPStan/Deptrac/style/Composer checks green.

Task 1: complete — four regression tests RED→GREEN; full original suite plus regressions: 52 tests/455 assertions green. Native FileLoader requires explicit final interface-alias registration when its registerClasses API is used directly.

Task 2: core implementation complete — 18 discovery/architecture/resolver tests green. Attributed arbitrary filenames, multiple roots, per-root exclusions, alternative integrations, orphan private and overlapping area errors implemented. Nested fixtures now include actual parent descriptors to validate ownership rather than directory spelling.

Task 3: core implementation complete — six static-reference tests RED→GREEN. Aliased types, real references, unused imports/descriptor metadata, transitive-only declaration, disabled targets and shared-to-private access covered. SourceFileParser is separate from filesystem indexing.

Task 4: shared build and Foundation removal implemented — three new kernel tests RED→GREEN (10 assertions). Original Foundation tests removed with their product scope; retained fixture converted to native Doctrine entities/controllers. Full suite and Composer lock checks in progress.

Ruling: Foundation implementation is removed from Nexum and remains recoverable in Git; no premature Strata package. This is a breaking public API/configuration change and receives an explicit migration guide.

Task 4: complete — full suite after actual lean vendor synchronization: 57 tests/214 assertions green. Composer lock regenerated and installed with scripts/plugins disabled; unused fixture Serializer/Messenger configuration removed after synchronized dependencies exposed it.

Task 5: complete — core diagnostics, file-selected routing, local Doctrine exclusion drivers, build-time env activation, cross-area native aliases, cache-resource tests, docs/migration/recipe and version CI delivered. Before final review: 61 tests/229 assertions green; PHPStan clean, Deptrac zero violations, Composer valid. Fresh Flex consumer installed newest Symfony 8.1.8/PHP-Parser5.9.0 and booted a module route without Doctrine.

Final review: independent read-only reviewer against HEAD 9103b574113a82dc652558137a61b9784975d334 and uncommitted diff identified three Important findings, no minors and no declined-to-judge items.
Final: fixed layered configuration divergence — testLayeredListsUseSymfonyConfigurationMergeSemantics RED→GREEN. Extension passes Symfony-processed options; resolver preserves omitted versus explicit root lists.
Final: fixed root-local excludes leaking through Project imports — testRootLocalExclusionsAlsoApplyToEnclosingProjectServiceImport RED→GREEN. SourceInspector records pruned paths once and all enclosing imports reuse them.
Final: fixed Project-inside-Module ownership overlap — testProjectCannotBeNestedInsideAModuleArea RED→GREEN. Inverse private-module-inside-Project nesting remains supported.
Final fix-pass suite: 64 tests/237 assertions green; fresh PHPStan clean, style 0/118, Deptrac 0 violations, Composer validate strict green. Symfony 7.4 isolated checkout: 64 tests/237 assertions green on PHP8.5.4. Symfony8.0 and final Flex re-verification in progress.

Final validation follow-up: fixed architecture configuration/Composer changes not invalidating router cache — testRoutingResourcesInvalidateWhenArchitectureConfigOrComposerChanges RED→GREEN; suite 65 tests/240 assertions green. Route resources now include application configuration resources, config-directory changes and Composer metadata. No additional review dispatch; covering regression and full checks verify the correction.

Task 6: complete — all three Important review findings fixed with failing regressions then green suite. No deferred minors, no declined-to-judge items, no unresolved findings. Final cache follow-up covered independently by regression.

Final evidence (2026-10-01): PHP8.5.4; Symfony framework-bundle8.1.6 (local),7.4.20 and8.0.15 (isolated temp dependencies), each 65 tests/240 assertions green. PHPStan no errors; Deptrac0 violations/warnings/errors; CS0/118; Composer validate --strict valid; git diff --check clean; final actual Flex consumer installed/booted without Doctrine successfully. PHP8.4 is in the compatibility CI matrix but unavailable locally. CI jobs not remotely executed in this session.

Integration state: changes remain uncommitted/reviewable on codex/nexum-evolution. No push/merge/deployment. Existing user Harbor change and nexum.zip preserved. Plan/spec/progress retained because no commits were requested.
