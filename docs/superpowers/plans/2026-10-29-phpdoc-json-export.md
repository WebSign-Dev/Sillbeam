# English PHPDoc JSON export

Use phpDocumentor 3.10's Twig transformation and existing descriptors. A minimal
standalone template projects plain summaries/descriptions, tags/examples, types and
relationships. No HTML/Markdown rendering and no second AST parser. Source and
byte offsets already supplied by phpDocumentor retain exact declaration text;
PHP tokens only delimit method bodies within those supplied spans.

The existing HTML config remains the source of visibility/version settings. CLI
selects the JSON-only template, source retention and separate cache/output staging.
A development-only PHP writer sorts stable FQSENs, adds versioned package/language
metadata, validates required input, and atomically writes one English JSON file.
Staging data (including raw source) never enters the public artifact.

- [x] Failing tests: real Composer command, deterministic bytes, public/internal
  filtering; phpDocumentor fixtures for traits/interfaces/methods/properties/constants,
  descriptions/code examples, type/return/exception data and Unicode/escaping.
- [x] Implement template, small writer/source-span handling and Composer script.
- [x] Document JSON schema v1 and one command; include tools in static/style checks.
- [x] Execute export twice, HTML build, PHPUnit, PHPStan, style, Deptrac and Composer
  validation; independent review and actual-results report.

No runtime dependencies/autoload registration, CI edits, translation, website,
synchronization, deployment, commits or publication. Preserve existing CI diff.

Verified: 136 real API elements; two identical direct exports and schema validation;
HTML build; 608 tests / 8,486 assertions; PHPStan, Deptrac, style and Composer checks.
PHP8.4 targeted tests pass (3 / 122). The upstream generic @example limitation is
explicitly documented. Existing CI diff preserved. No commits or publication.
