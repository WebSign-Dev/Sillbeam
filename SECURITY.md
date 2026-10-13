
# Security Policy

## Supported Versions

Sillbeam provides security fixes for the latest published release on a best-effort basis.

| Version | Supported |
|---------|-----------|
| 1.0.0-rc (latest release) | ✅ |
| Older versions | ❌ |

No long-term security support or guaranteed maintenance period is provided.

## Reporting a Vulnerability

If you discover a potential security vulnerability in Sillbeam, please report it privately through one of the following channels:

- **Email:** [security@websign.de](mailto:security@websign.de)
- **GitHub:** [Private Vulnerability Reporting](https://github.com/WebSign-Dev/Sillbeam/security/advisories/new) (when enabled)

**Please do not disclose security vulnerabilities through public GitHub issues or discussions.**

### What to Include

To help investigate your report, please provide:

- The affected Sillbeam version or commit.
- A description of the vulnerability.
- The potential security impact.
- Steps to reproduce the issue.
- A minimal proof of concept, if available.
- Relevant PHP, Symfony, and dependency versions.

Please avoid including real credentials, private keys, personal information, or other sensitive data.

## Handling Security Reports

Security reports are reviewed and addressed on a best-effort basis.

Confirmed vulnerabilities will be evaluated based on their severity, impact, and reproducibility.

Where appropriate, fixes will be included in a subsequent release and documented in the changelog or a GitHub Security Advisory.

No guaranteed response or resolution time is provided.

We appreciate responsible disclosure and encourage reporters to coordinate public disclosure until a potential fix has been evaluated.

## Security Scope

Sillbeam provides modular architecture management and build-time dependency validation for Symfony applications.

Sillbeam does **not** provide:

- Authentication or authorization.
- Tenant or process isolation.
- Application-level security enforcement.
- Protection against arbitrary execution of untrusted application code.

Modules and Projects share the underlying Symfony application runtime, service container, and infrastructure.

Applications remain responsible for their own security configuration, access controls, data protection, and production secrets.

## General Support

For non-security-related questions, bug reports, and feature requests, please use [GitHub Issues](https://github.com/WebSign-Dev/Sillbeam/issues).

For security-related concerns, always use the private reporting channels listed above.

---

**Maintained by [WebSign](https://websign.de/).**
