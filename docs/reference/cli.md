# CLI and architecture reporting

```bash
php bin/console sillbeam:architecture
php bin/console sillbeam:architecture --format=json
php bin/console sillbeam:architecture -v
php bin/console sillbeam:report
php bin/console sillbeam:report --output=docs/architecture-report.md
php bin/console sillbeam:architecture --env=prod --no-debug --format=json
php bin/console sillbeam:about
```

## Enable reporting before container compilation

In your application's existing `bin/console`, replace the Console Application import:

```php
use Sillbeam\Core\Console\Application;
// replaces: use Symfony\Bundle\FrameworkBundle\Console\Application;
```

Keep your existing Composer/Symfony Runtime bootstrap and kernel construction. For example, the existing runtime callback remains:

```php
return function (array $context) {
    $kernel = new App\Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new Application($kernel);
};
```

This adapter runs the full `sillbeam:architecture` and `sillbeam:report` commands before kernel boot; it also supports retrieving either exact command through `find()`/`get()`. Other commands, including `list`, delegate to Symfony and retain their normal boot behavior. Use the full command name when reporting a broken application. The package's test-app entry point demonstrates this import.

Without this import, the command remains available through the normal Symfony application when its container compiles. Symfony's normal console cannot discover container services when boot fails; the adapter is needed for that case. No additional executable, configuration file or dependency is required.

## Effective configuration

Reporting prepares an uncompiled builder through the actual kernel's configuration hooks. Symfony's own loaders read its YAML/PHP/XML imports, environment-specific configuration and parameters. Composer PSR-4 mappings determine discovery namespaces through the same resolver used by validation. Bundle `prepend()` configuration is included, and Sillbeam's existing Doctrine/late-prepend consistency requirement is enforced.

The analyzer and configuration processor are exactly those used by container validation. Reporting does not boot bundles, run service autowiring/optimization or require compiled registries. It can therefore report a valid architecture even when unrelated service definitions cannot compile. An unreadable/invalid configuration, missing bundle, unsupported kernel preparation, unexpected execution error or fatal analysis blocker returns code 2. An already broken configuration-loading hook cannot be bypassed by guessing its effective configuration.

The existing bootstrap selects the kernel, environment and debug mode as usual, including any Symfony Runtime/Dotenv setup. Reporting does not scan for a second configuration file, load another `.env` interpretation or assume an `App\Kernel` class. Custom kernels must support Symfony's uncompiled `initializeBundles`/`buildContainer` preparation hooks; this integration is tested on Symfony 7.4 and 8.1.

## Output and exit codes

| Exit | Meaning |
| --- | --- |
| 0 | Architecture is valid; this is not a service-container health check |
| 1 | Analysis completed and found architecture violations |
| 2 | Analysis incomplete/unexecutable, configuration or command usage invalid, or report file could not be written |

Text output starts with status and counts of violations, fatal blockers, modules and Projects. Findings are grouped by affected module or Project, with readable titles, rule IDs, explanations and available file/line locations. Remediation hints are collected under **How to fix** and shown once per group. The compact module catalog shows scope, activation state and declared/observed dependency counts; `-v` also shows module directories. Project hosts and activation counts remain visible. All existing report data remains available in JSON. `--format=json` writes exactly one raw JSON document, with no ANSI escapes, banners, stack traces or progress output from Sillbeam. JSON remains available with `--ansi`, verbose or quiet console settings. Buffered configuration output and PHP notices/deprecations are suppressed during analysis; execution failures become structured fatal findings. The application bootstrap itself should not print output before returning the Console Application.

JSON uses [schema version 1](architecture-report.schema.json):

```json
{
  "schema_version": 1,
  "status": "valid",
  "valid": true,
  "complete": true,
  "findings": [],
  "modules": [],
  "projects": []
}
```

- `status`: `valid`, `violations` or `unavailable`; `complete` means analysis was not blocked, independently of validity.
- `findings`: ordered/deduplicated objects with `rule`, `severity` (`error`/`fatal`), `message` and `context` (always a JSON object). Context includes available module/project/target, file and numeric line. `report.execution` records configuration/CLI execution failures; analyzer rule identifiers are unchanged.
- `modules`: the existing ID/class, scope/owner, enabled/active, inactive reason, paths, declared/observed dependencies, source-reference evidence, activation reasons and controller/entity directories.
- Module objects may additionally contain `application_dependencies` (unique observed application class names) and `application_references` (class, source file/line and `target_file`). These optional fields are emitted only for nonempty coupling evidence. They do not enter `observed_dependencies`, `references`, activation or findings. Application coupling is informational, including for inactive modules and reports with architecture violations. Text and Markdown include an **Application couplings (informational)** section with evidence; text `-v` also shows target files. This is an additive schema-version-1 extension: all previous fields/types/required keys are retained and the updated schema accepts previous reports. Consumers pinning the old schema with `additionalProperties: false` must update their schema to accept the optional fields.
- `projects`: the existing Project fields, configured hosts and compiled module graph.

