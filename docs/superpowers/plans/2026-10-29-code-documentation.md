# Sillbeam Core 0.3.0 Code Documentation Implementation Plan

**Goal:** Document the existing 0.2.0 behavior and generate a public HTML API reference for 0.3.0.
**Architecture:** English PHPDoc defines supported contracts and marks implementation services internal. phpDocumentor runs only as a development dependency.
**Constraints:** No behavior changes, runtime dependencies, commits, pushes, tags or releases.

- [x] Inventory source and public API; document contracts, failures, side effects and build architecture without redundant type comments.
- [x] Install phpDocumentor 3 shim with narrowly scoped plugin approval; check platform requirements and dependency changes.
- [x] Add versioned XML configuration and Composer build script; ignore HTML/cache output and document contribution rules.
- [x] Generate HTML, verify public pages and absence of internal pages; run PHPUnit, PHPStan, architecture and coding standards, review executable-token equivalence.

Validation uses the real documentation build and existing checks; no runtime implementation or new behavior tests are needed for comment-only source changes.

**Verified:** PHP 8.5.4; phpDocumentor 3.10.0; 550 PHPUnit tests / 6,440 assertions; PHPStan and coding standards clean; Deptrac zero violations; all 68 source files have identical executable tokens to HEAD. HTML: 20 API types, 48 internal types excluded, 1,775 local links/anchors checked. Composer platform and PHAR requirement checks pass; runtime lock entries and existing development versions are unchanged. Only the shim needs plugin approval; its distributor dependency is a library. Namespace navigation avoids a broken package-overview anchor in the upstream default template.
