# Sillbeam directory template

This directory is a starting directory layout and Composer manifest, not a bootable Symfony application and not a published `sillbeam/skeleton` package. It has no Kernel, front controller, console entry point or application configuration. Do not use `composer create-project sillbeam/skeleton` based on this repository alone.

Start with a regular Symfony application, install Sillbeam and register its bundle/configuration/route import using the [getting-started guide](../docs/guides/getting-started.md). Copy only the template directories you need. The storage directories do not enable Sillbeam runtime facilities; Symfony's normal `var/` layout remains supported.

The manifest requires the future published `websign-dev/sillbeam:^0.1`. Before that publication, configure a local path repository and explicitly require `websign-dev/sillbeam:dev-main` as shown in the [README](../README.md). PHP/Symfony constraints match the main package. This template is kept in the repository and excluded from the library's Composer distribution.
