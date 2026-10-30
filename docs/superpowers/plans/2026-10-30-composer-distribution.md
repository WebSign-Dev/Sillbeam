# Composer distribution cleanup plan

Goal: ship runtime code, metadata/license and useful documentation; keep all internal
historical evidence in Git, outside distribution. Branch dev-v0.3.x only.

Decision: one .gitattributes allowlist for Composer and Git archive. Remove the
competing composer.json archive.exclude. Include src, root public metadata/docs,
and named user-documentation subtrees. Exclude all development/raw evidence,
superpowers plans, placeholder trees, tests/tools/apps/skeleton/recipe sources,
local generated files and unlisted top-level files by default.

- [x] Inspect actual baseline ZIP and current release-package test.
- [x] Add failing archive/content/link-integrity/source-integrity regressions.
- [x] Consolidate policy, inspect actual ZIP and Git export with worktree attributes.
- [x] Convert links to excluded documents/recipe source to verified immutable
  repository commit links; leave historical reports and raw evidence untouched.
- [x] Update stale release/consumer smoke expectations to current CLI contracts
  without changing commands. Verify ZIP docs/examples from installed package.
- [x] Execute release-package, consumers, root tests and relevant quality checks.
- [x] Record precise before/after contents, sizes, link changes, failures and limits.

No source/architecture/CLI/CI-trigger/release-automation changes, commits or pushes.
