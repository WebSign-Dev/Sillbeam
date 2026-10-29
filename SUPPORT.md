# Support

Use [GitHub issues](https://github.com/WebSign-Dev/sillbeam-core/issues) for reproducible bugs and documentation questions. Check the [guides](docs/README.md) and [troubleshooting](docs/operations/troubleshooting.md) first.

Include the Sillbeam version/commit, PHP/Symfony versions, relevant configuration, application layout, expected/actual behavior and a minimal reproduction. Include sanitized `sillbeam:architecture` text/JSON output; the [Console Application adapter](docs/reference/cli.md) also supports reports when container boot fails. Remove credentials, personal data and private paths from shared logs.

Sillbeam is maintained on a best-effort basis; there is no guaranteed response time or commercial support commitment. The candidate under acceptance is Core v0.3.0. Its package is registered on Packagist; install with `composer require websign-dev/sillbeam-core:^0.3.0`. This command requires the stable version to be listed on Packagist; see [release status](README.md#release-status). The changelog records version history. Before 1.0, consult release notes for compatibility changes.

Do not report vulnerabilities in public issues. Follow [SECURITY.md](SECURITY.md).
