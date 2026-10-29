# Sillbeam Core v0.3.0 handbook

- [Getting started](guides/getting-started.md) and [Flex](guides/flex-recipe.md)
- [Architecture](architecture/overview.md), [Scope](architecture/foundation.md)
- [Modules](guides/modules.md), [Projects](guides/projects.md)
- [Architecture analysis](reference/architecture-analysis.md), [targeted module inspection](reference/module-inspection.md), [Mermaid graph export](reference/mermaid-graph.md)
- [Doctor JSON v1](reference/doctor-json.md) and [schema](reference/doctor-report.schema.json)
- [English PHPDoc JSON export](reference/phpdoc-json.md)
- [Configuration](reference/configuration.md), [public API](reference/public-api.md), [CLI](reference/cli.md)
- [Upgrade from 0.2.0](migration/0.3.0.md), [earlier migration](migration/modular-monolith.md)
- [v0.3.0 release notes](releases/0.3.0.md), [release acceptance](development/release-0.3.0-acceptance/README.md)
- [Testing](development/testing.md), [Test apps](development/test-apps.md), [Troubleshooting](operations/troubleshooting.md)
- [v0.2.0 RC report](development/core-rc-2026-10-23.md), [benchmark](development/core-benchmark-2026-10-23.md)
- [Creation/reporting/dependency evidence audit and benchmark comparison](development/core-extension-audit-2026-10-24.md)
- [Final class-analysis consistency audit](development/class-analysis-audit-2026-10-24.md)
- [Independent installation acceptance, 2026-10-25](development/installation-acceptance-2026-10-25/INSTALLATIONS-ABNAHME.md)
- [Doctor F01/F02 follow-up and final compatibility checks](development/doctor-f01-f02-verification-2026-10-25/README.md)
- [Core components](modules/README.md)

The reference pages describe the 0.3.0 candidate. [Changelog](../CHANGELOG.md),
[release notes](releases/0.3.0.md) and [upgrade guide](migration/0.3.0.md) describe
its changes and compatibility boundaries. Historical 0.2.0 reports above retain
their original results; the current [acceptance report](development/release-0.3.0-acceptance/README.md)
records what was executed for this candidate. Final release approval is pending.

Stable `:^0.3.0` installation requires the approved release to be published;
see [release status](../README.md#release-status). The compatible Flex recipe
remains version `0.2`, independent of package release numbering. Manual installation
is supported; local tests do not prove public recipe availability.
