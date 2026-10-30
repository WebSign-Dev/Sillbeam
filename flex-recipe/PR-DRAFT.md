# Add recipe for websign-dev/sillbeam-core 0.3

Sillbeam Core adds module and optional project discovery to Symfony applications.
This recipe enables `Sillbeam\Core\SillbeamCoreBundle` and installs the required
`type: sillbeam` route import. It targets the published MIT-licensed 0.3.0 package.

The recipe copies only `config/routes/sillbeam.yaml`. Installation instructions
link to the versioned getting-started guide for the manual Console adapter and
service-exclusion steps. Existing bootstrap and service definitions are preserved;
no empty package configuration or application PHP files are generated.

Validation:
- Official `lint:manifests --contrib`, `lint:yaml` and `lint:packages`: passed.
- Actual Packagist v0.3.0 with Symfony 8.1.10 / Flex 2.12.0: installation,
  both kernels in development/production, HTTP smoke checks and removal passed.
- Actual Packagist v0.3.0 with Symfony 7.4.22 and DoctrineBundle 3.3.4:
  installation, mappings, both kernels and removal passed.

## Local submission instructions (omit from PR body)

Target: `symfony/recipes-contrib`, branch `main`.
Copy only `flex-recipe/websign-dev/sillbeam-core/0.3/` into the target repository
as `websign-dev/sillbeam-core/0.3/`. This draft, the local README and package
source/tests are not part of the upstream contribution. Use the heading above
as the PR title and the paragraphs preceding this section as its body.

Local recipe commit: `dc71e95aedb6baa8f94a5518c9b7aa9c1902f57f`.
No remote branch, push or PR has been created. Separate explicit push approval
is required before publishing the final local stand. Hosted Symfony Bot checks,
PR-specific license/history checks and external approval remain pending.
