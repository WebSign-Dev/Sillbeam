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
