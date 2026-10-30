# Independent documentation, metadata and real ZIP review

Reviewed commit: `0b70c3143acf25cae5082f3ec6007b6d2a31da79` on `dev-v0.3.x`.
Date: 2026-10-30. This review changes no tracked source, metadata, workflow or historical evidence.

## Results

- Reviewed README, current CHANGELOG, CONTRIBUTING, release/upgrade notes, current graph/module-inspection/PHPDoc-JSON references and CLI reference against actual command definitions.
- Current descriptions consistently specify built-in PHP SVG as default, recursive parent creation, explicit `--force`, raw `--format=mermaid`, direct incident filtering, Project context and distinct exit codes. Historical optional Graphviz requirements are explicitly obsolete; no renderer-selection option is promised.
- Inspection default and `-v`, evidence classifications, unknown activation, Project/global distinction, collision selection and text-only interface agree with implementation. No new JSON inspection format is documented.
- PHPDoc JSON is documented as a manual development command, English schema version 1, independent of HTML, without translation, synchronization, publication or CI production-export hook.
- Current release flow is development `dev-v0.3.x` → `dev` automatic CI → maintainer approval → `main` automatic CI → explicitly authorized release. PR guidance targets `dev`. Current docs do not represent historical matrix results as fresh verification of subsequent refinements.
- Composer aliases are `dev-dev-v0.3.x`, `dev-dev`, `dev-main` → `0.3.x-dev`. Active consumer/Flex/release-package versions and example app requirements are 0.3.0. Root package has no fixed release version. Compatible Flex recipe stays under `0.2/`.
- Parsed actual workflow using Symfony YAML: push branches exactly `main`/`dev`, manual dispatch present, no PR trigger. Workflow blob remains `fff5249f69ea1107d9811b27056f1c3bd5a86e84` (SHA-256 `a8a1462d79716dd22c8acb9aacb3368ddf772427ce127ec563c8effd96f3cb61`). No workflow writes or remote actions.
- **46 current root/public Markdown files, 197 relative path targets, no broken paths**. All **16 unique immutable GitHub repository-link targets** exist at the cited local Git objects. This proves local object/path correctness, not external HTTP availability.
- `composer validate --strict --working-dir=apps/test-app/app`: exit 0, valid.

## Actual ZIP inspected

Ran the existing native `PackageArchive::inspect` against the actual artifact `../artifacts/sillbeam-core-0.3.0.zip`, not an inferred file list. See [inventory](package-inventory.json).

- **130 files**, **2,269,714 compressed bytes**, **2,714,066 uncompressed bytes**.
- SHA-256: `047c22812451c38757f89a23f6356c83e2a38e192ecbe4c4ed050f471f682f42`.
- **77 runtime source files**, all byte-identical to this checkout.
- **46 docs files**, plus 7 root metadata/license/public-doc files.
- **197 package-local documentation links** resolve against ZIP contents alone.
- No `docs/development`, `docs/superpowers`, tests, tools, apps, CI files, caches, logs, root lockfile, development renderer/templates or temporary acceptance artifacts in ZIP.
- The single `.gitattributes` policy preserves all expected source/public docs and excludes unlisted paths; no competing Composer `archive.exclude` mechanism remains.

## Executed checks

```sh
python3 /tmp/sillbeam-acceptance-docs.py
python3 /tmp/sillbeam-acceptance-repo-links.py
php -r 'require "vendor/autoload.php"; echo json_encode(Symfony\Component\Yaml\Yaml::parseFile(".github/workflows/ci.yml")["on"], JSON_PRETTY_PRINT),PHP_EOL;'
php -r 'require "tests/Support/PackageArchive.php"; $path=getcwd()."/docs/development/final-release-acceptance-2026-10-30/artifacts/sillbeam-core-0.3.0.zip"; $result=Sillbeam\Core\Tests\Support\PackageArchive::inspect($path,getcwd()); $result["zip_bytes"]=filesize($path); $result["zip_sha256"]=hash_file("sha256",$path); echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),PHP_EOL;'
composer validate --strict --working-dir=apps/test-app/app
```

Helper source is preserved as [public path checker](check-public-links.py) and [immutable link checker](check-immutable-links.py). Structured evidence: [metadata/paths](metadata-links.json), [immutable targets](immutable-repository-links.json), [parsed CI](ci-triggers.json), [actual ZIP](package-inventory.json), [app validation log](app-composer-validate.log).

## Limits and recommendation

No actionable documentation, release-metadata or distribution-boundary contradiction found in this independent sub-review. This is not the overall GO recommendation: the parent acceptance runner supplies the complete matrix/CLI/runtime findings. External URL responses and individual Markdown anchors were not comprehensively crawled. Historical v0.2 notes/raw measurements remain untouched. A local matrix cannot replace remote Actions on the final approved commit or maintainer release authorization.
