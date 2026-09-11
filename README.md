<p align="center">
  <img src="docs/assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520">
</p>

# Nexum

Nexum ist ein modularer, API-orientierter Backend-Baukasten auf Basis von PHP 8.4, Symfony 7.4 und Doctrine ORM 3. Das Framework wird als ein Composer-Package (`benjaminoeffinger/nexum`) veröffentlicht und als modularer Monolith betrieben. Die Module besitzen klare Namespace- und Abhängigkeitsgrenzen, sind aber keine getrennt installierbaren Pakete oder eigenständig laufenden Dienste.

## Projektstruktur

```text
nexum/
├── src/
│   ├── Api/
│   ├── Core/
│   ├── Doctrine/
│   ├── Entity/
│   ├── Http/
│   ├── Security/
│   │   ├── Mfa/
│   │   └── Passkey/
│   └── ...
├── tests/
├── docs/
├── composer.json
└── README.md
```

Der gemeinsame PSR-4-Einstieg `Nexum\\` zeigt auf `src/`. Daraus entstehen weiterhin die fachlich getrennten Namespaces `Nexum\\Core\\…`, `Nexum\\Entity\\…`, `Nexum\\Security\\…` und so weiter.

## Entwicklungsumgebung

Die lokale Laufzeit wird vollständig durch Fabryq Harbor verwaltet. Jede Anwendung ist ein eigenständiges Harbor-Projekt. Die Referenzumgebung liegt unter `apps/test-app`, ihre Harbor-Konfiguration unter `apps/test-app/.fabryq` und die frisch erzeugte Symfony-Anwendung unter `apps/test-app/app`. Dadurch können weitere Anwendungen mit eigenem Slug und eigener Laufzeit parallel daneben betrieben werden. Im Repository-Root liegen keine Harbor-Dateien.

```bash
cd apps/test-app
fabryq harbor build
fabryq harbor up
fabryq harbor ps
fabryq harbor down
```

Der Projekt-Starter setzt Harbors Node-Prozess auf die lokale `mkcert`-CA. Dadurch
bleibt die HTTPS-Prüfung auch nach einem erneuten Start erfolgreich. Die installierte
CA ist zusätzlich im NSS-Truststore des lokalen Browsers hinterlegt.

Nach dem Start:

- Backend: `https://nexum-test-app.localhost/`
- Mailpit: `https://tools.nexum-test-app.localhost/mp/`
- phpMyAdmin: `https://tools.nexum-test-app.localhost/pma/`
- Shell: `cd apps/test-app && fabryq harbor ssh php`

Harbor erzeugt `apps/test-app/app/.env.local` automatisch. Die Referenz-App beginnt bewusst als saubere Symfony-Anwendung; das Nexum-Package wird anschließend wie bei einem echten Verbraucherprojekt installiert und konfiguriert.

Der Composer-Lifecycle bereitet die bind-gemounteten Cache- und Logverzeichnisse automatisch für den lokalen PHP-Container vor. Das betrifft ausschließlich Development-Laufzeitordner; Produktionsrechte werden später über das jeweilige Deployment festgelegt.

## Qualitätsprüfungen

```bash
composer test
composer analyse
composer architecture
composer cs:check
```

## Installation und Architektur

Nexum besitzt genau einen Symfony-Einstieg:

```php
Nexum\NexumBundle::class => ['all' => true],
```

Bis zur Veröffentlichung der Flex-Recipe kommen eine zentrale `config/packages/nexum.yaml` und dieser einmalige Routenimport hinzu:

```yaml
nexum:
    resource: .
    type: nexum
```

`src/Module/Billing/BillingModule.php` wird allein durch `#[NexumModule]` entdeckt. Eine `modules.yaml`, Bundle-Registrierungen pro Komponente oder ein spezieller Kernel sind nicht erforderlich. `NexumKernel` bleibt als optionaler Komfort-Kernel verfügbar.

Ohne Project-Descriptoren laufen alle aktivierten Shared Modules im Application Mode. Sobald `src/Project/*/*Project.php` existiert, ordnen `#[NexumProject(modules: [...])]` und die Verzeichnisstruktur Shared beziehungsweise private Modules den hostbasierten Projects zu. Das steuert Routen und Oberfläche, nicht die Isolation des Containers, der Datenbank oder von Symfony Subscriber/Handlern.

Symfony bleibt vollständig nutzbar. Reine Klassen wie `Money`, `Coordinates`, Reports und Entity-Basisklassen funktionieren ohne Kernel; Framework-Services verwenden normales Dependency Injection. Nexum führt keinen statischen Service-Locator ein.

## Aktueller Foundation-Umfang

- **Core**: gemeinsame Marker und Basiskontrakte
- **Kernel/Module/Project**: optionaler Komfort-Kernel, automatische Descriptor-Discovery, unveränderliche Registries, Host-Auflösung und `nexum:about`
- **Entity**: UUIDv7-, Zeitstempel- und Soft-Delete-Basisklassen
- **Doctrine**: Doctrine-Filter, Lifecycle-Zeitstempel und expliziter Entity Store
- **Api**: deklarative REST-Resources, CRUD, JSON-Fehler, Filter, Sortierung und zwei Pagination-Modi
- **Security**: Bearer-Authentifizierung, widerrufbare Token und deklarative Permission-Prüfung
- **Security/Mfa**: verschlüsseltes TOTP, Recovery-Codes und kurzlebige Second-Factor-Challenges
- **Security/Passkey**: WebAuthn-Passkeys mit usernameless Login, User Verification und Counter-Prüfung
- **User**: erweiterbare Doctrine-Benutzerbasis, Token Store und Passwortverwaltung
- **http/client**: Request-ID und begrenzte, vertrauenswürdige Client-Metadaten
- **serializer/validator/exception**: sichere JSON-Defaults, wiederverwendbare Constraints und Exception-Verträge
- **filesystem**: lokale oder Flysystem-basierte Speicherung mit Traversal-Schutz und Leselimits
- **logger**: strukturierte, rekursive Redaction sensibler Kontextwerte
- **messenger**: schlanker Symfony-Messenger-Vertrag mit konfigurierbarer Retry-Strategie
- **mailer/qrcode**: begrenzter E-Mail-Versand und SVG-QR-Code-Erzeugung
- **health**: aggregierbare, fail-closed Health Checks
- **management**: read-only Architekturansicht per CLI und optionaler GET-API
- **search/notification/location/payment/reporting**: provider-neutrale optionale Fachverträge
- `apps/test-app`: isoliertes Harbor-Verbraucherprojekt mit einer frischen Symfony-App
- `tests/Fixtures/Application`: interne Nexum-Testanwendung für Integrationstests

Externe Provider – beispielsweise Stripe, S3, OpenSearch oder Push-Dienste – bleiben bewusst eigene Adapter. Das Framework erzwingt keine Anbieter-SDKs.

## Dokumentation

Das vollständige, navigierbare Handbuch beginnt unter [`docs/README.md`](docs/README.md). Dort finden sich Quickstart, Architektur, Konfigurationsreferenz, Module, Projects, Security, Integrationen, Test-Apps, CLI und Migration.
