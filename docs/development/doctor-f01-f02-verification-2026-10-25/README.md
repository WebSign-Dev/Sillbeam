# Doctor F01/F02 — ergänzende Installationsnachprüfung

Datum: **25.10.2026**. Ausgangspunkt: `dev`, Commit `07be0c7` plus die hier
beschriebenen lokalen Korrekturen. Version **v0.2.0 bleibt unreleased**.

Diese Nachprüfung ergänzt die [ursprüngliche unabhängige Abnahme](../installation-acceptance-2026-10-25/INSTALLATIONS-ABNAHME.md).
Deren Bericht, Reproduktionen und Rohdaten wurden nicht verändert. Neue Consumer
und Matrixkopien liegen ausschließlich in neuen `/tmp`-Verzeichnissen; vorhandene
Testanwendungen wurden nicht überschrieben. Die ursprünglichen F01/F02 sind dort
weiterhin als historische Befunde nachvollziehbar.

## Ergebnis und Freigabeeinschätzung

**F01 und F02 sind für die nachfolgend geprüften Konventionen behoben.** Ein
normaler Symfony-Runtime-Callback bestätigt den korrekt eingebundenen Core-Adapter;
der fehlende Adapter erhält einen konkreten Hinweis auf `bin/console`, den Import
und den Erhalt von Bootstrap/Kernel. Ein unbekannter Routing-Aspekt verdeckt keinen
unabhängig belegbaren Service-Import-Konflikt mehr.

Alle unten aufgeführten abschließenden Prüfungen bestanden. Aus Sicht dieser
Installationskorrekturen kann der geprüfte Stand für v0.2.0 freigegeben werden.
Dies ist keine Veröffentlichung und keine Zusage, dass Doctor beliebige dynamische
Symfony-Konfiguration verifizieren kann. Unbekannte Teilbereiche benötigen weiterhin
manuelle Prüfung; insbesondere können unveränderte Standard-Routen Exit 2 ergeben.
**F03 bleibt unverändert:** Der normale Symfony-Einstieg ohne Core-Adapter kann
bereits vor Doctor Cache-Dateien schreiben. Öffentliche Paketveröffentlichung,
Release-Tag und externe Contrib-Recipe-Freigabe bleiben separate Schritte.

## Ursachen und Korrekturen

### F01 — zurückgegebene Application und Standard-Kernel

Der bisherige AST-Inspector akzeptierte nur das abschließende Muster
`$app = new Application(...); $app->run();`. Der reguläre Runtime-Callback war
nicht abgedeckt. Die Kernel-Prüfung lehnte außerdem jede eigene Methode ab und
damit auch das normale private `getAllowedEnvs()` des Symfony-8.1-Skeletons.

Die statische Auswertung prüft jetzt die tatsächlich zurückgegebene Application
im konventionellen Callback. Namensauflösung erfolgt weiter über PHP-Parsers
`NameResolver`: Imports, Aliase und vollständig qualifizierte Namen werden
berücksichtigt. Shebang und die üblichen Symfony-Abhängigkeitsprüfungen sind
abgedeckt. Ein direktes `return new Application(...)` sowie eine unmittelbar
vorhergehende eindeutige Zuweisung mit `return $app` werden erkannt.

Kommentare und unbenutzte Konstruktionen beweisen keine Adapterverwendung.
Verzweigungen, dynamische Rückgaben und mögliche spätere Überschreibungen bleiben
unverifiziert. Ein zusätzlicher Review fand eine verschachtelte Überschreibung in
einem Konstruktorargument; der zunächst fehlschlagende Regressionstest wurde
anschließend durch die konservative Prüfung der letzten Zuweisung erfüllt. Auch
mögliche Referenzänderungen durch einen späteren Konstruktor werden nicht geraten.

Unterstützt sind direkte Standard-Kernel mit `MicroKernelTrait`, einschließlich
der privaten Methode `getAllowedEnvs(): array` mit ausschließlich wörtlicher
Stringliste, sowie unveränderte Unterklassen des vorhandenen `SillbeamKernel`.
Eigene Lade-Hooks, weitere unbekannte Klassenkonventionen oder dynamische
Methodenkörper bleiben unverifiziert. Bei unbekannten Kernel-Hooks gelten auch
die davon abhängigen Routen-/Discovery-/Service-Ergebnisse nicht als verifiziert.

### F02 — bereichsspezifische Unsicherheit

