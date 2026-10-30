# Findings recorded before any correction

## B1 — Flex smoke test expects retired module-inspection output

First observed in the unmodified `0b70c3143acf25cae5082f3ec6007b6d2a31da79` snapshot on PHP 8.4.26 / Symfony 8.1, `composer test:flex-recipe`, exit **255**.

Evidence: [original log](logs/php84-sf81-flex-default.log), [command record](results/php84-sf81-flex-default.json).

`tools/test-flex-recipe.php:470` still requires `ID: <module>` and `State: <state>`. The current implemented and documented compact command returns `Module: hello — Active`, scope, architecture status and merged direct relationships. The successful inspection is consequently rejected by the obsolete smoke-test assertion. The script then reports `Consumer boot without package configuration failed` at line 484. The raw log includes the actual successful inspection output.

This is a required-check/CI blocker, not evidence that the application failed to inspect its module. Nevertheless the remaining steps in that Flex scenario are not reached, so their success cannot be inferred. All intended variants are being executed separately to expose their outcomes. The required follow-up is a narrowly scoped update of the Flex test's expectations to the current contract, followed by a complete rerun on the corrected commit. No correction was made during this acceptance task.

## E1 — Test image initially has no GnuPG

The pre-existing disposable PHP Docker images lacked `gpg` (initial probe exit **127**). GnuPG was provisioned only in disposable QA containers using their system package manager; subsequent version probes and dependency resolution succeeded. Product requirements, source, Composer dependencies and CI configuration were not changed. The configured GitHub Actions environment already checks GnuPG explicitly. Original failed probes and provisioning logs are retained.
