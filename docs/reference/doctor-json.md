# Installation Doctor JSON v1

```bash
php bin/console sillbeam:doctor --format=json
php bin/console sillbeam:doctor --format=text
php bin/console sillbeam:doctor --format=json --env=prod --no-debug > doctor.json
```

`text` is the default. `json` is a machine-readable presentation of exactly the
same `InstallationInspector` results. No additional inspection, runtime boot or
configuration execution is introduced by selecting JSON. Read the process exit
code even when capturing output. A quiet invocation writes nothing and still
runs the same checks with the same exit code.

The normative [JSON Schema](doctor-report.schema.json) uses JSON Schema draft
2020-12. It is separate from [architecture report JSON](architecture-report.schema.json):
both have version 1, but describe different contracts.

## Envelope

Every Doctor JSON result, including a handled command error, contains:

| Field | Meaning |
| --- | --- |
| `schema_version` | Integer `1`; inspect this before consuming fields. |
| `status` | `verified`, `action required` or `not verified`, exactly as in text. |
| `exit_code` | Integer `0`, `1` or `2`, matching the process exit code. |
| `summary` | Counts of Inspector observations for each of the three literal status keys, including zero counts. |
| `checks` | All Inspector observations, without deduplication or filtering. |
| `errors` | Command-level failures; empty after a completed inspection. |

Each observation has `id`, `status`, `description` and `detail`:

- `id` identifies the check category independently of wording or terminal layout.
- `status` preserves the Inspector's state verbatim, including spaces.
- `description` is its human-readable label, for example `Service imports`.
- `detail` is the Inspector's unwrapped diagnostic/remediation message, including
  available file names, conflicting paths and manual next steps. It is normally
  empty for verified observations. No file lists or suggested edits are inferred
  by parsing that message a second time.

The text in `description`, `detail` and error `message` is for people. Do not parse
English sentences as a machine contract. Check IDs, fields, types and status/exit
semantics form that contract. Evidence may contain application paths; choose the
appropriate audience before sharing an exported report.

## Stable check IDs

| ID | Description |
| --- | --- |
| `bundle.registration` | Selected-environment SillbeamCoreBundle registration. |
| `console.adapter` | Statically recognized Application construction in `bin/console`. |
| `configuration.loading` | Supported kernel configuration-loading convention. |
| `configuration.files` | Configuration-file reading/parsing problems, when present. |
| `routing.import` | Exactly one statically verified Sillbeam route import. |
| `discovery.configuration` | Discovery options and Composer namespace/path resolution. |
| `services.imports` | Native service resource overlap and exclusions. |

**IDs identify categories, not unique rows.** A category can appear several times:
for example `services.imports` can contain both a proven overlap (`action required`)
and a separate unknown resource (`not verified`). Configuration-file failures may
also repeat. Keep every row and count every observation; do not index the list by
ID in a way that overwrites earlier rows. Not every category needs to be present,
and clients must not depend on observation order.

## Status and exit priority

| Aggregate status | Exit | Condition |
| --- | --- | --- |
| `verified` | 0 | Every Inspector observation is verified. |
| `action required` | 1 | At least one action item, without unverified observations. |
| `not verified` | 2 | At least one unverified observation, or a command-level failure. |

Exit 2 takes precedence over 1. Counts describe observations, not unique categories.
A command error has no inspection result: `checks` is empty, all summary counts
are zero, `status` is `not verified` and `exit_code` is 2. It never fabricates a
verified check. Normally the actual Inspector emits the baseline check categories;
`configuration.files` appears only when file issues are found.

Static `verified` does not certify effective runtime behavior. Dynamic/ambiguous
configuration stays `not verified`; JSON does not execute it to obtain a more
optimistic answer. Run architecture checks and appropriate Symfony/runtime tests
separately, as described in [installation](../guides/getting-started.md).

## Command errors

`errors` is a list of `{ "code": "...", "message": "..." }` objects:

| Code | Meaning |
| --- | --- |
| `doctor.usage` | Invalid command arguments/options or unsupported output format. |
| `doctor.execution` | Unexpected failure while preparing or executing Doctor. |

When JSON is requested, binding failures such as an unknown option retain the
Doctor envelope and exit 2, without Symfony text or architecture-report fields:

```bash
php bin/console sillbeam:doctor --format=json --unknown-option
```

Example command-error envelope:

```json
{
    "schema_version": 1,
    "status": "not verified",
    "exit_code": 2,
    "summary": {
        "verified": 0,
        "action required": 0,
        "not verified": 0
    },
    "checks": [],
    "errors": [
        {
            "code": "doctor.usage",
            "message": "The \"--unknown-option\" option does not exist."
        }
    ]
}
```

Use exactly `text` or `json`. If repeated, the final `--format` value takes
precedence, consistently for normal output, errors and buffering. Other values,
a missing value and unexpected positional
arguments are usage errors (exit 2). An unsupported format such as `--format=xml`
uses the text error renderer, since no supported JSON format was selected. Error
messages are explanatory text and may vary with Symfony versions.

## Output and CLI entry points

JSON is written directly as one UTF-8 document plus a final newline, without ANSI
formatting, headings, success messages or verbose annotations, even with `--ansi`
or `-vvv`. Malformed UTF-8 in evidence is substituted with U+FFFD. JSON escaping
keeps literal terminal-control bytes from becoming active escape sequences on
STDOUT. Incidental PHP output during the JSON command is discarded, and enabled
PHP warnings become catchable failures, reported by the Inspector or the command
as appropriate. Previous output buffering and
error handling are restored afterwards. `--quiet` suppresses both report and
handled-error output without changing exit codes; do not combine it with a pipeline
that expects a JSON document.

Both ordinary Symfony Console and the existing Core Console adapter run this same
command and serializer. Prefer the command-first examples above. The Core adapter
also recognizes `--format json sillbeam:doctor` before the command name; ordinary
Symfony does not know command-specific value-taking options until it resolves the
command, so use `sillbeam:doctor --format=json` there.

The normal Symfony entry point boots the application **before** Doctor is found.
Failures or application output during that earlier startup are outside the command's
JSON boundary. A command that cannot be created cannot return its own schema.
The Core adapter provides Doctor before container compilation and encodes Doctor
preparation failures too; select it for reliable inspection of a broken installation.
Native Symfony may log its own debug events to **STDERR** at high verbosity;
STDOUT remains the Doctor document once the command runs. Keep streams separate
rather than redirecting `2>&1` into a JSON consumer.

### Help is command metadata

```bash
php bin/console sillbeam:doctor --help
```

Use help on its own: it does not inspect files and returns 0. It is not a Doctor
report. The adapter retains its existing text-help behavior, even with
`--help --format=json`. Ordinary Symfony delegates to its own HelpCommand, whose
`--format=json` describes command metadata, and whose text format is named `txt`,
not Doctor's `text`. Do not validate help output against the Doctor schema.

## Versioning

Schema version 1 fixes the documented fields, types, check-ID catalog, literal
statuses, error codes and exit priority. A change that breaks these guarantees
requires a new schema version and a corresponding reference schema. Diagnostic
wording, paths, ordering and the number of observations are not stable identifiers.
