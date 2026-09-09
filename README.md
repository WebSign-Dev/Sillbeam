# Nexum

Nexum ist ein modularer, API-orientierter Backend-Baukasten auf Basis von PHP 8.4, Symfony 7.4 und Doctrine ORM 3. Die Entwicklung findet in einem Monorepo statt; jedes Framework-Modul besitzt trotzdem bereits ein eigenes Composer-Paket und kann später ohne grundlegenden Umbau in ein separates Repository verschoben werden.

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

Harbor erzeugt `apps/test-app/app/.env.local` automatisch. Die Referenz-App beginnt bewusst als saubere Symfony-Anwendung; Nexum-Pakete werden anschließend wie bei einem echten Verbraucherprojekt installiert und konfiguriert.

Der Composer-Lifecycle bereitet die bind-gemounteten Cache- und Logverzeichnisse automatisch für den lokalen PHP-Container vor. Das betrifft ausschließlich Development-Laufzeitordner; Produktionsrechte werden später über das jeweilige Deployment festgelegt.

## Qualitätsprüfungen

```bash
composer test
composer analyse
composer architecture
composer cs:check
```

## Aktueller Foundation-Umfang

- `nexum/core`: gemeinsame Marker und Basiskontrakte
- `nexum/kernel`: Kernel, YAML-Modulregister, Modulrouting und `nexum:about`
- `nexum/entity`: UUIDv7-, Zeitstempel- und Soft-Delete-Basisklassen
- `nexum/doctrine`: Doctrine-Filter, Lifecycle-Zeitstempel und expliziter Entity Store
- `nexum/api`: deklarative REST-Resources, CRUD, JSON-Fehler, Filter, Sortierung und zwei Pagination-Modi
- `nexum/security`: Bearer-Authentifizierung, widerrufbare Token und deklarative Permission-Prüfung
- `nexum/security-mfa`: verschlüsseltes TOTP, Recovery-Codes und kurzlebige Second-Factor-Challenges
- `nexum/security-passkey`: WebAuthn-Passkeys mit usernameless Login, User Verification und Counter-Prüfung
- `nexum/user`: erweiterbare Doctrine-Benutzerbasis, Token Store und Passwortverwaltung
- **http/client**: Request-ID und begrenzte, vertrauenswürdige Client-Metadaten
- **serializer/validator/exception**: sichere JSON-Defaults, wiederverwendbare Constraints und Exception-Verträge
- **filesystem**: lokale oder Flysystem-basierte Speicherung mit Traversal-Schutz und Leselimits
- **logger**: strukturierte, rekursive Redaction sensibler Kontextwerte
- **messenger**: schlanker Symfony-Messenger-Vertrag mit konfigurierbarer Retry-Strategie
- **mailer/qrcode**: begrenzter E-Mail-Versand und SVG-QR-Code-Erzeugung
- **health**: aggregierbare, fail-closed Health Checks
- **management**: gemeinsame CLI-/API-Logik für Modulkonfigurationen
- **search/notification/location/payment/reporting**: provider-neutrale optionale Fachverträge
- `apps/test-app`: isoliertes Harbor-Verbraucherprojekt mit einer frischen Symfony-App
- `tests/Fixtures/Application`: interne Nexum-Testanwendung für Integrationstests

Externe Provider – beispielsweise Stripe, S3, OpenSearch oder Push-Dienste – bleiben bewusst eigene Adapter. Das Framework erzwingt keine Anbieter-SDKs.