Invalid analysis retains discovered catalog information and available references. `active` is `null`, `inactive_reason` is `unknown`, Project `modules` is `null`, and activation reasons are empty when no valid compiled graph exists. Enabled state is retained from resolved configuration for complete analysis; identity/discovery blockers may make it `null`. A fatal discovery failure can leave both catalogs empty. These unknown values never claim a partial graph is valid.

Existing valid JSON module/Project fields are retained; version/status/findings fields are additive. Consumers should select a supported `schema_version` and check the exit code and `complete` before treating activation data as authoritative. See [architecture analysis](architecture-analysis.md) for the shared result API and failure semantics.

Symfony's regular `debug:container`, `debug:router` and optional `doctrine:mapping:info` commands continue to use the ordinary compiled-container path.

## Standalone Markdown report

```bash
php bin/console sillbeam:report
php bin/console sillbeam:report --output=docs/architecture-report.md
php bin/console sillbeam:report --env=prod --no-debug
```

The default file is `<project-root>/architecture-report.md`. Relative output paths
are resolved against the kernel's project root, independently of the current
working directory; absolute local paths are also accepted. The parent directory
must already exist. The command does not create directories or other files.

The report includes the installed Sillbeam version from Composer (or `source` when
not installed), the check time in UTC, architecture status, module/Project counts,
violation and blocker counts, and relevant findings with locations and remediation
hints. Findings are grouped as in the console view. Only Sillbeam architecture
rules are checked; the report makes no general code-quality, security or
service-container health assessment. No HTML, diagrams or other export formats
are generated.

The Markdown command uses the same analysis and report data as
`sillbeam:architecture`, including the same effective configuration. It writes the
file even when violations are found (exit 1), or the analysis is unavailable (exit
2). For incomplete analysis, counts represent only available catalog entries and
findings, not an assurance that all sources were checked. Fatal blockers are
counted separately from architecture violations. The JSON schema is unchanged.

An existing report is replaced only after the complete new content has been
written to a temporary file in the same directory. Write/replacement failures
return exit 2 with an actionable console error and preserve the previous file;
temporary files are cleaned up. On a valid analysis with successful writing, the
exit code is 0. Invalid command usage returns exit 2 without writing a report.

Use the Console adapter described above to export even when architecture
violations or unrelated service errors prevent container compilation. With the
regular Symfony Console Application the command is registered as usual, but
requires a compilable container. Both reporting commands continue to support
normal Symfony environment, verbosity, help and quiet options.

## Verification

See [testing](../development/testing.md) for the test suite and quality commands.
Reporting tests cover text grouping and summaries, the unchanged JSON schema,
Markdown output paths and atomic replacement, and reporting without a compilable
container. Run compatibility checks on each supported PHP/Symfony combination;
a local run on one combination does not verify the whole compatibility matrix.

## Minimal module and Project creation

```bash
php bin/console sillbeam:make:module user
php bin/console sillbeam:make:project shop
php bin/console sillbeam:make:module checkout --project=Shop
```

Each command creates only a directory and an empty final PHP descriptor class with
`#[SillbeamModule]` or `#[SillbeamProject]`. With the defaults, these are
`src/Module/User/UserModule.php`, `src/Project/Shop/ShopProject.php`, and
`src/Project/Shop/Module/Checkout/CheckoutModule.php`. No controllers, services,
entities, templates or configuration files are generated. Core provides these
commands without Frame or Symfony MakerBundle.

Names start with an ASCII letter and contain only ASCII letters and digits.
Only an initial lowercase ASCII letter is uppercased: `user` becomes `User`,
`userProfile` becomes `UserProfile`, `shop` becomes `Shop`, and `User` stays `User`.
Every other character is preserved exactly. The effective name is used consistently
for the directory, namespace, descriptor class and filename, and appears in the
success output as part of the created path. Paths, namespaces, leading digits,
spaces, underscores, hyphens and non-ASCII letters are rejected.
Pass the base name without a namespace, path or the corresponding `Module` or
`Project` suffix; that suffix is added automatically, and already suffixed names
are rejected to prevent double suffixes.
`--project` accepts an existing discovered Project's directory name, ID or full
descriptor class name. This selector is matched as supplied, without name
normalization. Missing or ambiguous Projects fail without creating files.

Creation uses the application's resolved `sillbeam.discovery` roots, namespaces,
excludes and Composer PSR-4 mappings, exactly as discovery does. Exactly one root
of the requested type must be configured; empty or multiple root lists require
an explicit configuration adjustment before creation. Explicit custom discovery
roots must already exist, as required by discovery. Private modules use
`Module/<Name>` inside their owning Project and the namespace resolved for that
location. Excluded paths, symbolic links in creation paths, overlapping areas,
invalid PHP namespaces and existing target directories/files are rejected.
Existing content is never overwritten, including when normalization makes `user`
and `User` resolve to the same target. Exit codes are 0 for creation and 1 for
validation or write failures.

Descriptors are discoverable immediately. Discovery and activation are separate:
shared modules still need to be selected by a Project when operating in Project
mode. Private modules belong to their owning Project automatically. When multiple
Projects exist, configure `sillbeam.projects.<id>.hosts` for every Project before
the next application boot; the Project command prints this reminder and never
creates configuration on your behalf. An application with invalid existing
architecture/configuration must be corrected before Symfony can run these commands.
