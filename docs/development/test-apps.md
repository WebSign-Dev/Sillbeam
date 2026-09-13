<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Referenz- und Testanwendungen

Unter [`apps/`](../../apps) liegen reale Symfony-Anwendungen, die unterschiedliche Nexum-Nutzungsgrade abbilden. Sie dienen gleichzeitig als ausführbare Beispiele und als Integrationstest-Fixtures.

| App | Szenario | Wichtiger Nachweis |
| --- | --- | --- |
| [`test-app/app`](../../apps/test-app/app) | vollständige Verbraucher-App | Doctrine, API, Security, MFA, Passkeys, Harbor |
| [`symfony-only`](../../apps/symfony-only) | nur normales Symfony | Nexum ohne Module und Projects, normale Services und Routen |
| [`modules-only`](../../apps/modules-only) | nur Shared Modules | Application Mode, Dependencies, deaktivierte Module |
| [`project-only`](../../apps/project-only) | Project ohne Modules | direkte Project-Controller, Services und Doctrine-Entities |
| [`single-project`](../../apps/single-project) | ein Project | hostloses Defaultverhalten, Shared + Private Module |
| [`multi-project`](../../apps/multi-project) | mehrere Projects | Hosts, Aliase, canonical URLs, gleiche private IDs |
| [`hybrid`](../../apps/hybrid) | Symfony + Nexum gemischt | normale Controller neben Project-Routen, unzugeordnetes Modul |

## Modules-only

Die App besitzt `FoundationModule`, `InventoryModule` und ein deaktiviertes `ArchivedModule`. `InventoryModule` hängt von Foundation ab. Der Integrationstest beweist:

- parameterlose Descriptoren werden automatisch entdeckt;
- Abhängigkeiten werden kompiliert;
- Services werden per Autowiring verwendet;
- deaktivierte Controller-Routen existieren nicht;
- `ProjectContext::current()` bleibt `null`.

## Symfony-only

Die App besitzt weder `src/Module` noch `src/Project`. Controller und Services werden ausschließlich über die normale Symfony-Konfiguration geladen. Der Test beweist, dass Nexum die Standardstruktur nicht ersetzt und seine leeren Registries sowie der `ProjectContext` trotzdem sicher benutzbar bleiben.

## Project-only

`PortalProject` besitzt unmittelbar `Controller/`, `Service/` und `Entity/`, aber keinen Modulordner. Doctrine wird bewusst aktiviert. Der Integrationstest beweist:

- ein Project benötigt kein Shared oder privates Module;
- Project-eigene Services werden automatisch verdrahtet;
- Project-eigene Controller erhalten das Project-Routenpräfix;
- Project-eigene Entities werden automatisch als Doctrine-Mapping registriert;
- die Modulregistry bleibt leer.

## Single-project

`PortalProject` verwendet ein Shared `ProfileModule`; `DashboardModule` liegt privat im Project-Baum. Es existiert keine `nexum.projects.portal.hosts`-Konfiguration. Dadurch wird geprüft:

- ein einzelnes Project benötigt keinen Nexum-Host;
- Project- und Modulrouten erhalten `portal.` als Namenspräfix;
- Shared und private Modules sind im Context aktiv;
- Project-eigene Controller und Entities werden nicht nochmals aus dem privaten Modulbaum eingesammelt;
- Symfonys `trusted_hosts` bleibt unabhängig konfigurierbar.

## Multi-project

Storefront und Admin besitzen getrennte Hosts. Das Shared `CatalogModule` gehört nur Storefront; beide Projects besitzen ein privates `CheckoutModule` mit derselben ID. Die App prüft:

- wiederholte private IDs sind erlaubt;
- Shared-Zuordnung ist explizit;
- Aliase funktionieren inbound;
- absolute URLs verwenden den Canonical Host;
- unbekannte Hosts schlagen fail-closed fehl.

## Hybrid

Normale Symfony-Controller und Services laufen neben Public- und Admin-Project. Das Backoffice-Modul hängt transitiv vom Common-Modul ab; ein gefundenes, aber keinem Project zugeordnetes Modul bleibt ungenutzt.

Reale HTTP-Tests belegen, dass `/health` normal erreichbar bleibt, `/common` auf beiden Project-Hosts existiert und `/backoffice` auf dem Public-Host 404 liefert.

## Harbor-Referenzumgebung

Die vollständige lokale Umgebung lebt unter `apps/test-app`:

```bash
cd apps/test-app
fabryq harbor build
fabryq harbor up
fabryq harbor ps
```

Weitere Betriebsdetails stehen im Root-[README](../../README.md) und in [apps/README.md](../../apps/README.md).
