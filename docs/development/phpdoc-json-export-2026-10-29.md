# English PHPDoc JSON export verification — 2026-10-29

Implemented the manual development command `composer docs:export-json`.
Artifact: `build/docs/api/sillbeam-core.en.json`, ignored together with existing
HTML output. No commits, pushes, merges, tags, releases, translation or website
integration were performed.

## Implementation and boundaries

The installed tool is phpDocumentor **3.10.0** (`phpdocumentor/shim` remains the
existing development dependency). Its Twig transformation receives the existing
API descriptors, without HTML scraping or another AST parser. The JSON-only
template projects original text, types, tags, relationships and examples.

The small writer sorts FQSEN IDs, adds metadata and atomically replaces the one
public file. phpDocumentor's retained source and byte offsets provide exact
signatures; PHP tokenization only locates body delimiters in those known spans.
Whole-file source/intermediate descriptors remain in ignored cache/staging and
are excluded from the artifact. Staging is removed even after failure; an existing
successful JSON/HTML artifact is preserved when generation fails.

The existing `phpdoc.dist.xml`, `docs:build`, runtime `src/`, dependency constraints,
lockfile and runtime autoload mappings are unchanged. Separate JSON cache/source
retention avoids changing the HTML cache policy. Tools are explicitly included in
PHPStan/style checks; intentionally irregular source fixtures are excluded from
those application-code checks, not from the export tests.

An existing `.github/workflows/ci.yml` working-tree diff was present at task start
and has been preserved without editing it. No CI trigger/step/hook was added.
Composer-command tests use temporary project copies; automated test runs do not
write the actual project's website artifact.

## Results actually observed

Host: PHP **8.5.4**, Composer **2.9.5**, phpDocumentor **3.10.0**.

| Command/check | Result |
| --- | --- |
| `composer docs:export-json` | Exit 0; English JSON generated |
| Second `composer docs:export-json`, `cmp` against first copy | Exit 0; byte-identical |
| Python `json.loads` and `Draft202012Validator.check_schema/validate` | Both schema and real output valid |
| `composer docs:build` | Exit 0; HTML reference still generated and JSON retained |
| `vendor/bin/phpunit --filter PhpDocJsonExportTest` | 3 tests, 122 assertions, successful |
| Same targeted tests under PHP 8.4.26, repository mounted read-only | 3 tests, 122 assertions, successful |
| `composer test` | **608 tests, 8,486 assertions, successful** |
| `composer analyse` | PHPStan level 8, no errors, including new development tools |
| `composer architecture` | No Deptrac violations/warnings/errors |
| `composer cs:check` | No formatting changes required |
| `composer validate --strict` | Valid; no lockfile/dependency change required |
| `git diff --check`, local documentation links | Successful |

The actual output contains **136 elements**: **16 classes**, **4 interfaces**,
**53 methods**, **63 properties**. Other kinds are absent because the current
public source does not declare them; fixture tests cover traits, enums, enum
cases, constants and functions. Internal classes/members and private members
are excluded according to the existing HTML boundary.

Artifact: **176,136 bytes**. SHA-256 from both direct runs (also unchanged after
HTML generation):

```text
90a347db6281fd137bcf1c33e991336eb33b33355c1acde1360fe7228ecae005
```

## Test coverage and corrected first attempts

Real phpDocumentor fixtures cover type relationships, protected/promoted
properties, constants, enum cases, methods/functions, argument order and defaults,
returns, exceptions, Markdown paragraphs, fenced PHP, Unicode, quotes/backslashes,
internal/private exclusion and exact native signatures with original alias names,
spacing and multiline defaults containing body-like punctuation. The encoder also
rejects missing descriptor collections. The isolated Composer test checks repeated
bytes, HTML preservation, nonzero failures, previous-JSON retention and cleanup.

The first meaningful feature tests failed because the Composer command/template
were missing. During setup a helper name collided with PHPUnit's final `run()`;
it was renamed. A temporary-config source-root mismatch was corrected by using
phpDocumentor's normal directory override for the fixture. PHPStan initially
reported fixture-only unused code, an undiscovered tool class and nullable XPath
handling; the tools/fixture scan boundary and the actual version-array guard were
corrected. No runtime type error was suppressed.

The `@example` file test initially expected resolved code. Inspection of the actual
3.10 factory proved it produces a Generic tag for this input, retaining reference
text but no loaded code. The export therefore preserves that text and null code,
without inventing file content. Fenced examples are preserved verbatim in the
plain description. This limitation is explicit in the [contract](../reference/phpdoc-json.md).

An independent read-only review found no actionable issue and independently ran
all 3 export tests (122 assertions).

## Contract and limits

See the [field guide](../reference/phpdoc-json.md) and
[Draft 2020-12 schema v1](../reference/phpdoc-api.schema.json). Descriptions use
phpDocumentor's textual/inheritance representation; structured types/defaults are
its normalized view. Original method/function declaration text is separate.
The export does not infer missing prose, resolve generic example references,
translate language, render HTML or integrate a website.

Only the targeted export tests, not a new full compatibility matrix, were rerun
under PHP 8.4. Existing Core runtime/HTML tooling was not modified. The Python
schema validator is a local verification tool and was not added as a dependency.
Generated JSON/cache is not committed. No external publication action is implied
by successful export.
