# Release consistency plan

Scope: current v0.3.0 metadata and documentation only, on dev-v0.3.x. Preserve the
pending distribution cleanup and existing source. No CI file edits, commits, pushes,
merges, tags or releases. Start HEAD is `88add02`, already present before this task.

- [x] Compare commands/metadata with actual implementation and Composer version parser.
- [x] Correct branch aliases for dev-v0.3.x/dev/main; update only needed lock metadata.
- [x] Align current README/changelog/release/migration/contributor docs with native SVG,
  compact inspection, manual English PHPDoc JSON and dev-to-main authorization flow.
- [x] Add a dated current-status correction to the original 0.3 report; preserve all
  original test results, findings and raw evidence. Freeze historical 0.2 files.
- [x] Verify actual Composer branch resolution, docs CLI examples, Markdown/archive
  links, versions and immutable CI hash; run relevant real tests/builds.
- [x] Document all changes, actual verification and unresolved limits separately.
