# Prepared Symfony Recipes Contrib submission

Target: https://github.com/symfony/recipes-contrib (main)
Upstream base: a6cdd1ae429b2664b441eddb52671564e89dbe90
Local checkout: /tmp/sillbeam-recipes-contrib-submit
Local branch: codex/sillbeam-core-recipe-0.3
The three recipe files are committed locally as
`dc71e95aedb6baa8f94a5518c9b7aa9c1902f57f`. No push or PR.
The /tmp checkout is temporary; this ZIP and patch preserve the handoff.

PR-TITLE.txt contains the exact title; PR-BODY.md is the clean PR body.
submission-0.3.patch adds only the three files under websign-dev/sillbeam-core/0.3/.
Alternatively copy the included websign-dev/ directory into a fresh upstream checkout.
Do not submit this README, PR text, ZIP, patch, package sources or local tests.

Apply in a clean recipes-contrib checkout with:
  git apply --check /absolute/path/submission-0.3.patch
  git apply /absolute/path/submission-0.3.patch
  git diff --check

Official manifest, YAML and package validators passed. Packagist v0.3.0 was tested
with Symfony 8.1.10 and Symfony 7.4.22 with DoctrineBundle 3.3.4.
Hosted PR checks and independent Symfony approval remain pending.
Before any push, obtain explicit confirmation for the concrete final local state.
