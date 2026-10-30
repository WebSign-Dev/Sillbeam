# Independent CLI acceptance

Source: `0b70c3143acf25cae5082f3ec6007b6d2a31da79`. No production source edits.

Executed on host PHP 8.5.4:

```sh
php /tmp/sillbeam-cli-acceptance.php
python3 /tmp/sillbeam-final-runner.py cli-targeted-tests vendor/bin/phpunit tests/Integration/GraphCommandTest.php tests/Integration/GraphSvgTest.php tests/Integration/InspectModuleTest.php tests/Integration/ProductionCliTest.php
google-chrome --headless --no-sandbox --disable-gpu --hide-scrollbars --screenshot=/tmp/sillbeam-architecture.png --window-size=1600,1680 file:///home/oeffe/PhpstormProjects/websign/sillbeam-core/docs/development/final-release-acceptance-2026-10-30/agent-cli/architecture.svg
```

The retained runner uses the existing SourceWorkspace, ArchitectureAnalyzer and ArchitectureReport. It creates 12 shared modules, two private User modules in two Projects, 32 direct dependencies and existing PHP type-reference evidence. All 13 command probes match their expected exit codes (including intentional usage/file failures). SVG has 14 nodes and 32 edges and parses as XML. Every command's stdout/stderr and expected/actual exit are retained in results.json. The random fixture namespace and temporary source path are captured as provenance; the fixture directory was removed after execution.

Focused regression suite: **69 tests, 1279 assertions, exit 0**, no skips reported. Covers actual Core and normal Symfony entrypoints; invalid/incomplete architecture, cycles, disabled/unknown activation, collisions and Multi-Project; direct filters; escaping; no process functions; help/quiet/options; safe writes and no-clobber concurrency. The fixture itself directly exercises CommandTester, while both real adapters are covered by those regression tests.

The actual SVG was rendered with local Chrome and the screenshot visually inspected. Labels, scope boxes, Project membership, arrowheads and legend are readable; no clipped labels were seen. Every edge is retained. Dense routing occupies much of the shared scope and long edges cross near the same ports. This is an explicit nonfunctional limitation of the simple native layout, not a claim of crossing minimization. The module filter is the practical focused view. Chrome is only a local acceptance viewer, not a product renderer dependency.

Graphviz absence is not an error condition in this source version: the native PHP SVG renderer succeeds with empty PATH. The historical missing-dot concern is obsolete. Invalid output and accidental overwrite fail with explanatory stderr (exit 4); stdout remains empty for those failures. Default graph emits only the confirmation path, Mermaid emits raw flowchart text, quiet SVG emits no confirmation.
