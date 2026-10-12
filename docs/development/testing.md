# Qualitätsprüfung

```bash
composer install
composer test
composer analyse
composer architecture
composer cs:check
composer validate --strict
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
composer test:consumer
```

Unit-Tests prüfen deklarierte Graphen, flexible Discovery, PSR-4-Auflösung, statische Referenzen und native DI-Attribute/Aliase. Integrationsprüfungen booten normale Symfony-Kernel, sechs Betriebsformen, native Service-Overrides und einen Kernel ohne Doctrine. ORM-Tests prüfen aktive/private/excluded Mappings gemeinsam mit nativen Mappings und Persistenz. Cache-Ressourcen überwachen neu hinzugefügte sowie entfernte Quellpfade.

PHPStan läuft mit `--debug`, damit die Prüfung auch in Umgebungen ohne lokale Worker-Sockets funktioniert. Für die Analyse sind 512 MiB PHP-Speicher vorgesehen. Flex benötigt Composer-Netzwerkzugriff und einen lokalen HTTP-Endpoint; der Test installiert die Recipe in einem temporären Verbraucherprojekt und bootet beide Kernelvarianten, optional mit Doctrine.

`test:consumer` installiert die sechs Anwendungsszenarien jeweils ohne und mit Doctrine in unabhängigen Composer-Projekten und prüft beide Kernels, HTTP/Autowiring, CLI, Mappings/Persistenz und Dependency-Validierung. Der Root-Testautoloader wird dort nicht geladen.

Die CI-Matrix installiert aktuelle kompatible Abhängigkeiten für PHP 8.4/8.5 mit Symfony 7.4/8.0/8.1 einschließlich passender transitiver Komponenten. Der [Paketkompatibilitätsnachweis](package-compatibility-2026-10-10.md) enthält die tatsächlich geprüften Kombinationen und Reproduktionsbefehle. `tools/prepare-compatibility.php` ist ausschließlich für wegwerfbare Testkopien bestimmt und verändert deren Composer-Anforderungen; eine konfigurierte Matrix ersetzt keine beobachteten Testergebnisse.

`SymfonySkeletonTest` prüft den normalen Namespace-Serviceimport mit und ohne Bereichsausschlüsse sowie native Overrides, Interface-Aliase und DI-Attribute in Development/Production mit warmen Containern. `tests/bootstrap.php` registriert den Symfony-Exception-Handler vor PHPUnits Test-Snapshots; `failOnRisky` verhindert erfolgreiche Exitcodes bei zurückgelassenen Handlern, auch unter Symfony 7.4/8.0.

Reproduzierbarer Performance-Test: `php tools/benchmark-architecture.php`. Jede Kombination aus 10/50/100 Modulen und Development/Production läuft in einem eigenen Prozess mit temporärem PSR-4-Workspace, 20 verketteten Services plus Descriptor und Controller pro Modul. JSON-Zeilen enthalten kalten/warmen Quellindex, Discovery, Graph-Compiler, Dependency-Analyse, Container-Boot, Routenladen und Prozess-Speicherpeak. Der warme Index gilt innerhalb eines Builds; er ist kein persistierter Discovery-Cache. Messwerte und Grenzen stehen im [aktuellen Release-Audit](release-readiness-2026-10-12.md).
