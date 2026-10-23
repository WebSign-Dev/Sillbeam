# Validation lifecycle

Use Symfony's existing kernel.debug policy: debug builds validate on cold/rebuilt containers and watch existing source/config resources; non-debug web requests must use a prebuilt valid container. Do not add architecture configuration, another artifact store, validation rules or routes.

1. Add cold/warm dev/prod subprocess tests for boot, HTTP, source changes and build rejection; cover stale warm-cache warmup and Doctrine/service/routing functionality.
2. Guard non-debug container compilation before Sillbeam configuration/discovery when invoked by HTTP (web SAPI, request context, native worker runtime or Kernel::handle). Return a generic 503 without source/diagnostic details. Warm compiled state never executes this guard or analyzer. Explicit CLI compilation stays strict.
3. Add a console command subscriber that runs the existing reporter/analyzer before cache:clear and cache:warmup, including warm caches, --no-warmup and --no-optional-warmers. Invalid checks fail nonzero before the command proceeds. Publish only after successful build in a fresh release cache.
4. Add rule-specific remediation through existing findings/context and text reporting/aggregate exception formatting; preserve JSON schema v1 and rule boundaries.
5. Verify source mutations invalidate debug containers while non-debug HTTP uses prebuilt state, missing state is safely unavailable, invalid fresh CLI builds and stale-cache build checks fail. Run full suite, static/style/architecture checks and Symfony 7.4 compatibility. Document timing and deployment steps. No release/push operations.
