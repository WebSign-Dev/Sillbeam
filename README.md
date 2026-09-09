# Nexum

Nexum ist ein modularer, API-orientierter Backend-Baukasten auf Basis von PHP 8.4, Symfony 7.4 und Doctrine ORM 3. Die Entwicklung findet in einem Monorepo statt; jedes Framework-Modul besitzt trotzdem bereits ein eigenes Composer-Paket und kann später ohne grundlegenden Umbau in ein separates Repository verschoben werden.

## Entwicklungsumgebung

Die lokale Laufzeit wird vollständig durch Fabryq Harbor verwaltet. Harbor erkennt das Backend über den flachen Kompatibilitätspfad `backend`, der auf `apps/test-app` zeigt. Dieser Alias ist nötig, weil Harbor 1.0 in Rollenpfaden noch keine verschachtelten Verzeichnisse wie `apps/test-app` unterstützt.

```bash
./tools/harbor build
./tools/harbor up
./tools/harbor status
./tools/harbor down
```

Der Projekt-Starter setzt Harbors Node-Prozess auf die lokale `mkcert`-CA. Dadurch
bleibt die HTTPS-Prüfung auch nach einem erneuten Start erfolgreich. Die installierte
CA ist zusätzlich im NSS-Truststore des lokalen Browsers hinterlegt.

Nach dem Start:

- Backend: `https://nexum.localhost/`
- Mailpit: `https://tools.nexum.localhost/mp/`
- phpMyAdmin: `https://tools.nexum.localhost/pma/`
- Shell: `./tools/harbor ssh php`

Harbor erzeugt `apps/test-app/.env.local` automatisch. Damit verwendet die Development-App MariaDB und Mailpit aus dem Harbor-Stack. Tests verwenden weiterhin eine isolierte SQLite-Datenbank.

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
- `apps/test-app`: ausführbare Referenz-App für modulare und klassische Symfony-Strukturen

Die weiteren Paketordner bilden die geplanten, späteren Bausteine ab. Sie werden erst implementiert, wenn ihre Foundation-Schnittstellen feststehen.
