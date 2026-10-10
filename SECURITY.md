# Security policy

## Reporting a vulnerability

Use GitHub's [private vulnerability reporting](https://github.com/WebSign-Dev/Sillbeam/security/advisories/new) when enabled for this repository. Include the affected version, impact, prerequisites and a minimal reproduction. Do not post credentials, exploit details or confidential application data in public issues.

Private reporting must be enabled before public release. If GitHub reports that the channel is unavailable, do not open a public vulnerability report; contact a repository maintainer privately using their published contact details. No separate security email or response-time guarantee is currently offered.

## Scope and supported versions

No version is public yet. The proposed initial line is 0.1.x. Security fixes are intended for the latest released 0.1 version on a best-effort basis; no long-term support window is promised. Development checkouts should follow main and the changelog.

Sillbeam validates architecture at build time. It does not provide authentication, authorization or tenant isolation. Projects share the Symfony runtime and persistence; applications remain responsible for those security boundaries and production secrets.
