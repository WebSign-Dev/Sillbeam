# Qualitätsprüfung

```bash
composer install
composer test
composer analyse
composer architecture
composer cs:check
composer validate --strict
composer test:flex-recipe
```

Unit-Tests prüfen deklarierte Graphen, flexible Discovery, PSR-4-Auflösung, statische Referenzen und native DI-Attribute/Aliase. Integrationsprüfungen booten normale Symfony-Kernel, sechs Betriebsformen, native Service-Overrides und einen Kernel ohne Doctrine. ORM-Tests prüfen aktive/private/excluded Mappings gemeinsam mit nativen Mappings und Persistenz. Cache-Ressourcen überwachen neu hinzugefügte sowie entfernte Quellpfade.

PHPStan läuft mit `--debug`, damit die Prüfung auch in Umgebungen ohne lokale Worker-Sockets funktioniert. Flex benötigt Composer-Netzwerkzugriff und einen lokalen HTTP-Endpoint; der Test installiert die Recipe in einem temporären Verbraucherprojekt und bootet dessen minimalen Kernel.

Die CI-Matrix installiert aktuelle kompatible Abhängigkeiten für PHP 8.4/8.5 mit Symfony 7.4/8.x. Lokale Ergebnisse werden im [Umsetzungsprotokoll](../superpowers/plans/2026-10-01-sillbeam-evolution-progress.md) festgehalten; eine konfigurierte Matrix ersetzt keine beobachteten CI-Läufe.
