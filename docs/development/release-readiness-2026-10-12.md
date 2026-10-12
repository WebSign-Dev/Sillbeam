# Release-Kandidaten-Prüfung v0.1.0 — 2026-10-12

Die Prüfung erweitert den Nachweis vom 2026-10-10 um normale Symfony-Skeleton-Serviceimports, Development-/Production-Container, warme Caches und die verfügbaren PHP-/Symfony-Versionen. Ausgangspunkt: 74 Tests / 263 Assertions; vorhandene Änderung an `docs/assets/sillbeam-logo.png` unverändert erhalten. Kein Push, Merge, Tag oder Release. Composer-Anforderungen, Lockfile und öffentliche Laufzeit-API bleiben unverändert.

## Korrekturen und Integrationsbefund

- Der Flex-Verbrauchertest nutzte `services: {}` und prüfte damit keinen normalen Skeleton-Import. `tools/test-flex-recipe.php` verwendet jetzt `App\:` mit aktiven Standarddefaults und den nötigen Bereichsausschlüssen, einen nativen Service und ein deaktiviertes Modul. Beide Kernel werden in `dev`/`prod` jeweils zweimal in separaten Prozessen gestartet, mit kaltem und warmem Cache, ohne/mit Doctrine.
- Unter Symfony 8.0.15 und minimalen Symfony-7.4.0-Abhängigkeiten registrierte FrameworkBundle während der Tests einen Exception-Handler: 30 Tests waren „risky“, der alte Testbefehl endete trotzdem mit Exitcode 0. Zuerst reproduziert mit `failOnRisky`, Exitcode 1. `tests/bootstrap.php` registriert Symfonys Handler vor PHPUnits Ownership-Snapshots; `phpunit.xml.dist` benutzt diesen Bootstrap und lässt risky Tests fehlschlagen. Danach keine risky Tests mehr in den geprüften Kombinationen. Keine Unterdrückung der PHPUnit-Prüfung und keine Änderung am Anwendungskernel.
- `SymfonySkeletonTest` belegt: Ein unbeschränkter nativer Import registriert Services auch aus deaktivierten Modulen. Das ist eine bewahrte Symfony-Escape-Hatch, kein Fehler der Sillbeam-Aktivierung. Für Sillbeam-kontrollierte automatische Services müssen `Module`/`Project` beziehungsweise eigene Bereiche aus breiten nativen Imports ausgeschlossen werden. Mit diesen Ausschlüssen bleiben native Services, explizite Argumente/Interface-Aliase, `Exclude`, `When`, `WhenNot` und `AsAlias` erhalten; deaktivierte private und nicht zugeordnete Shared-Module gelangen nicht über Sillbeam in Services/Routen. Die Recipe verändert die Benutzer-Servicekonfiguration nicht.

Keine weiteren reproduzierten Laufzeitfehler in Discovery, Compiler, Quellindex/Parser, Dependency-Analyse, Registries, Routing oder Doctrine; daher keine spekulativen Produktionsänderungen oder Optimierungen. Bestehende Tests prüfen direkte/transitive Abhängigkeiten, Zyklen, private Grenzen, mehrere Roots/Projects, Hosts und Request-Kontext. Ergänzt wurden unbekannte Dependencies in inaktiven Modulen, Aktivierung eines fremden privaten Moduls, statische AST-Typ-/Ausdruckspositionen und explizite Grenzen dynamischer Klassenstrings/PHPDoc/Imports. Doctrine-Tests bestätigen ein natives Override eines generierten Mappings; Cache-Ressourcen werden zusätzlich für Descriptor, Controller und Routenkonfiguration verändert.

Betroffen: `tests/Integration/{SymfonySkeletonTest,ModuleLayerTest}.php`, `tests/Unit/{ArchitectureTest,SourceFileParserTest}.php`, `tests/Support/TestApplicationKernel.php`, `tests/bootstrap.php`, `phpunit.xml.dist`, `tools/{test-flex-recipe,benchmark-architecture}.php`, `.php-cs-fixer.dist.php` sowie README/Getting-started/Flex-/Testing-Dokumentation. Der Testkernel trennt jetzt Environment-/Debug-Container. CS-Checks erfassen auch die beiden Tools.

## Tatsächlich ausgeführte Prüfungen

Alle geforderten Befehle am finalen Code erfolgreich: `composer validate --strict`, `composer test` (82 Tests / 308 Assertions), `composer analyse`, `composer architecture`, `composer cs:check`, `composer test:flex-recipe`, `git diff --check`. Deptrac: 0 Violations/Warnings/Errors; 102 bereits vorhandene externe/nicht abgedeckte Dependencies bleiben ausgewiesen. Erwartete 404-Logs der Negativtests sind keine Testfehler.

| PHP | Symfony FrameworkBundle | Vollständige PHPUnit-Suite |
| --- | --- | --- |
| 8.4.26 | 7.4.0, 7.4.20, 8.0.15, 8.1.6 | jeweils 82 Tests / 308 Assertions, keine risky Tests |
| 8.5.4 | 7.4.0, 7.4.20, 8.0.15, 8.1.6 | jeweils 82 Tests / 308 Assertions, keine risky Tests |

