# Sillbeam applications

The repository contains independently bootable Symfony applications for reference and integration testing:

| Application | Purpose |
| --- | --- |
| `test-app/app` | Full Application Mode with normal Symfony controllers, Doctrine entities, API, Security and Sillbeam modules |
| `symfony-only` | Standard Symfony controllers and services with Sillbeam installed, but without Modules or Projects |
| `modules-only` | Minimal stock Symfony kernel with enabled, dependent and disabled shared modules, without Projects or integrations |
| `project-only` | One Project with direct Controller, Service and Doctrine Entity directories, without any Module |
| `single-project` | One Project without Sillbeam host configuration, with a shared and a private module |
| `multi-project` | Multiple host-bound Projects, shared/private modules, aliases and canonical URL generation |
| `hybrid` | Normal Symfony controllers/services beside multiple Projects, transitively shared modules and an unassigned module |

The architecture applications register only `Sillbeam\\SillbeamBundle` as their Sillbeam integration. Module and Project descriptors are excluded from the normal Symfony service scan and are found through Sillbeam discovery. Their boot, container, Doctrine mapping, routing and HTTP behavior is covered by the root PHPUnit suite.
