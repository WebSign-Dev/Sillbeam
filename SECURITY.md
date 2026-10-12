# Security policy

## Reporting a vulnerability

Use GitHub's [private vulnerability reporting](https://github.com/WebSign-Dev/Sillbeam/security/advisories/new) when enabled for this repository. Include the affected version, impact, prerequisites and a minimal reproduction. Do not post credentials, exploit details or confidential application data in public issues.

Private reporting availability has not been verified during this preparation. It must be enabled and checked before public release. If the GitHub channel is unavailable, use the [published general contact](https://getsillbeam.websign.de/kontakt/) (`sillbeam@websign.de`) only to request a confidential reporting channel; do not send exploit details or secrets before agreeing on that channel. This is a general project contact, not a dedicated security mailbox. No response-time guarantee is offered.

## Scope and supported versions

No version is public yet. The proposed initial line is 0.1.x. Security fixes are intended for the latest released 0.1 version on a best-effort basis; no long-term support window is promised. Development checkouts should follow main and the changelog.

Sillbeam validates architecture at build time. It does not provide authentication, authorization or tenant isolation. Projects share the Symfony runtime and persistence; applications remain responsible for those security boundaries and production secrets.
