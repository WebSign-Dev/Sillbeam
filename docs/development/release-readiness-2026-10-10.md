# Technische Release-Prüfung — 2026-10-10

Geprüft wurden alle 37 Produktionsdateien des Modullayers und die zugehörigen Unit-/Integrationstests: Discovery und physische Ownership, deklarierte und statische Dependencies, Aktivierungsgraphen, Symfony-DI/Routing, Doctrine, Registries/Request-Kontext, Cache-Ressourcen und Diagnose-API. Ausgangspunkt: `3d1c510`, sauberer Arbeitsbaum, 65 Tests / 240 Assertions.

## Reproduzierte und korrigierte Fehler

- Ohne entdeckte Projects wurde unbekannte `projects`-Konfiguration ignoriert. Die bestehende Prüfung unbekannter Project-IDs greift jetzt auch im Anwendungsmodus.
- Eine Klasse mit Modul- und Project-Attribut wurde abhängig von der Attributreihenfolge einer Ownership zugeordnet. Mehrere Descriptor-Attribute werden jetzt vor Reflection eindeutig abgelehnt.
- Bereits geladene Descriptor-Attribute mit anderer Groß-/Kleinschreibung wurden vom AST-Index übersehen. Der Index folgt jetzt PHPs Klassenauflösung und speichert den kanonischen Attributnamen. Composer-Autoloading und PSR-4-Anforderungen bleiben bestehen.
- Modul-/Project-IDs mit abschließendem Zeilenumbruch bestanden die Formatprüfung. Die vorhandene ID-Regel prüft jetzt die vollständige Zeichenfolge.
- Der Doctrine-Pass sortierte auch native Mapping-Treiber um und änderte damit deren Priorität bei überlappenden Namespaces. Jetzt werden nur generierte Sillbeam-Mappings innerhalb ihrer Positionen geordnet; native Mapping-Reihenfolge und andere Treiberaufrufe bleiben erhalten.

Jeder Fehler wurde zunächst durch einen fehlgeschlagenen Regressionstest belegt. Die Korrekturen ändern weder die Architektur noch die erlaubten Abhängigkeitsrichtungen. Ein bestehender AST-Test enthielt unpassende Konfiguration nicht vorhandener Projects; diese wurde entfernt.

## Erhaltene Semantik und geprüfte Grenzen

Aktive Graphen benötigen aktivierte Dependencies; unbekannte Abhängigkeiten, Zyklen, überlappende Descriptorflächen und fremde private Module bleiben Fehler. Nicht aktive Module werden weiterhin katalogisiert. Die statische Prüfung betrifft tatsächliche Klassenreferenzen zwischen Modulflächen; normale Symfony-Klassen, direkter Project-Code und dynamische Klassenstrings erhalten keine zusätzlichen Architekturregeln. Native Serviceimporte, Aliase, Environment-Attribute, Routen und manuelle Doctrine-Mappings bleiben verfügbar.

`ProjectRegistry::forHost()` und der Context werfen bei vorhandenen Projects und nicht zugeordnetem Host weiterhin `OutOfBoundsException`. Fehlende Projects oder Requests ergeben im Context `null`; ein einzelnes Project ohne Hosts gilt für jeden Host. Im Project-Modus gibt es ohne Request keinen aktiven Anwendungsgraphen für `hasModule()`. Diese bestehende Semantik ist jetzt ausdrücklich dokumentiert und zusätzlich für Subrequests und native Hybrid-Routen geprüft. Symfonys eigene Trusted-Host-Prüfung bleibt vorgelagert.

Die vorhandenen Tests decken fehlende Standardroots, neue/gelöschte Dateien in Custom-Roots und Änderungen an Konfiguration/Composer als Cache-Ressourcen ab. Umgebungswerte zur Aktivierung bleiben Build-Konfiguration; Änderungen benötigen einen Cache-Neubau. Produktionscaches werden weiterhin im Deployment neu gebaut.

## Skalierung

Synthetische PSR-4-Workspaces mit 20 Klassen pro Shared-Modul plus Descriptor; jeder nachfolgende Bereich hängt vom vorherigen ab und referenziert dessen Klasse. Discovery, Compiler und Codeanalysator verwenden denselben Quellindex. Die Project-Variante ergänzt vier Projects mit je fünf privaten Modulen sowie Controller-/Entity-Verzeichnissen. Gemessen auf PHP 8.5.4, ohne persistierten Discovery-Cache:

| Module / Projects | PHP-Dateien | Referenzen | Discovery | Compiler | Codevalidierung |
| --- | ---: | ---: | ---: | ---: | ---: |
| 20 / 0 | 420 | 380 | 0,170 s | 0,001 s | 0,002 s |
| 100 / 0 | 2.100 | 1.980 | 0,726 s | <0,001 s | 0,016 s |
| 200 / 0 | 4.200 | 3.980 | 1,434 s | 0,001 s | 0,046 s |
| 220 / 4 | 4.272 | 3.980 | 1,472 s | 0,001 s | 0,047 s |

Speicherpeak des gemeinsamen Benchmark-Prozesses: 16 MiB. Diese Messungen zeigen keinen relevanten Engpass der Discovery/Validierung in den geprüften Strukturen; keine Performance-Optimierung vorgenommen. Sie messen keinen vollständigen Container-Build und sind keine Lastgarantie für beliebige Graphen oder Dateisysteme.

## Abschlussprüfungen und verbleibende Grenzen

- `composer test`: 74 Tests, 263 Assertions bestanden.
- `composer analyse`: keine PHPStan-Fehler.
- `composer architecture`: keine Deptrac-Verstöße, Warnungen oder Fehler. 102 externe/nicht von den eigenen Layers erfasste Abhängigkeiten bleiben als „Uncovered“ ausgewiesen.
- `composer cs:check`: keine Style-Abweichungen.
- `composer validate --strict`: gültige Composer-Metadaten und Lockfile.
- `composer test:flex-recipe`: Installation und minimaler Consumer-Boot bestanden. Der lokale HTTP-Port erforderte Ausführung außerhalb der Sandbox.
- Unabhängige Diff-Prüfung ohne weitere Befunde; betroffene Tests nochmals separat bestanden.

Lokal geprüft: PHP 8.5.4 / Symfony 8.1.6; der Flex-Consumer löst seine Dependencies separat auf. PHP 8.4 und Symfony 7.4/8.0 wurden in dieser Prüfung nicht erneut ausgeführt; dafür bleibt die vorhandene CI-Matrix maßgeblich. PHP CS Fixer weist auf den Unterschied zur minimal unterstützten PHP-Version hin; die Änderungen verwenden keine PHP-8.5-exklusive Syntax. Keine weiteren reproduzierten Fehler im geprüften Umfang offen. Keine Veröffentlichung, Tags oder Push.
