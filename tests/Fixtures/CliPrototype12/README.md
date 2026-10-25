# Sillbeam Core – CLI prototype 1.2

**Status:** presentation prototype based on the approved direction of v1.1. No changes to Sillbeam Core.

## What has changed since 1.1?

- For **one issue**, the full diagnosis appears immediately (problem, cause, conditional next steps).
- For **several issues**, a compact issue index appears first. The first issue is expanded, and the rest can be selected via `--issue=N`. Nothing is deleted: `--all` expands all diagnoses.
- The selected issue remains prominent; module relationships stay cyan, serious caveats yellow, failures red.
- A compact source path is shown instead of absolute filesystem paths. Verbose mode shows the complete fixture locations and rule names.
- Informational application couplings are **not part of the default view**. `--couplings` illustrates a separate opt-in section.
- Output is capped at 90 columns and still supports widths from 52 columns, with and without ANSI formatting.

## Requirements and installation

- PHP 8.4+ and Composer 2
- Symfony Console 7.4 or 8.x; installed by Composer
- No Termwind, no new mandatory PHP extensions beyond Symfony's requirements

```bash
composer install
```

## Compare the scenarios

```bash
php demo.php --scenario=success
php demo.php --scenario=conflict
php demo.php --scenario=multiple
php demo.php --scenario=multiple --issue=2
php demo.php --scenario=multiple --all
```

Other views:

```bash
php demo.php --scenario=multiple --issue=2 -v
php demo.php --scenario=multiple --width=60 --no-ansi
php demo.php --scenario=conflict --couplings
```

For scenarios with architecture errors the demo intentionally exits with status `1`.
Invalid options exit `2` and the success scenario exits `0`.

The `--issue`, `--all` and `--couplings` options belong **only to this prototype**. They do not exist in the production `sillbeam:architecture` command.

## Test status

Run the dependency-free presentation tests:

```bash
php tests/render-smoke.php
```

This tests emitted formatter markup and width constraints using a minimal `OutputInterface` substitute. It is **not** a live Symfony Console execution test. For final approval, run the real `php demo.php` commands above after `composer install`.

The `previews/*.png` images are generated from the renderer markup by `tests/generate-previews.py`, not captured from the running terminal. They should be treated as graphical approximations of the ANSI output.

## Important limitation

All diagnoses, classifications, ordering, suggestions, grouping and filenames are deliberately defined in `src/Scenarios.php`. **There is no implementation of an architecture analyzer or a diagnostic engine.** The real Core must derive these messages from verified graph facts, preserve raw findings and decide only from actual evidence whether a suggested dependency would introduce a cycle.

No automated fixes or changes to application code are provided.
