# Installation doctor

Approved in chat: a read-only static installation inspector, shared by the regular
Symfony command and existing container-independent Application adapter. Never
execute application configuration or boot a kernel during inspection. Reuse
ConsoleStyle (prototype 1.2). Inspect bundle declarations, console construction,
YAML routes/services and discovery. Unknown/custom/dynamic configuration requires
manual verification, never inferred success. No changes to Flex, diagnostics,
schema, architecture rules or HTTP. No dependencies. Exit 0: all checks verified;
1: proven action required; 2: incomplete/unexecutable (takes precedence).

Alternatives: preparing the real kernel gives effective configuration but can
write cache/application files; rejected for the strict read-only requirement.
Static analysis was selected by the user. Its scope and limitations must be
visible in help and reference. Tests snapshot all consumer file contents and
exercise the existing adapter without boot, plus normal command registration.
