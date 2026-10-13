# CLI

```bash
php bin/console sillbeam:about
php bin/console sillbeam:architecture
php bin/console sillbeam:architecture --format=json
php bin/console debug:container
php bin/console debug:router
php bin/console doctrine:mapping:info  # when DoctrineBundle is installed
```

`sillbeam:architecture` shows the entire catalog, including disabled and unassigned modules. JSON includes scope, owner, enabled/active, inactive_reason (`disabled`/`unassigned`), paths, direct declarations, observed dependencies, file/line evidence and activation reasons. Projects include hosts and their compiled graphs. Unknown output formats return exit code 2.

Architecture errors prevent the container build before CLI commands can run. Their exceptions include the modules involved and, for actual code references, the file/line. Symfony diagnostics remain available alongside them.
