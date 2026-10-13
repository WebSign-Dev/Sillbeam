# Symfony Flex

The Contrib recipe source for v0.1.0 is in [flex-recipe](https://github.com/WebSign-Dev/Sillbeam/blob/main/flex-recipe/README.md). It registers SillbeamBundle and imports `type: sillbeam` once for routing. Doctrine activates only when DoctrineBundle is installed and registered. The recipe does not install Foundation.

Install v0.1.0 with `composer require websign-dev/sillbeam:^0.1.0`. Flex recipe availability depends on the configured Symfony Flex endpoint and is independent of package installation. If the recipe is not installed automatically, register the bundle and route import manually as shown in the [README](../../README.md). A local Composer/Flex installation test checks registration, files, symfony.lock, installation without a package configuration file, both kernels and subsequent removal: `composer test:flex-recipe`.

The recipe preserves existing `services.yaml` files. In a Symfony skeleton, keep the active `App\:` import and add `Module`/`Project` or custom discovery areas to its exclusions when Sillbeam should control automatic activation. See the [integration rules](getting-started.md#preserve-symfony-integration). The local Flex test uses this normal import, checks a native service and a disabled module, and boots both kernels in development/production with cold and warm caches.

The recipe does not create a semantically empty `config/packages/sillbeam.yaml`. Create that file yourself with the desired options if needed. Removal preserves native services and manually created Sillbeam configuration; the recipe removes only its route import and bundle registration.

After uninstalling, remove manually created `sillbeam` options from Symfony package imports and, if necessary, switch an application kernel derived from `SillbeamKernel` back to the standard Symfony kernel. These are application settings; the recipe neither deletes nor overwrites them. The consumer test also checks native kernel boot after this explicit cleanup.
