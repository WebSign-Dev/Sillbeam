# Sillbeam scope

Sillbeam includes Architecture, Module, Project, native integration, diagnostics and an optional kernel. Doctrine mapping is an optional integration, not a required runtime dependency.

The former Foundation has been removed from this package: APIs/CRUD, Security/User/MFA/Passkeys, entity bases/stores/soft delete, Filesystem/Mailer/QR and generic domain or infrastructure contracts belong in application code or separate packages. [Migration](../migration/modular-monolith.md) describes the transition.

Strata is a possible future library. This change creates neither a Strata implementation nor mutual package dependencies.
