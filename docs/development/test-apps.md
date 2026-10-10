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