Ein gemeinsames `$unknown` übertrug Unsicherheit aus Routen auf Discovery und
Services. Dadurch ging der konkrete Konflikthinweis beim Standard-Fehlerrouten-
Overlay verloren.

Routing wird nun getrennt bewertet. Discovery und Services behalten ihre echten
gemeinsamen Abhängigkeiten: Nicht auswertbare Container-Imports können beide
beeinflussen. YAML-Lesefehler werden je Datei erfasst, damit beispielsweise eine
fehlerhafte Routing-Datei das Lesen der Service-Dateien nicht abbricht.

Ein sicher bestimmter Service-Konflikt erhält `action required` mit Datei und
nächstem Schritt. Zusätzliche unabhängig unbekannte Service-Details erhalten ein
weiteres `not verified`-Ergebnis. Die vorhandenen drei Statuswerte und die
Exit-Codes reichen dafür aus; **Exit 2 behält Vorrang vor 1**. Unbekannte
Ausschlüsse, überschreibende doppelte Service-IDs oder unbekannte Discovery-Roots
werden nicht als sicherer Konflikt oder als sichere Konfiguration ausgegeben.

## Geänderte Dateien und Sicherheitsgrenzen

- `src/Console/Doctor/InstallationInspector.php`: ausschließlich statische
  Bootstrap-/Kernel-Erkennung und getrennte Konfigurationsauswertung.
- `tests/Integration/DoctorTest.php`: Regressionen und konservative Gegenbeispiele.
- `CHANGELOG.md`, `docs/releases/0.2.0.md`, `docs/reference/cli.md` und
  `docs/guides/getting-started.md`: nachgewiesene Korrekturen und verbleibende Grenzen.
- `README.md`, `docs/guides/flex-recipe.md`, `docs/migration/0.2.0.md`: notwendige
  Korrektur der dort zuvor beschriebenen F01/F02-Einschränkungen.
- Dieses ergänzende Prüfverzeichnis: Befehle, Skripte, Ergebnisse und Rohprotokolle.

`DoctorCommand` und der vorhandene `Console\Application`-Adapter benötigen keine
Änderungen: Darstellung und Exit-Code-Ermittlung verarbeiten die getrennten
Ergebnisse bereits. Keine Änderungen an Diagnostic Engine, Architekturregeln,
JSON-Schema, Aktivierung, Produktions-HTTP, Composer-Anforderungen oder Recipe.
Der Doctor führt weiterhin keine Anwendungskonfiguration aus, bootet keinen Kernel,
kompiliert keinen Container und korrigiert keine Dateien.

## Gezielte Regressionstests

```bash
vendor/bin/phpunit tests/Integration/DoctorTest.php --no-progress
```

**23 Tests / 732 Assertions bestanden**; siehe [Protokoll](logs/final-doctor-tests.log).
Die neuen Fälle wurden vor der jeweiligen Korrektur fehlschlagend beobachtet.
Abgedeckt sind Runtime-Rückgaben, Symfony-/Core-Imports, Aliase/FQCNs, Kommentare,
unbenutzte und überschriebene Variablen, mehrdeutige Callbacks, Standard- und eigene
Kernel, Routing-Overlays/-Imports/-Syntaxfehler, sichere/überlappende Service-Imports,
gemischte bekannte/unbekannte Service-Details, unbekannte Ausschlüsse, doppelte
Service-IDs und dynamische Container-/Discovery-Konfiguration.

Die bestehenden Tests für den regulären Symfony-Einstieg nach Boot, den realen
containerunabhängigen Einstieg bei ungültiger Architektur, unveränderte Dateien,
`--help`, `--quiet` und konsistente Exit-Codes bestehen weiterhin.

## Echte Symfony-Consumer

Finales isoliertes Verzeichnis:
`/tmp/sillbeam-doctor-final-consumers-20261025/`.
Die neuen Consumer übernehmen Bootstrap, Kernel und Konfigurationsvorlagen aus den
archivierten 7.4/8.1-Reproduktionen. Ihre `vendor`-Verzeichnisse wurden **neu mit
Composer installiert**; kein übernommenes `vendor` im finalen Nachweis. Core wird
aus einer Kopie des aktuellen lokalen Quellstands per Composer-Path-Repository mit
`symlink: false` installiert. Der Bootstrap bleibt ein Runtime-Callback; nur der
Import wird in den vorgesehenen Testschritten manuell gewechselt.

