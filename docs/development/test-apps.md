# Test apps

Fixtures use the root autoloader and temporary kernel caches.

`composer test:consumer` uses the same scenario sources as templates for fresh, independent Composer applications. Each scenario is tested without/with Doctrine and with both kernels. Their vendor autoloaders use no root development autoloads. Versions and results are in the [package compatibility evidence](package-compatibility-2026-10-10.md).

| App | Coverage |
|---|---|
| apps/symfony-only | ordinary Symfony app with Sillbeam and no descriptors |
| apps/modules-only | shared module graph, disabled module |
| apps/project-only | direct Project services/routes/entities |
| apps/single-project | single Project with a shared module and no host requirement |
| apps/multi-project | shared module assignments and separate host routes |
| apps/hybrid | native Symfony services/routes alongside Project/Module areas |

`tests/Fixtures/Application` checks shared native Doctrine persistence and module architecture. Dynamic temporary applications under `tests/Support` check flexible roots, exclusions, diagnostics and a kernel without Doctrine.

`apps/test-app` is the separate Harbor consumer project. The Symfony app is in `app/`, local runtime configuration in `.fabryq/`. Start with `fabryq harbor build` and `fabryq harbor up` in `apps/test-app`. Root tests do not start this external runtime.

The Harbor configuration and local path lock are for repository development only and are not shipped as part of the Composer library. The example database credentials are public local test values, not production credentials. Never use them outside this isolated test environment. Set your own `APP_SECRET` in the ignored `app/.env.dev.local`, for example with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`; save only the output locally. Development values committed before release preparation remain in Git history; replace them elsewhere if they were ever reused.
