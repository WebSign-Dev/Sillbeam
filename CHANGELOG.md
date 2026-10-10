# Changelog

This changelog records public Sillbeam releases. No public release has been made yet.

## Unreleased — proposed 0.1.0

### Initial package scope

- Attribute-based Module and optional Project discovery with configurable PSR-4 roots and exclusions.
- Physical ownership, declared dependency validation, activation graphs and static PHP class-reference checks during container compilation.
- Native Symfony service autowiring/autoconfiguration and attribute routing for active architecture areas.
- Optional DoctrineBundle attribute mappings alongside native Symfony mappings.
- Module and Project registries, request Project context and architecture/runtime CLI diagnostics.
- Standard Symfony kernel support and optional `SillbeamKernel`.
- Manual installation and a prepared, separately publishable Symfony Flex recipe.
- Independent consumer checks for six application layouts, both kernels and operation with/without Doctrine; PHP 8.4/8.5 and Symfony 7.4/8.0/8.1 compatibility evidence.

The first public package contains the modular core only. Former private Nexum/Foundation APIs are not shipped. Existing development consumers must follow the [migration guide](docs/migration/modular-monolith.md).

See the [draft 0.1.0 release notes](docs/releases/0.1.0.md) for installation, limitations and verification. The release date and tag remain unset until publication is approved.
