# Sillbeam 0.3.0 Installation and Onboarding

**Goal:** Make the existing Composer/Flex → Doctor → first working module path concrete, preserving application ownership.
**Design:** Keep the current recipe manifest and Console adapter. Improve Doctor's evidence-based remediation and recognize only finite, literal single-group brace exclusions, without evaluating PHP/config imports or guessing merge order. Give confirmed duplicate route imports an actionable diagnosis. Unknown/dynamic evidence retains exit 2; confirmed problems exit 1; all-static evidence exit 0.
**Constraints:** No installer command, Doctor JSON, automatic application edits, dependencies, architecture/runtime changes, commits, pushes or releases. Package publication and public recipe availability remain separate from local acceptance.

- [x] Add regression tests for manual repair, default brace excludes, custom roots, missing bundle/route/adapter and duplicate routes; prove failures first.
- [x] Implement bounded exclusion parsing and contextual repair messages; retain conservative fallback and read-only snapshots.
- [x] Reorder Quickstart, installation guide and recipe post-install guidance; preserve existing native files and explain boot-failure recovery.
- [x] Extend the real Composer/Flex consumer test to cover fresh and pre-existing applications, no/with Doctrine, broken configuration → explicit correction → Doctor → first HTTP module; retain cold/warm kernels and uninstall checks.
- [x] Run the matrix and complete suite, PHPStan, coding standards, architecture and documentation build; record exact results and limitations.

**Approach choice:** Existing static inspector and manual Symfony edits fit the request. A mutating installer or runtime config evaluation would violate ownership/read-only requirements. Recipe 0.2 remains the compatible minimum recipe version; the test package can simulate 0.3.0 locally without tagging or publishing.

**Outcome:** See `docs/development/installation-onboarding-2026-10-29.md` for the observed red/green checks, eight successful real Composer/Flex consumers, preserved destination check, full-suite results and limits. Composer version selection uses `7.4.*`, not exact `7.4.0`; security blocking remains enabled. Root Composer metadata/lock and runtime integration files are unchanged.
