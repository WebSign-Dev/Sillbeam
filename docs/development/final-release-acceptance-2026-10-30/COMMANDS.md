# Executed command ledger

All entries link to original logs and record actual exit codes. Command arrays are authoritative in `results/*.json`; this table uses shell quoting for readability. Environment overrides for matrix jobs are part of the `docker run`/`docker exec` commands. Exit255 is the uncorrected Flex blocker; initial exit127 is the missing QA-image GnuPG prerequisite, subsequently provisioned. Expected failing CLI cases are separately recorded with expected/actual exits in `agent-cli/results.json`.

| Label | Exit | Seconds | Command |
|---|---:|---:|---|
| [host-validate](logs/host-validate.log) | 0 | 0.316 | `composer validate --strict` |
| [host-platform](logs/host-platform.log) | 0 | 0.215 | `composer check-platform-reqs` |
| [host-phpunit](logs/host-phpunit.log) | 0 | 46.977 | `composer test` |
| [api-runtime-json-regressions](logs/api-runtime-json-regressions.log) | 0 | 10.602 | `vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Integration/ValidationLifecycleTest.php tests/Integration/ArchitectureReportingTest.php tests/Integration/DoctorJsonTest.php` |
| [php84-sf74-container](logs/php84-sf74-container.log) | 0 | 0.367 | `docker run -d --name sillbeam-final-php84-sf74 -v /tmp/sillbeam-final-matrix/php84-sf74:/workspace -v /home/oeffe/.cache/composer:/composer-cache -w /workspace -e COMPOSER_ROOT_VERSION=dev-dev-v0.3.x -e 'SILLBEAM_SYMFONY_VERSION=7.4.*' -e COMPOSER_PROCESS_TIMEOUT=900 -e COMPOSER_CACHE_DIR=/composer-cache -e PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/workspace/.acceptance-ini sillbeam-frame-verify:php84 sleep infinity` |
| [php84-sf81-container](logs/php84-sf81-container.log) | 0 | 0.366 | `docker run -d --name sillbeam-final-php84-sf81 -v /tmp/sillbeam-final-matrix/php84-sf81:/workspace -v /home/oeffe/.cache/composer:/composer-cache -w /workspace -e COMPOSER_ROOT_VERSION=dev-dev-v0.3.x -e 'SILLBEAM_SYMFONY_VERSION=8.1.*' -e COMPOSER_PROCESS_TIMEOUT=900 -e COMPOSER_CACHE_DIR=/composer-cache -e PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/workspace/.acceptance-ini sillbeam-frame-verify:php84 sleep infinity` |
| [php84-sf74-initial-gpg](logs/php84-sf74-initial-gpg.log) | 127 | 0.168 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 gpg --version` |
| [php84-sf81-initial-gpg](logs/php84-sf81-initial-gpg.log) | 127 | 0.166 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 gpg --version` |
| [php84-sf81-qa-install-gpg](logs/php84-sf81-qa-install-gpg.log) | 0 | 8.141 | `docker exec --user 0:0 sillbeam-final-php84-sf81 sh -c 'apt-get update -qq && apt-get install -y -qq --no-install-recommends gnupg'` |
| [php84-sf74-qa-install-gpg](logs/php84-sf74-qa-install-gpg.log) | 0 | 8.154 | `docker exec --user 0:0 sillbeam-final-php84-sf74 sh -c 'apt-get update -qq && apt-get install -y -qq --no-install-recommends gnupg'` |
| [cli-targeted-tests](logs/cli-targeted-tests.log) | 0 | 12.274 | `vendor/bin/phpunit tests/Integration/GraphCommandTest.php tests/Integration/GraphSvgTest.php tests/Integration/InspectModuleTest.php tests/Integration/ProductionCliTest.php` |
| [php84-sf81-php](logs/php84-sf81-php.log) | 0 | 0.167 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 php -v` |
| [php84-sf74-php](logs/php84-sf74-php.log) | 0 | 0.166 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 php -v` |
| [php84-sf81-gpg](logs/php84-sf81-gpg.log) | 0 | 0.117 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 gpg --version` |
| [php84-sf74-gpg](logs/php84-sf74-gpg.log) | 0 | 0.117 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 gpg --version` |
| [php84-sf81-composer](logs/php84-sf81-composer.log) | 0 | 0.267 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer --version` |
| [php84-sf74-composer](logs/php84-sf74-composer.log) | 0 | 0.216 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer --version` |
| [php84-sf74-prepare](logs/php84-sf74-prepare.log) | 0 | 0.165 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 php tools/prepare-compatibility.php` |
| [php84-sf81-prepare](logs/php84-sf81-prepare.log) | 0 | 0.167 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 php tools/prepare-compatibility.php` |
| [php84-sf74-resolve](logs/php84-sf74-resolve.log) | 0 | 15.541 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer update --with-all-dependencies --no-scripts --no-interaction --prefer-dist` |
| [php84-sf81-resolve](logs/php84-sf81-resolve.log) | 0 | 10.877 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer update --with-all-dependencies --no-scripts --no-interaction --prefer-dist` |
| [host-analyse](logs/host-analyse.log) | 0 | 34.117 | `composer analyse` |
| [api-production-extended-probe](logs/api-production-extended-probe.log) | 0 | 0.97 | `vendor/bin/phpunit --configuration=phpunit.xml.dist --filter testProductionHttpNeverLoadsTheSourceAnalyzerWithColdOrWarmCache /tmp/AcceptanceHttpProbeTest.php` |
| [php84-sf81-validate](logs/php84-sf81-validate.log) | 0 | 0.368 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer validate --strict` |
| [php84-sf81-platform](logs/php84-sf81-platform.log) | 0 | 0.368 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer check-platform-reqs` |
| [php84-sf81-audit](logs/php84-sf81-audit.log) | 0 | 1.032 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer audit --locked` |
| [php84-sf81-audit-app](logs/php84-sf81-audit-app.log) | 0 | 0.867 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer audit --locked --working-dir=apps/test-app/app` |
| [php84-sf81-phpunit](logs/php84-sf81-phpunit.log) | 0 | 55.414 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer test` |
| [php84-sf74-validate](logs/php84-sf74-validate.log) | 0 | 0.368 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer validate --strict` |
| [php84-sf74-platform](logs/php84-sf74-platform.log) | 0 | 0.418 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer check-platform-reqs` |
| [php84-sf74-audit](logs/php84-sf74-audit.log) | 0 | 0.666 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer audit --locked` |
| [php84-sf74-audit-app](logs/php84-sf74-audit-app.log) | 0 | 0.622 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer audit --locked --working-dir=apps/test-app/app` |
| [php84-sf74-phpunit](logs/php84-sf74-phpunit.log) | 0 | 57.393 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer test` |
| [host-architecture](logs/host-architecture.log) | 0 | 0.617 | `composer architecture` |
| [host-cs](logs/host-cs.log) | 0 | 0.571 | `composer cs:check` |
| [host-audit](logs/host-audit.log) | 0 | 0.416 | `composer audit --locked` |
| [host-audit-app](logs/host-audit-app.log) | 0 | 0.366 | `composer audit --locked --working-dir=apps/test-app/app` |
| [host-docs-json-first](logs/host-docs-json-first.log) | 0 | 1.872 | `composer docs:export-json` |
| [host-docs-json-second](logs/host-docs-json-second.log) | 0 | 1.98 | `composer docs:export-json` |
| [host-docs-html](logs/host-docs-html.log) | 0 | 2.425 | `composer docs:build` |
| [host-release-package](logs/host-release-package.log) | 0 | 7.697 | `composer test:release-package` |
| [host-archive](logs/host-archive.log) | 0 | 1.975 | `composer archive --format=zip --dir=/home/oeffe/PhpstormProjects/websign/sillbeam-core/docs/development/final-release-acceptance-2026-10-30/artifacts --file=sillbeam-core-0.3.0` |
| [api-rule-unit-regressions](logs/api-rule-unit-regressions.log) | 0 | 0.468 | `vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Unit/ArchitectureTest.php tests/Unit/ArchitectureFindingsTest.php tests/Unit/ArchitectureReportTest.php tests/Unit/ClassAnalysisConsistencyTest.php tests/Unit/ApplicationCouplingTest.php tests/Unit/ClassReferenceTest.php` |
| [api-doctor-report-unit](logs/api-doctor-report-unit.log) | 0 | 0.269 | `vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Unit/DoctorReportTest.php` |
| [php84-sf81-analyse](logs/php84-sf81-analyse.log) | 0 | 16.493 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer analyse` |
| [performance-current-phases](logs/performance-current-phases.log) | 0 | 13.363 | `php tools/benchmark-architecture.php --phases` |
| [php84-sf74-analyse](logs/php84-sf74-analyse.log) | 0 | 16.577 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer analyse` |
| [performance-current-symfony-build](logs/performance-current-symfony-build.log) | 0 | 1.775 | `php tools/benchmark-architecture.php --sample /tmp/sillbeam-source-91aab83b9655 SillbeamFixture2872652e08c4 prod build 0 100` |
| [performance-current-sillbeam-build](logs/performance-current-sillbeam-build.log) | 0 | 4.937 | `php tools/benchmark-architecture.php --sample /tmp/sillbeam-source-e6d8fceb3623 SillbeamFixture1e131da5d1cc prod build 0 100` |
| [php84-sf81-architecture](logs/php84-sf81-architecture.log) | 0 | 1.619 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer architecture` |
| [php84-sf81-cs](logs/php84-sf81-cs.log) | 0 | 17.339 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer cs:check` |
| [performance-current-symfony-http-0](logs/performance-current-symfony-http-0.log) | 0 | 1.271 | `php tools/benchmark-architecture.php --sample /tmp/sillbeam-source-91aab83b9655 SillbeamFixture2872652e08c4 prod http 200 100` |
| [performance-current-sillbeam-http-0](logs/performance-current-sillbeam-http-0.log) | 0 | 1.274 | `php tools/benchmark-architecture.php --sample /tmp/sillbeam-source-e6d8fceb3623 SillbeamFixture1e131da5d1cc prod http 200 100` |
| [php84-sf74-architecture](logs/php84-sf74-architecture.log) | 0 | 1.627 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer architecture` |
| [performance-current-sillbeam-http-1](logs/performance-current-sillbeam-http-1.log) | 0 | 1.326 | `php tools/benchmark-architecture.php --sample /tmp/sillbeam-source-e6d8fceb3623 SillbeamFixture1e131da5d1cc prod http 200 100` |
| [php84-sf74-cs](logs/php84-sf74-cs.log) | 0 | 17.407 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer cs:check` |
| [performance-current-symfony-http-1](logs/performance-current-symfony-http-1.log) | 0 | 1.426 | `php tools/benchmark-architecture.php --sample /tmp/sillbeam-source-91aab83b9655 SillbeamFixture2872652e08c4 prod http 200 100` |
| [performance-current-symfony-http-2](logs/performance-current-symfony-http-2.log) | 0 | 1.223 | `php tools/benchmark-architecture.php --sample /tmp/sillbeam-source-91aab83b9655 SillbeamFixture2872652e08c4 prod http 200 100` |
| [performance-current-sillbeam-http-2](logs/performance-current-sillbeam-http-2.log) | 0 | 1.22 | `php tools/benchmark-architecture.php --sample /tmp/sillbeam-source-e6d8fceb3623 SillbeamFixture1e131da5d1cc prod http 200 100` |
| [php84-sf81-docs-html](logs/php84-sf81-docs-html.log) | 0 | 2.577 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer docs:build` |
| [php84-sf81-docs-json](logs/php84-sf81-docs-json.log) | 0 | 2.28 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer docs:export-json` |
| [php84-sf81-flex-default](logs/php84-sf81-flex-default.log) | 255 | 7.155 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer test:flex-recipe` |
| [php84-sf74-docs-html](logs/php84-sf74-docs-html.log) | 0 | 2.677 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer docs:build` |
| [host-docs-json-schema](logs/host-docs-json-schema.log) | 0 | 0.369 | `python3 /tmp/sillbeam-final-json-check.py` |
| [php84-sf74-docs-json](logs/php84-sf74-docs-json.log) | 0 | 2.22 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer docs:export-json` |
| [php84-sf74-flex-default](logs/php84-sf74-flex-default.log) | 255 | 24.368 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer test:flex-recipe` |
| [php84-sf81-flex-fresh](logs/php84-sf81-flex-fresh.log) | 255 | 7.536 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_FRESH=1 sillbeam-final-php84-sf81 composer test:flex-recipe` |
| [php84-sf81-flex-existing-route](logs/php84-sf81-flex-existing-route.log) | 255 | 6.738 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_EXISTING_ROUTE=1 sillbeam-final-php84-sf81 composer test:flex-recipe` |
| [php84-sf81-flex-doctrine](logs/php84-sf81-flex-doctrine.log) | 255 | 15.669 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_DOCTRINE=1 sillbeam-final-php84-sf81 composer test:flex-recipe` |
| [php84-sf74-flex-fresh](logs/php84-sf74-flex-fresh.log) | 255 | 10.877 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_FRESH=1 sillbeam-final-php84-sf74 composer test:flex-recipe` |
| [php84-sf81-flex-fresh-doctrine](logs/php84-sf81-flex-fresh-doctrine.log) | 255 | 6.611 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_FRESH=1 -e SILLBEAM_TEST_DOCTRINE=1 sillbeam-final-php84-sf81 composer test:flex-recipe` |
| [php84-sf74-flex-existing-route](logs/php84-sf74-flex-existing-route.log) | 255 | 12.959 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_EXISTING_ROUTE=1 sillbeam-final-php84-sf74 composer test:flex-recipe` |
| [php84-sf81-consumer](logs/php84-sf81-consumer.log) | 0 | 60.596 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer test:consumer` |
| [php84-sf74-flex-doctrine](logs/php84-sf74-flex-doctrine.log) | 255 | 8.542 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_DOCTRINE=1 sillbeam-final-php84-sf74 composer test:flex-recipe` |
| [php84-sf74-flex-fresh-doctrine](logs/php84-sf74-flex-fresh-doctrine.log) | 255 | 6.583 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_FRESH=1 -e SILLBEAM_TEST_DOCTRINE=1 sillbeam-final-php84-sf74 composer test:flex-recipe` |
| [php84-sf74-consumer](logs/php84-sf74-consumer.log) | 0 | 58.196 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer test:consumer` |
| [environment-host](logs/environment-host.log) | 0 | 0.066 | `php -v` |
| [environment-composer](logs/environment-composer.log) | 0 | 0.219 | `composer --version` |
| [environment-images](logs/environment-images.log) | 0 | 0.219 | `docker image inspect sillbeam-frame-verify:php84 sillbeam-frame-verify:php85` |
| [host-app-validate](logs/host-app-validate.log) | 0 | 0.421 | `composer validate --strict --working-dir=apps/test-app/app` |
| [php84-sf81-release-package](logs/php84-sf81-release-package.log) | 0 | 8.954 | `docker exec --user 1000:1000 sillbeam-final-php84-sf81 composer test:release-package` |
| [php84-sf81-cleanup](logs/php84-sf81-cleanup.log) | 0 | 0.921 | `docker rm -f sillbeam-final-php84-sf81` |
| [php85-sf74-container](logs/php85-sf74-container.log) | 0 | 0.365 | `docker run -d --name sillbeam-final-php85-sf74 -v /tmp/sillbeam-final-matrix/php85-sf74:/workspace -v /home/oeffe/.cache/composer:/composer-cache -w /workspace -e COMPOSER_ROOT_VERSION=dev-dev-v0.3.x -e 'SILLBEAM_SYMFONY_VERSION=7.4.*' -e COMPOSER_PROCESS_TIMEOUT=900 -e COMPOSER_CACHE_DIR=/composer-cache -e PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/workspace/.acceptance-ini sillbeam-frame-verify:php85 sleep infinity` |
| [php85-sf74-initial-gpg](logs/php85-sf74-initial-gpg.log) | 127 | 0.115 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 gpg --version` |
| [php85-sf74-qa-install-gpg](logs/php85-sf74-qa-install-gpg.log) | 0 | 8.949 | `docker exec --user 0:0 sillbeam-final-php85-sf74 sh -c 'apt-get update -qq && apt-get install -y -qq --no-install-recommends gnupg'` |
| [php85-sf74-php](logs/php85-sf74-php.log) | 0 | 0.166 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 php -v` |
| [php85-sf74-gpg](logs/php85-sf74-gpg.log) | 0 | 0.115 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 gpg --version` |
| [php85-sf74-composer](logs/php85-sf74-composer.log) | 0 | 0.266 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer --version` |
| [php85-sf74-prepare](logs/php85-sf74-prepare.log) | 0 | 0.167 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 php tools/prepare-compatibility.php` |
| [php85-sf74-resolve](logs/php85-sf74-resolve.log) | 0 | 5.585 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer update --with-all-dependencies --no-scripts --no-interaction --prefer-dist` |
| [php84-sf74-release-package](logs/php84-sf74-release-package.log) | 0 | 14.358 | `docker exec --user 1000:1000 sillbeam-final-php84-sf74 composer test:release-package` |
| [php85-sf74-validate](logs/php85-sf74-validate.log) | 0 | 0.422 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer validate --strict` |
| [php85-sf74-platform](logs/php85-sf74-platform.log) | 0 | 0.316 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer check-platform-reqs` |
| [php85-sf74-audit](logs/php85-sf74-audit.log) | 0 | 0.917 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer audit --locked` |
| [php85-sf74-audit-app](logs/php85-sf74-audit-app.log) | 0 | 0.517 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer audit --locked --working-dir=apps/test-app/app` |
| [php85-sf74-phpunit](logs/php85-sf74-phpunit.log) | 0 | 55.624 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer test` |
| [php84-sf74-cleanup](logs/php84-sf74-cleanup.log) | 0 | 0.519 | `docker rm -f sillbeam-final-php84-sf74` |
| [php85-sf81-container](logs/php85-sf81-container.log) | 0 | 0.266 | `docker run -d --name sillbeam-final-php85-sf81 -v /tmp/sillbeam-final-matrix/php85-sf81:/workspace -v /home/oeffe/.cache/composer:/composer-cache -w /workspace -e COMPOSER_ROOT_VERSION=dev-dev-v0.3.x -e 'SILLBEAM_SYMFONY_VERSION=8.1.*' -e COMPOSER_PROCESS_TIMEOUT=900 -e COMPOSER_CACHE_DIR=/composer-cache -e PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/workspace/.acceptance-ini sillbeam-frame-verify:php85 sleep infinity` |
| [php85-sf81-initial-gpg](logs/php85-sf81-initial-gpg.log) | 127 | 0.118 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 gpg --version` |
| [php85-sf81-qa-install-gpg](logs/php85-sf81-qa-install-gpg.log) | 0 | 8.966 | `docker exec --user 0:0 sillbeam-final-php85-sf81 sh -c 'apt-get update -qq && apt-get install -y -qq --no-install-recommends gnupg'` |
| [php85-sf81-php](logs/php85-sf81-php.log) | 0 | 0.168 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 php -v` |
| [php85-sf81-gpg](logs/php85-sf81-gpg.log) | 0 | 0.115 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 gpg --version` |
| [php85-sf81-composer](logs/php85-sf81-composer.log) | 0 | 0.215 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer --version` |
| [php85-sf81-prepare](logs/php85-sf81-prepare.log) | 0 | 0.166 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 php tools/prepare-compatibility.php` |
| [php85-sf81-resolve](logs/php85-sf81-resolve.log) | 0 | 5.432 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer update --with-all-dependencies --no-scripts --no-interaction --prefer-dist` |
| [php85-sf81-validate](logs/php85-sf81-validate.log) | 0 | 0.517 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer validate --strict` |
| [php85-sf81-platform](logs/php85-sf81-platform.log) | 0 | 0.317 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer check-platform-reqs` |
| [php85-sf81-audit](logs/php85-sf81-audit.log) | 0 | 0.566 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer audit --locked` |
| [php85-sf81-audit-app](logs/php85-sf81-audit-app.log) | 0 | 0.466 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer audit --locked --working-dir=apps/test-app/app` |
| [php85-sf81-phpunit](logs/php85-sf81-phpunit.log) | 0 | 52.746 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer test` |
| [performance-phase-10-dev](logs/performance-phase-10-dev.log) | 0 | 0.817 | `php tools/benchmark-architecture.php --phase-worker 10 dev` |
| [performance-phase-10-prod](logs/performance-phase-10-prod.log) | 0 | 0.668 | `php tools/benchmark-architecture.php --phase-worker 10 prod` |
| [performance-phase-50-dev](logs/performance-phase-50-dev.log) | 0 | 2.658 | `php tools/benchmark-architecture.php --phase-worker 50 dev` |
| [performance-phase-50-prod](logs/performance-phase-50-prod.log) | 0 | 1.922 | `php tools/benchmark-architecture.php --phase-worker 50 prod` |
| [performance-phase-100-dev](logs/performance-phase-100-dev.log) | 0 | 4.382 | `php tools/benchmark-architecture.php --phase-worker 100 dev` |
| [performance-phase-100-prod](logs/performance-phase-100-prod.log) | 0 | 3.883 | `php tools/benchmark-architecture.php --phase-worker 100 prod` |
| [php85-sf74-analyse](logs/php85-sf74-analyse.log) | 0 | 15.762 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer analyse` |
| [php85-sf74-architecture](logs/php85-sf74-architecture.log) | 0 | 1.571 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer architecture` |
| [php85-sf74-cs](logs/php85-sf74-cs.log) | 0 | 16.723 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer cs:check` |
| [php85-sf81-analyse](logs/php85-sf81-analyse.log) | 0 | 16.221 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer analyse` |
| [php85-sf74-docs-html](logs/php85-sf74-docs-html.log) | 0 | 2.523 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer docs:build` |
| [php85-sf74-docs-json](logs/php85-sf74-docs-json.log) | 0 | 2.125 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer docs:export-json` |
| [php85-sf74-flex-default](logs/php85-sf74-flex-default.log) | 255 | 7.794 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer test:flex-recipe` |
| [php85-sf81-architecture](logs/php85-sf81-architecture.log) | 0 | 1.57 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer architecture` |
| [php85-sf81-cs](logs/php85-sf81-cs.log) | 0 | 16.573 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer cs:check` |
| [php85-sf74-flex-fresh](logs/php85-sf74-flex-fresh.log) | 255 | 5.23 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_FRESH=1 sillbeam-final-php85-sf74 composer test:flex-recipe` |
| [php85-sf74-flex-existing-route](logs/php85-sf74-flex-existing-route.log) | 255 | 6.587 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_EXISTING_ROUTE=1 sillbeam-final-php85-sf74 composer test:flex-recipe` |
| [php85-sf74-flex-doctrine](logs/php85-sf74-flex-doctrine.log) | 255 | 7.998 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_DOCTRINE=1 sillbeam-final-php85-sf74 composer test:flex-recipe` |
| [php85-sf81-docs-html](logs/php85-sf81-docs-html.log) | 0 | 2.524 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer docs:build` |
| [php85-sf81-docs-json](logs/php85-sf81-docs-json.log) | 0 | 2.171 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer docs:export-json` |
| [php85-sf81-flex-default](logs/php85-sf81-flex-default.log) | 255 | 6.936 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer test:flex-recipe` |
| [php85-sf74-flex-fresh-doctrine](logs/php85-sf74-flex-fresh-doctrine.log) | 255 | 6.44 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_FRESH=1 -e SILLBEAM_TEST_DOCTRINE=1 sillbeam-final-php85-sf74 composer test:flex-recipe` |
| [php85-sf81-flex-fresh](logs/php85-sf81-flex-fresh.log) | 255 | 5.18 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_FRESH=1 sillbeam-final-php85-sf81 composer test:flex-recipe` |
| [php85-sf74-consumer](logs/php85-sf74-consumer.log) | 0 | 54.994 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer test:consumer` |
| [php85-sf81-flex-existing-route](logs/php85-sf81-flex-existing-route.log) | 255 | 7.435 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_EXISTING_ROUTE=1 sillbeam-final-php85-sf81 composer test:flex-recipe` |
| [php85-sf81-flex-doctrine](logs/php85-sf81-flex-doctrine.log) | 255 | 7.792 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_DOCTRINE=1 sillbeam-final-php85-sf81 composer test:flex-recipe` |
| [php85-sf81-flex-fresh-doctrine](logs/php85-sf81-flex-fresh-doctrine.log) | 255 | 6.39 | `docker exec --user 1000:1000 -e SILLBEAM_TEST_FRESH=1 -e SILLBEAM_TEST_DOCTRINE=1 sillbeam-final-php85-sf81 composer test:flex-recipe` |
| [php85-sf81-consumer](logs/php85-sf81-consumer.log) | 0 | 53.948 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer test:consumer` |
| [php85-sf74-release-package](logs/php85-sf74-release-package.log) | 0 | 8.597 | `docker exec --user 1000:1000 sillbeam-final-php85-sf74 composer test:release-package` |
| [php85-sf74-cleanup](logs/php85-sf74-cleanup.log) | 0 | 0.518 | `docker rm -f sillbeam-final-php85-sf74` |
| [php85-sf81-release-package](logs/php85-sf81-release-package.log) | 0 | 8.142 | `docker exec --user 1000:1000 sillbeam-final-php85-sf81 composer test:release-package` |
| [php85-sf81-cleanup](logs/php85-sf81-cleanup.log) | 0 | 0.416 | `docker rm -f sillbeam-final-php85-sf81` |
