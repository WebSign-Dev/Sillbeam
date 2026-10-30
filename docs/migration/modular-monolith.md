# Migrating to the Sillbeam module layer

This evolution is a breaking change. Before updating, inventory usage of the removed areas, implement replacements and test applications with the new package. The package does not automatically change existing databases; schema migrations are the application's responsibility.

## Removed Foundation

The namespaces `Sillbeam\Core\Api`, `Core`, `Doctrine`, `Entity`, `Exception`, `Filesystem`, `Health`, `Http`, `Location`, `Logger`, `Mailer`, `Management`, `Messenger`, `Notification`, `Payment`, `QrCode`, `Reporting`, `Search`, `Security`, `Serializer`, `User` and `Validator` and their configuration have been removed. Older versions remain available in Git history.

- Replace API resource/CRUD bases with Symfony controllers or an explicitly chosen API package.
- Replace entity inheritance, UUID/timestamp/soft-delete mappings and EntityStores with application entities, repositories, listeners/filters. Initially retain columns and existing IDs; review ORM schema diffs before execution.
- Migrate User, AccessToken, MFA and passkey features using an application security/persistence design. Preserve existing credentials/encrypted secrets and their keys. Sillbeam no longer installs authentication.
- Run infrastructure contracts and adapters in the application or a separate package under its own namespace; use direct Symfony/provider configuration.
- Remove Foundation keys from `sillbeam.yaml`: `api`, `user`, `security`, `management`, `filesystem`, `mailer`, `messenger`, `qrcode`, `http`, `serializer`, `validator` and other former integrations. The new configuration tree accepts only discovery, module activation, Projects, integration patterns and Doctrine mapping.

Strata is neither an available replacement implementation nor a required package.

## Architecture and imports

Module/Project attributes remain. PSR-4 filenames are unrestricted; discovery recognizes attributes rather than a filename convention. Class names without the `Module`/`Project` suffix need an explicit `id:` if the old ID is desired.

Default paths remain `src/Module`/`src/Project`. [Configuration](../reference/configuration.md) supports additional roots and internal layouts. Overlapping module areas and private descriptors without a Project are now rejected. Domain subdirectories remain unrestricted.

Direct static module access requires direct `dependencies`; transitive dependencies are insufficient. Error messages identify the source. Unused imports have no effect. Shared→Private access and private access across Project boundaries are forbidden.

Disabled private modules are no longer activated automatically. Active modules with disabled dependencies remain a build error. Doctrine automatically maps only active modules and direct Project entities. When DoctrineBundle is installed, Sillbeam mapping is now enabled by default; set `doctrine: false` for an existing purely native configuration.

`#[Exclude]`, `#[When]`, `#[WhenNot]`, `#[AsAlias]` and automatic interface aliases follow the native Symfony loader. Check `debug:container` after updating. Manual Symfony/Doctrine imports can intentionally bypass automatic exclusions.

## Validation

1. Update Composer dependencies; explicitly install Doctrine in the application if needed.
2. Rebuild the cache and correct statically reported dependencies.
3. Compare `sillbeam:architecture --format=json`, `debug:container`, `debug:router` and, with Doctrine, `doctrine:mapping:info`.
4. Run application, authentication and persistence tests; review database changes separately.

The test application now uses ordinary Symfony controllers and native Doctrine entities; [Test apps](https://github.com/WebSign-Dev/sillbeam-core/blob/3f891afbff43a0ab48af4ec5991f19aa59fe80b0/docs/development/test-apps.md) shows the supported operating modes.