Die archivierten Dependency-Locks bleiben Grundlage; `composer update
websign-dev/sillbeam-core` aktualisiert die lokale Paketquelle. Es wird keine
unabhängige Neuauflösung aller Consumer-Abhängigkeiten unter PHP 8.4 behauptet.
Der Recipe-Installationsweg wird zusätzlich durch die bestehenden CI-Skripte geprüft.

| Szenario, jeweils Symfony 7.4.21 und 8.1.9 | Erwartetes und erhaltenes Ergebnis |
| --- | --- |
| Stock-Runtime, Symfony-Application | Konkreter Adapter-Handlungsbedarf; Exit 2 wegen weiterhin unklarer Routen |
| Nur Import auf Core geändert | Adapter und Standard-Kernel verifiziert; Routen unbekannt; Discovery/Services verifiziert; Exit 2 |
| F02: breiter Import plus Standard-Fehlerrouten | Datei/Service-Konflikt und Ausschlusshinweis sichtbar; nur Routing unbekannt; Exit 2 |
| Derselbe Import ohne Overlay, literale Routen | Konkreter Konflikt; Exit 1 |
| Sichere Ausschlüsse und literale Routen | Sechs verifizierte Ergebnisse; Exit 0 |
| Unbekannter Service-Glob | Services unverifiziert; Exit 2 |
| Ausführbare PHP-Discovery-Konfiguration mit Throw-Sentinel | Nicht ausgeführt; Discovery unverifiziert; Exit 2 |
| Ungültige Architektur | Architekturprüfung Exit 1, Markdown-Report Exit 1; Doctor weiterhin verfügbar, Exit 0 für die statische Installation |
| Doctor `--help` / `--quiet` | Hilfe Exit 0; Quiet leer und Exit 0 |
| Architektur repariert | Architektur und Doctor Exit 0 |

**56/56 Szenarien bestanden:** 14 je Symfony-Linie und PHP-Version, unter PHP
**8.5.4** und dem vorhandenen lokalen Docker-Image mit PHP **8.4.26**. In allen
**40** als lesend geprüften Doctor-/Hilfe-/Quiet-Aufrufen blieben vollständige
Vorher-/Nachher-Snapshots unverändert: Dateiinhalte, Modi, Änderungszeiten und
Verzeichnisbestand, einschließlich `var/cache` und `vendor`. Die regulären
Symfony-Boot-Aufrufe sind wegen F03 ausdrücklich nicht Teil dieser Null-Schreib-
Behauptung. Architekturberichte schreiben ihre angeforderte Ausgabedatei separat.

Belege: [PHP 8.5 Ergebnisse](php85-results.json),
[PHP 8.4 Ergebnisse](php84-results.json),
[Versionen 7.4](sf74-versions.json), [Versionen 8.1](sf81-versions.json),
[Umgebung und Quell-Hashes](environment.json). Die installierten Inspector-Dateien
haben denselben SHA-256 wie der geprüfte aktuelle Quellstand.

### Reproduktion

Die Skripte schreiben ausschließlich neue Consumer/Testzustände. Das Zielverzeichnis
muss noch nicht existieren; die ursprünglichen Abnahme-Consumer bleiben Eingabe.

```bash
python3 docs/development/doctor-f01-f02-verification-2026-10-25/scripts/prepare-consumers.py \
  /tmp/sillbeam-doctor-new-check \
  /home/oeffe/PhpstormProjects/websign/Sillbeam \
  /home/oeffe/PhpstormProjects/websign/test/sillbeam-core-v020-acceptance-20261025-10834dcb/consumers
DOCTOR_RECHECK_ROOT=/tmp/sillbeam-doctor-new-check DOCTOR_CONSUMER_PREFIX=sf \
  python3 docs/development/doctor-f01-f02-verification-2026-10-25/scripts/check-consumers.py
DOCTOR_RECHECK_ROOT=/tmp/sillbeam-doctor-new-check DOCTOR_CONSUMER_PREFIX=sf DOCTOR_PROFILE=php84 \
  python3 docs/development/doctor-f01-f02-verification-2026-10-25/scripts/check-consumers.py
```

PHP-8.4-Nachprüfung benötigt das bereits vorhandene Image `dwell-php-matrix:8.4`.
Sie läuft ohne Netzwerk/Pull, als Benutzer 1000, mit ausschließlich dem neuen
Testverzeichnis als Mount. Die beiden Consumer-Läufe müssen nacheinander laufen,
da das Skript zwischen Szenarien bewusst eigene Testkonfigurationen verändert.

## Vollständige Core- und CI-Prüfungen

