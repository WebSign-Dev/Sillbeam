# Test-Apps

Die Fixtures verwenden den Root-Autoloader und temporäre Kernel-Caches.

`composer test:consumer` verwendet dieselben Szenarioquellen als Vorlagen für frische, unabhängige Composer-Anwendungen. Jedes Szenario wird ohne/mit Doctrine und mit beiden Kernels geprüft. Deren Vendor-Autoloader verwendet keine Root-Dev-Autoloads. Versionen und Ergebnisse stehen im [Paketkompatibilitätsnachweis](package-compatibility-2026-10-10.md).

| App | Abdeckung |
|---|---|
| apps/symfony-only | gewöhnliche Symfony-App mit Sillbeam ohne Descriptoren |
| apps/modules-only | Shared-Module-Graph, deaktiviertes Modul |
| apps/project-only | direkte Project-Services/-Routen/-Entities |
| apps/single-project | einzelnes Project mit Shared Module ohne Hostpflicht |
| apps/multi-project | Shared-Module-Zuordnung und getrennte Host-Routen |
| apps/hybrid | native Symfony-Services/-Routen neben Project-/Module-Flächen |

`tests/Fixtures/Application` prüft gemeinsame native Doctrine-Persistenz und Modularchitektur. Dynamische temporäre Anwendungen unter `tests/Support` prüfen flexible Roots, Ausschlüsse, Diagnose und einen Kernel ohne Doctrine.

`apps/test-app` ist das separate Harbor-Verbraucherprojekt. Die Symfony-App liegt in `app/`, die lokale Laufzeitkonfiguration in `.fabryq/`. Start: `fabryq harbor build` und `fabryq harbor up` in `apps/test-app`. Die Root-Tests starten diese externe Laufzeit nicht.

Die Harbor-Konfiguration und der lokale Path-Lock gehören nur zur Repository-Entwicklung und werden nicht als Composer-Library ausgeliefert. Die Datenbank-Zugangsdaten im Beispiel sind öffentliche lokale Testwerte, keine Produktions-Credentials. Niemals außerhalb dieser isolierten Testumgebung verwenden. Einen eigenen `APP_SECRET` in der ignorierten `app/.env.dev.local` setzen, etwa mit `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`; nur die Ausgabe lokal speichern. Die vor der Release-Vorbereitung eingecheckten Entwicklungswerte bleiben in der Git-Historie; falls sie jemals anderweitig verwendet wurden, dort ersetzen.
