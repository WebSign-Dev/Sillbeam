<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Core

`Nexum\Core` ist die kleinste stabile Grundlage und darf nicht von Module, Project, Routing oder optionaler Infrastruktur abhängen.

## Contracts

| Contract | Zweck |
| --- | --- |
| `NexumComponentInterface` | Marker für Nexum-Komponenten |
| `SubjectInterface` | stabile, nichtleere fachliche Subject-ID |
| `NexumExceptionInterface` | gemeinsamer Throwable-Marker |

`SubjectInterface` trennt eine dauerhafte Identität von veränderbaren Login-Namen wie E-Mail-Adressen. Security, User und Scopes können darauf aufbauen, ohne eine konkrete Entity zu kennen.

Core enthält bewusst keinen Containerzugriff, keinen ProjectContext, keine statischen Helfer und keine Framework-Lifecycle-Annahme. Klassen aus diesem Bereich müssen ohne Kernel testbar bleiben.

Weiter: [Architektur](../architecture/overview.md) und [öffentliche API](../reference/public-api.md).
