# Symfony Flex

The Contrib recipe source for v0.2.0 is in [flex-recipe](../../flex-recipe/README.md). It registers SillbeamCoreBundle and imports `type: sillbeam` once for routing. Doctrine activates only when DoctrineBundle is installed and registered. The recipe does not install Foundation.

After publication, install v0.2.0 with `composer require websign-dev/sillbeam-core:^0.2.0`. Flex recipe availability depends on the configured Symfony Flex endpoint and is independent of package installation. If the recipe is not installed automatically, register the bundle and route import manually as shown in the [README](../../README.md). A local Composer/Flex installation test checks registration, files, symfony.lock, installation without a package configuration file, both kernels and subsequent removal: `composer test:flex-recipe`.

The recipe preserves existing `services.yaml` files. In a Symfony skeleton, keep the active `App\:` import and add `Module`/`Project` or custom discovery areas to its exclusions when Sillbeam should control automatic activation. See the [integration rules](getting-started.md#preserve-symfony-integration). The local Flex test uses this normal import, checks a native service and a disabled module, and boots both kernels in development/production with cold and warm caches.

The recipe does not create a semantically empty `config/packages/sillbeam.yaml`. Create that file yourself with the desired options if needed. Removal preserves native services and manually created Sillbeam configuration; the recipe removes only its route import and bundle registration.

After uninstalling, remove manually created `sillbeam` options from Symfony package imports, restore a manually selected Core Console adapter to Symfony, and, if necessary, switch an application kernel derived from `SillbeamKernel` back to the standard Symfony kernel. These are application settings; the recipe neither deletes nor overwrites them. The consumer test also checks native kernel boot after this explicit cleanup.

After installation, first run `php bin/console sillbeam:doctor`. Review action
items and checks marked `not verified`. If needed, manually change the Application
import in the existing `bin/console` to `use Sillbeam\Core\Console\Application;`,
keeping the bootstrap and Kernel. This enables checks without container compilation.
Review broad service imports as described above, then run
`php bin/console sillbeam:make:module Billing` and
`php bin/console sillbeam:architecture`.

Doctor is static and read-only in its implementation. Conventional Runtime
callbacks and standard Symfony 7.4/8.1 kernels are supported; unknown route overlays
no longer hide independently proven service-import conflicts. Dynamic or ambiguous
configuration still needs manual review; see the
[F01/F02 follow-up](../development/doctor-f01-f02-verification-2026-10-25/README.md).
The regular Symfony entry point boots first and can write its ordinary cache;
select the existing Core Console adapter for inspection without that boot.

These steps are printed by Flex from the recipe's `post-install.txt`. The source
manifest contains only `bundles` and `copy-from-recipe`; the endpoint generates
its post-install output metadata. Neither the source text file nor an empty
package configuration is copied into your application.

For public installation, the package must exist on Packagist with a matching
released version, the separate Recipes Contrib review must be completed, and
the accepted recipe must be available through the configured endpoint. Publishing
the v0.2.0 tag alone does not activate it. Enable Contrib recipes with
`composer config extra.symfony.allow-contrib true` and allow the Flex plugin.
See the [submission instructions](../../flex-recipe/README.md#submit-to-symfonyrecipes-contrib)
and [verification record](../development/flex-recipe-verification.md).

Before uninstalling, preserve application-specific edits to the recipe-managed
`config/routes/sillbeam.yaml`: Flex tracks that filename even if it existed
before installation. Other manually created configuration, including package
options and native service imports, is retained.