Symfony 7.4.0 wurde mit `--prefer-lowest --prefer-stable` separat aufgelöst: DI 7.4.0, PHP-Parser 5.7.0, DoctrineBundle 3.3.0. Die Testkopien verändern das Repository-Lockfile nicht. PHP 8.4 läuft im vorhandenen Docker-Image `dwell-php-matrix:8.4`, ohne Netzwerk, mit ausschließlich temporärer Testkopie als Mount. PHPStan zusätzlich unter Symfony 7.4.20 und 8.0.15 erfolgreich.

Zusätzlich: `composer test:consumer` bestanden, zwölf unabhängige Installationen (sechs Betriebsformen ohne/mit Doctrine, beide Kernel), PHP 8.5.4 / Symfony 8.1.8 / DoctrineBundle 3.3.2 / ORM 3.7.5. Flex-Skeleton-Tests ohne/mit Doctrine unter Symfony 8.1.8 bestanden; zusätzlich ohne Doctrine unter 7.4.20 und mit Doctrine unter 8.0.15. Diese Flex-Läufe prüfen kalte/warme `dev`-/`prod`-Caches und beide Kernel. Composer-Netzwerk und lokaler HTTP-Endpoint wurden außerhalb der Sandbox ausgeführt.

## Messwerte

Reproduktion: `php tools/benchmark-architecture.php`. Pro Größe/Environment ein eigener Prozess und temporärer Workspace, je Modul Descriptor, Controller und 20 Services; Dependencies und Typreferenzen bilden eine Kette. Gemessen unter PHP 8.5.4 / Symfony 8.1.6. Zeiten in Millisekunden, Peak in MiB:

| Module | Env | Dateien | Index kalt / warm | Discovery nach Index / warm | Graph | Analyse / warm | Container kalt / warm | Routen kalt / warm | Worker-Peak |
| ---: | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 10 | dev | 220 | 86.669 / 0.010 | 5.798 / 4.718 | 0.582 | 0.782 / 0.592 | 432.782 / 9.354 | 10.467 / 4.482 | 28 |
| 10 | prod | 220 | 85.035 / 0.009 | 5.683 / 4.632 | 0.579 | 0.745 / 0.572 | 369.046 / 0.304 | 9.867 / 3.607 | 24 |
| 50 | dev | 1100 | 368.987 / 0.011 | 25.681 / 23.166 | 0.691 | 4.967 / 4.596 | 1172.464 / 43.320 | 53.689 / 32.235 | 42 |
| 50 | prod | 1100 | 372.947 / 0.012 | 25.599 / 23.552 | 0.684 | 5.032 / 4.856 | 1003.527 / 0.313 | 33.448 / 15.315 | 36 |
| 100 | dev | 2200 | 725.209 / 0.010 | 53.332 / 50.049 | 0.993 | 15.372 / 14.115 | 2237.515 / 105.589 | 149.993 / 98.576 | 60 |
| 100 | prod | 2200 | 753.572 / 0.014 | 51.961 / 47.766 | 1.073 | 15.442 / 14.187 | 1914.278 / 0.319 | 84.931 / 38.496 | 50 |

Bei 10/50/100 Modulen entstehen 180/980/1.980 beobachtete Referenzen. Parsing/Discovery und kalte Container-Builds steigen annähernd linear; die Bereichssuche der Dependency-Analyse wächst stärker, benötigt bei 100 Modulen aber nur etwa 15 ms gegenüber rund 750 ms Parsing. Warme Dev-Boots enthalten Ressourcenprüfungen und wachsen mit der Anwendung; warme Prod-Boots nutzen den Dump ohne diese Prüfung. Keine frei erfundenen Grenzwerte und kein nachgewiesener Optimierungsbedarf für diese Größen.

Messgrenzen: „kalt“ bedeutet fehlender Container-/Routing-Cache, **nicht** ein völlig unaufgewärmter PHP-Prozess oder OS-Dateisystemcache. Vor dem Container-Build wurden die separaten Architekturphasen gemessen, Descriptor-Klassen sind bereits geladen. Der Peak umfasst den ganzen Worker einschließlich behaltenem Quellindex/Analyseobjekten, nicht nur den Verbraucher-Kernel. „Routen“ misst `getRouteCollection()`, keinen HTTP-Lasttest. Einzelmessungen mit einem zweiten vollständigen Durchlauf zur Plausibilisierung, keine statistischen Perzentile oder Lastgarantie. Der warme Quellindex gilt nur innerhalb des Builds; Production-Deployments benötigen weiter einen expliziten Cache-Neubau.

## Verbleibende Grenzen

Exakte Patch-Untergrenzen PHP 8.4.0/8.5.0 und Symfony 8.0.0 wurden nicht ausgeführt; PHP-8.4-Flex-Neuinstallationen ebenfalls nicht. Die geprüften Patchversionen sind oben genannt; Symfony 7.4.0 als minimale Symfony-Untergrenze wurde tatsächlich getestet. Dynamische Klassennamen, PHPDoc, String-Service-IDs und Code außerhalb der Modulflächen bleiben außerhalb der statischen Dependency-Prüfung. Native breite Service-/Route-/Doctrine-Imports bleiben ausdrücklich Benutzerkonfiguration und können inaktive Bereiche bewusst aufnehmen; die dokumentierten Ausschlüsse sind eine Integrationsvoraussetzung. Build-Environment-Änderungen und Production-Caches werden im Deployment neu gebaut. Kein offener reproduzierter Defekt im geprüften Umfang. Unabhängige Review der Änderungen ohne actionable Befunde.