| Lauf | Ergebnis | Beleg |
| --- | --- | --- |
| PHP 8.5.4 / Symfony FrameworkBundle 8.1.6, HttpFoundation 8.1.9, Root-Lock | 550 Tests / 6352 Assertions | [Suite](logs/final-tests.log) |
| PHP 8.5.4 / Symfony 7.4.21, disponibler CI-Kompatibilitätscheckout | 550 Tests / 6358 Assertions | [Suite](logs/final-sf74-php85-tests.log) |
| PHP 8.4.26 / dieselben 8.1-Dependencies | 550 Tests / 6352 Assertions | [Suite](logs/final-sf81-php84-tests.log) |
| PHP 8.4.26 / dieselben 7.4-Dependencies | 550 Tests / 6358 Assertions | [Suite](logs/final-sf74-php84-tests.log) |
| PHPStan Level 8, Root und 7.4-Kopie | Keine Fehler | [Root](logs/final-phpstan.log), [7.4](logs/final-sf74-phpstan.log) |
| Coding Style | 0 korrigierbare Dateien | [Prüfung](logs/final-style.log) |
| Deptrac | 0 Verstöße, 0 Warnungen, 0 Fehler; 261 uncovered bleiben ausgewiesen | [Prüfung](logs/final-architecture.log) |
| Recipe-CI ohne / mit Doctrine | Installation, beide Kernel und Deinstallation bestanden | [Ohne](logs/ci-flex-recipe-retry.log), [mit](logs/ci-flex-doctrine.log) |
| Consumer-CI, Symfony 8.1.9 / 7.4.21 | Je sechs Anwendungsszenarien, jeweils mit/ohne Doctrine und beiden Kernelvarianten bestanden | [8.1](logs/ci-consumer.log), [7.4](logs/ci-consumer-sf74.log) |
| Release-ZIP ohne Dev-Abhängigkeiten | Installation, dokumentiertes Hello, beide Kernel, kaltes/warmes Dev+Prod bestanden | [Prüfung](logs/ci-release-package.log) |
| Composer-Validierung / Plattform | Bestanden | [Validierung](logs/composer-validate.log), [Plattform](logs/platform.log) |

Die regulären Suiten enthalten erwartete 404-Testprotokolle; die abschließenden
PHPUnit-Ergebnisse weisen keine Fehler aus. Die 7.4-Abhängigkeiten wurden nach dem
vorhandenen CI-Verfahren in einer Kopie mit `tools/prepare-compatibility.php` und
`composer update --with-all-dependencies --no-scripts --no-plugins` aufgelöst;
[Resolver-Protokoll](logs/matrix-sf74-resolve.log). Die PHP-8.4-Läufe verwenden diese
unter PHP 8.5 aufgelösten Dependencies. Das ist keine zusätzliche Composer-
Versionsmatrix unter PHP 8.4 und kein Lauf der externen GitHub-Actions-Infrastruktur.

Ausgeführte zentrale Befehle:

```bash
composer test
composer analyse
composer architecture
composer cs:check
composer validate --strict
composer check-platform-reqs
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
composer test:consumer
SILLBEAM_SYMFONY_VERSION='7.4.*' composer test:consumer
composer test:release-package
```

### Grenzen der Testumgebung und verbleibende Einschränkungen

Ein erster Offline-Installationsversuch scheiterte an fehlenden Download-Caches;
der erste nicht freigeschaltete Recipe-Lauf scheiterte an der lokalen Socket-Sperre.
Die automatische Freigabeprüfung für den ersten Download-Aufruf lief ins Zeitlimit.
Nach Freigabe des erneuten CI-Aufrufs konnten Abhängigkeiten geladen und die
lokalen Endpunkt-/CI-Prüfungen erfolgreich wiederholt werden. Zwischenzeitliche
Consumer-Kopien mit übernommenen Dependencies sind nicht Grundlage der finalen
56-Szenarien-Aussage. Der finale Neuaufbau mit leerem `vendor` ist protokolliert.

Unbekannte PHP/XML-Konfiguration, nicht aufgelöste Imports/Parameter, Routing-
Overlays, eigene Kernel-Hooks und mehrdeutige Bootstraps bleiben manuell zu prüfen.
Doctor ist weder PHPStan noch Architekturprüfung noch allgemeiner Symfony-
Konfigurationscheck. F03, öffentliche Recipe-Zulassung und Paketpublikation sind
nicht Gegenstand der Korrekturen. Keine Pushes, Merges, Tags oder Veröffentlichungen.
