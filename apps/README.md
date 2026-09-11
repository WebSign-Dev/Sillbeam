# Nexum applications

The repository contains independently bootable Symfony applications for reference and integration testing:

| Application | Purpose |
| --- | --- |
| `test-app/app` | Full Application Mode with normal Symfony controllers, Doctrine entities, API, Security and Nexum modules |
| `modules-only` | Minimal stock Symfony kernel with enabled, dependent and disabled shared modules, without Projects or integrations |
| `single-project` | One Project without Nexum host configuration, with a shared and a private module |
| `multi-project` | Multiple host-bound Projects, shared/private modules, aliases and canonical URL generation |
| `hybrid` | Normal Symfony controllers/services beside multiple Projects, transitively shared modules and an unassigned module |

The architecture applications register only `Nexum\\NexumBundle`. Module and Project descriptors are excluded from the normal Symfony service scan and are found through Nexum discovery. Their boot, container, routing and HTTP behavior is covered by the root PHPUnit suite.
