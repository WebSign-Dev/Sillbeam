# v0.1.0 integration audit implementation plan

**Goal:** Verify the release candidate in ordinary Symfony consumers and fix only reproduced defects.
**Architecture:** Keep native Symfony imports and explicit configuration authoritative. Sillbeam controls only its automatic imports; consumers exclude architecture areas from overlapping native imports.
**Tech stack:** PHP 8.4/8.5, Symfony 7.4/8.0/8.1, PHPUnit, optional DoctrineBundle.
**Spec:** User release audit request, 2026-10-12.

Constraints: preserve the existing logo change; no push, merge, tags, publication, mandatory dependencies or product features.

- [x] Exercise an unrestricted skeleton import and the required architecture exclusions; cover native services, overrides, aliases and environment attributes in development and production.
- [x] Review existing architecture, AST, registry, route and Doctrine tests. Add regression tests before fixing any demonstrated defect; preserve dynamic-analysis limits.
- [x] Measure isolated 10/50/100-module applications: parsing/index reuse, discovery, dependency validation, cold container build and warm boot, peak memory. Optimize only measured defects.
- [x] Exercise independent Flex consumers with a standard import, with/without Doctrine and both kernel choices; run available Symfony versions in disposable copies.
- [x] Run all mandatory commands, inspect the final diff, document integration rules, measurements and unavailable matrix combinations.

Review focus: native imports bypass activation; native interface aliases remain authoritative; inactive nested areas do not leak; route/config/source resources invalidate development caches; production caches need explicit rebuilds.

Execution: directly in this session as requested; changes to runtime code require a failing regression test first.

Completed evidence: [release audit](../../development/release-readiness-2026-10-12.md).
