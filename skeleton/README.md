# Sillbeam directory template

This directory is a starting directory layout and Composer manifest, not a bootable Symfony application and not a published `sillbeam/skeleton` package. It has no Kernel, front controller, console entry point or application configuration. Do not use `composer create-project sillbeam/skeleton` based on this repository alone.

Start with a regular Symfony application, install Sillbeam and register its bundle/configuration/route import using the [getting-started guide](../docs/guides/getting-started.md). Copy only the template directories you need. The storage directories do not enable Sillbeam runtime facilities; Symfony's normal `var/` layout remains supported.

The manifest requires `websign-dev/sillbeam-core:^0.3.0`. Install Sillbeam through Composer in a bootable Symfony application with `composer require websign-dev/sillbeam-core:^0.3.0`, as shown in the [README](../README.md#install-with-composer). PHP/Symfony constraints match the main package. This template is kept in the repository and excluded from the library's Composer distribution.
