# B1 Follow-up – Sillbeam Core v0.3.0, 30.10.2026

**Empfehlung: GO für den exakt dokumentierten lokalen Prüfstand. Finale Flex-Abnahme: 20/20 erfolgreich.** Alle Ergebnisse dieses Berichts wurden neu ausgeführt; der ursprüngliche NO-GO-Bericht bleibt unverändert.

## Exakt geprüfter Stand und Änderungen

Branch `dev-v0.3.x`, HEAD `ae7518d00662a1b2df444641b8ed8b934a82779f` plus der lokale, nicht committete [Korrektur-Diff](correction.diff). [Provenienz](provenance.json) und [SHA-256-Manifest aller versionierten Dateien](source-sha256.json) identifizieren den geprüften Arbeitsstand. Es gibt keinen neuen Commit, und diese Abnahme zertifiziert keinen hypothetischen späteren Commit.

Einzige geänderte versionierte Datei: `tools/test-flex-recipe.php`. Neu angelegt: dieses Nachweisverzeichnis mit Bericht, Diff, Hashmanifest, Logs, Befehls-/Exit-Code-Datensätzen, Abhängigkeitsinventaren und QA-Hilfsskripten. Keine Änderungen an Produktionscode, CI/Workflows, Architekturregeln, JSON-Schemas, öffentlichen APIs, SVG-Renderer oder Runtime-Abhängigkeiten. Keine Commits, Pushes, Merges, Tags oder Releases.

## B1 – Ursache, Reproduktion und Korrektur

Der echte Flex-Test wurde vor der Korrektur erneut ausgeführt: [Red-Log](attempt-1/logs/red-flex.log), [Befehl und Exit 255](attempt-1/results/red-flex.json). Die gültige kompakte Ausgabe `Module: hello — Active` wurde von den veralteten Erwartungen `ID: hello` / `State: active` abgelehnt. Ein vorangegangener Sandbox-Versuch scheiterte an der gesperrten lokalen Socket-Erstellung ([Umgebungslog](attempt-1/logs/red-host.log)); der B1-Nachweis stammt aus dem anschließend freigegebenen vollständigen Lauf.

`InspectModuleCommand` und die 27 Integrationstests in `InspectModuleTest` wurden geprüft. Der bestehende Vertrag bleibt unverändert: Standardausgabe `Module: <id> — <State>`; `ID`, `State` und `Active` erscheinen in der ausführlichen Ausgabe. Die korrigierte Smoke-Prüfung verlangt Exit 0 und die kompakte Identität samt fachlichem Zustand. Zusätzlich wird die ausführliche Ausgabe mit `VERBOSITY_VERBOSE` einschließlich `ID`, `State`, `Active` und Exit 0 geprüft.

Für jede Installationsvariante gilt: `hello` immer aktiv; `off` im Zero-Config-Boot aktiv, nach explizitem `off.enabled: false` deaktiviert. Bestehende Registry-, Servicegrenzen-, Mermaid-, HTTP-, Doctrine- und Deinstallationsprüfungen bleiben erhalten. Keine Assertion entfernt, kein Test übersprungen, kein Fehler unterdrückt. Die übrigen Assertions und Befehlsaufrufe wurden geprüft; keine weitere veraltete Ausgabeerwartung bestätigt. Erfolgreiche Smoke-Boots protokollieren jetzt Kernel, Umgebung, Konfiguration, Zustände und die tatsächlich bestandenen Folgeschritte.

## B2 – zusätzlich entdeckter und bestätigter Cache-Fehler im Testaufbau

Die erste komplette Abnahmerunde mit ausschließlich der B1-Korrektur ergab **19/20**, nicht 20/20. Alle Originalnachweise liegen separat unter [attempt-1](attempt-1/summary.json); ihr [Quellmanifest](attempt-1/source-sha256.json) und [Diff](attempt-1/correction.diff) identifizieren diesen Zwischenstand.

[PHP 8.5 / Symfony 8.1, Standardlauf](attempt-1/logs/php85-sf81-flex-default.log) scheiterte vor der Inspektion an `Missing adapter needs instructions` (Exit 255). Doctor selbst lieferte Exit 1, weshalb die Exit-Prüfung den Fehler nicht allein unterscheiden konnte. Ein separater [Diagnoselauf](attempt-1/logs/php85-sf81-flex-default-diagnostic.log) bestand zunächst. Von drei weiteren separat protokollierten Wiederholungen bestanden [1](attempt-1/logs/php85-sf81-doctor-probe-1.log) und [2](attempt-1/logs/php85-sf81-doctor-probe-2.log); [3](attempt-1/logs/php85-sf81-doctor-probe-3.log) reproduzierte die tatsächliche Meldung: `There are no commands defined in the "sillbeam" namespace.`

Die Cache-Untersuchung bestand in den ersten [zwei](attempt-1/logs/php85-sf81-cache-probe-2.log) Läufen und reproduzierte im [dritten Lauf](attempt-1/logs/php85-sf81-cache-probe-3.log) den Fehler am selben Consumer. Bundle-Datei und alter Container-Cache hatten exakt denselben Sekunden-Zeitstempel **1793373766**. Ausschließlich nach Entfernen dieses Caches lieferte dieselbe Anwendung die korrekte Doctor-Ausgabe: `4 verified · 2 action required · 0 not verified` und `Console adapter: action required`. Der Diagnoselauf blieb absichtlich fehlgeschlagen (Exit 255); seine ursprüngliche Assertion wurde nicht durch den erfolgreichen Neuaufbau ersetzt.

Bestätigte Ursache: Der vor der Installation ausgeführte native `about`-Boot erzeugt einen Container ohne Core. Die Testfixture hat leere Cache-Clear-Auto-Skripte. Symfony `FileResource::isFresh()` akzeptiert `filemtime <= cache timestamp`; Bundle-Änderungen innerhalb derselben Sekunde invalidieren den alten Container deshalb nicht zuverlässig. Die Korrektur entfernt den veralteten Fixture-Cache explizit **nach** den Installations-/Dateierhaltungsassertions und **vor** dem Doctor-Onboarding. Alle nachfolgenden Prüfungen einschließlich kalter/warmer Caches laufen unverändert. Der Assertion-Fehler enthält künftig auch die tatsächliche Doctor-Ausgabe.

Die [Diagnosekopie](helpers/cache-diagnostic.php.txt) protokolliert Zeitstempel und Vorher-/Nachher-Ausgabe; sie gehört nur zu den QA-Nachweisen. Es wurde keine spekulative Runtime-Korrektur vorgenommen. Nach dieser bestätigten Testaufbau-Korrektur wurde die gesamte folgende Abnahme auf dem endgültigen Stand neu gestartet.

## Verfahren und Umgebungen

Vier separate Kopien der versionierten Arbeitsdateien unter `/tmp/sillbeam-b1-final-matrix/`, PHP **8.4.26 / 8.5.11**, Symfony FrameworkBundle **v7.4.22 / v8.1.10**. Images `sillbeam-frame-verify:php84` und `:php85`, Speicherlimit 512M. `tools/prepare-compatibility.php` und `composer update --with-all-dependencies --no-scripts --no-interaction --prefer-dist` wählen ausschließlich in Wegwerfkopien die jeweilige Symfony-Linie. Root-Manifest und Root-Lock bleiben unverändert. Pro Kombination liegen `*-dependencies.json` sowie Hashes der vorbereiteten Composer-Dateien in [summary.json](summary.json) vor.

Die Images enthalten zunächst kein GnuPG (Probes Exit 127). Die bereits bekannte QA-Voraussetzung wurde nur in temporären Containern installiert; erfolgreiche Installation und Versionsprobes sind separat protokolliert. Das ist keine Produktabhängigkeit. Composer-Warnungen über HTTP betreffen den beabsichtigten lokalen Flex-Endpunkt auf 127.0.0.1.

[Matrix-Runner](helpers/matrix.py) und [Befehls-Runner](helpers/runner.py) speichern für jeden Befehl Zeitstempel, Laufzeit, tatsächlichen Exit-Code und vollständige stdout/stderr-Logs. Container-Datensätze enthalten `SILLBEAM_SYMFONY_VERSION`; variantenspezifische Umgebungsvariablen stehen in den jeweiligen `docker exec -e`-Befehlen.

## Finale Ergebnisse aller 20 Flex-Läufe

| PHP / Symfony | Standard | Fresh | Existing Route | Doctrine | Fresh + Doctrine |
|---|---|---|---|---|---|
| php84-sf74 | [PASS (0)](logs/php84-sf74-flex-default.log) | [PASS (0)](logs/php84-sf74-flex-fresh.log) | [PASS (0)](logs/php84-sf74-flex-existing-route.log) | [PASS (0)](logs/php84-sf74-flex-doctrine.log) | [PASS (0)](logs/php84-sf74-flex-fresh-doctrine.log) |
| php84-sf81 | [PASS (0)](logs/php84-sf81-flex-default.log) | [PASS (0)](logs/php84-sf81-flex-fresh.log) | [PASS (0)](logs/php84-sf81-flex-existing-route.log) | [PASS (0)](logs/php84-sf81-flex-doctrine.log) | [PASS (0)](logs/php84-sf81-flex-fresh-doctrine.log) |
| php85-sf74 | [PASS (0)](logs/php85-sf74-flex-default.log) | [PASS (0)](logs/php85-sf74-flex-fresh.log) | [PASS (0)](logs/php85-sf74-flex-existing-route.log) | [PASS (0)](logs/php85-sf74-flex-doctrine.log) | [PASS (0)](logs/php85-sf74-flex-fresh-doctrine.log) |
| php85-sf81 | [PASS (0)](logs/php85-sf81-flex-default.log) | [PASS (0)](logs/php85-sf81-flex-fresh.log) | [PASS (0)](logs/php85-sf81-flex-existing-route.log) | [PASS (0)](logs/php85-sf81-flex-doctrine.log) | [PASS (0)](logs/php85-sf81-flex-fresh-doctrine.log) |

Alle Zellen verlinken vollständige Logs; der gleichnamige JSON-Datensatz unter `results/` enthält den ausgeführten Befehl und Exit-Code. Die fünf Varianten entsprechen exakt `composer test:flex-recipe` mit jeweils keinem Zusatz, `SILLBEAM_TEST_FRESH=1`, `SILLBEAM_TEST_EXISTING_ROUTE=1`, `SILLBEAM_TEST_DOCTRINE=1` bzw. `SILLBEAM_TEST_FRESH=1 SILLBEAM_TEST_DOCTRINE=1`.

Nachweis der zuvor nicht erreichten Schritte: **180 Smoke-Boots**, also ein Zero-Config-Boot und acht konfigurierte Boots pro erfolgreichem Lauf (beide Kernel × dev/prod × kalt/warm). Das sind **720 Modul-Inspektionen** für `hello` und `off`, jeweils kompakt und ausführlich, einschließlich Exit-Code und erwartetem Zustand. Jeder Boot prüft Mermaid-Export, HTTP 200 und Antwortinhalt; die acht Doctrine-Varianten belegen **72 Mapping-Prüfungen**. Jeder erfolgreiche Lauf reicht bis zur Deinstallation und zum anschließenden nativen Symfony-Boot.

[verify-evidence.py](helpers/verify-evidence.py) wertet Exit-Codes und alle erwarteten Boot-Kombinationen automatisch aus und prüft die abschließende Deinstallations-Erfolgsmeldung. Ergebnis: **Exit 0, keine offenen Befunde**. Erfolgreiche Wiederholungen aus der Diagnose wurden nicht in diese finale 20er-Matrix hineingerechnet.

## Finale Regressionsergebnisse

| Kombination | PHPUnit | inspect:module gezielt | PHPStan 8 | Deptrac | Coding Standards | Consumer | Composer-ZIP | Composer validate --strict / Plattform |
|---|---|---|---|---|---|---|---|---|
| php84-sf74 | [OK (635 tests, 8363 assertions)](logs/php84-sf74-phpunit.log) | [OK (27 tests, 261 assertions)](logs/php84-sf74-inspect-module.log) | [Exit 0](logs/php84-sf74-analyse.log) | [Exit 0](logs/php84-sf74-architecture.log) | [Exit 0](logs/php84-sf74-cs.log) | [Exit 0](logs/php84-sf74-consumer.log) | [Exit 0](logs/php84-sf74-release-package.log) | [Exit 0](logs/php84-sf74-validate.log) / [Exit 0](logs/php84-sf74-platform.log) |
| php84-sf81 | [OK (635 tests, 8223 assertions)](logs/php84-sf81-phpunit.log) | [OK (27 tests, 261 assertions)](logs/php84-sf81-inspect-module.log) | [Exit 0](logs/php84-sf81-analyse.log) | [Exit 0](logs/php84-sf81-architecture.log) | [Exit 0](logs/php84-sf81-cs.log) | [Exit 0](logs/php84-sf81-consumer.log) | [Exit 0](logs/php84-sf81-release-package.log) | [Exit 0](logs/php84-sf81-validate.log) / [Exit 0](logs/php84-sf81-platform.log) |
| php85-sf74 | [OK (635 tests, 8363 assertions)](logs/php85-sf74-phpunit.log) | [OK (27 tests, 261 assertions)](logs/php85-sf74-inspect-module.log) | [Exit 0](logs/php85-sf74-analyse.log) | [Exit 0](logs/php85-sf74-architecture.log) | [Exit 0](logs/php85-sf74-cs.log) | [Exit 0](logs/php85-sf74-consumer.log) | [Exit 0](logs/php85-sf74-release-package.log) | [Exit 0](logs/php85-sf74-validate.log) / [Exit 0](logs/php85-sf74-platform.log) |
| php85-sf81 | [OK (635 tests, 8223 assertions)](logs/php85-sf81-phpunit.log) | [OK (27 tests, 261 assertions)](logs/php85-sf81-inspect-module.log) | [Exit 0](logs/php85-sf81-analyse.log) | [Exit 0](logs/php85-sf81-architecture.log) | [Exit 0](logs/php85-sf81-cs.log) | [Exit 0](logs/php85-sf81-consumer.log) | [Exit 0](logs/php85-sf81-release-package.log) | [Exit 0](logs/php85-sf81-validate.log) / [Exit 0](logs/php85-sf81-platform.log) |

Die unveränderte gezielte Inspektionssuite umfasst kompakte, ausführliche und Quiet-Ausgabe, Aktivierungszustände, Projektkontexte, ungültige Eingaben und beide Console-Einstiegspunkte. Erwartete HTTP-404-Protokolle der vollständigen PHPUnit-Suite sind in den Logs enthalten; keine PHPUnit-Fehler.

Consumer: je Kombination zwölf Szenarien (sechs Anwendungstopologien mit/ohne Doctrine), jeweils beide Kernel. Release-Paket: echtes Composer-ZIP erzeugt und installiert, **130 Archivdateien, 77 bytegleiche Runtime-Dateien, 197 lokale Dokumentationslinks**, dokumentiertes Hello, beide Kernel, kalte/warme dev/prod-Boots, keine Doctrine-/Entwicklungsabhängigkeiten und Ausschluss von Entwicklungsverzeichnissen.

## Empfehlung und verbleibende Grenzen

**GO für den dokumentierten lokalen Korrekturstand:** 20/20 Flex-Läufe und alle geforderten Regressionstests bestehen. B1 und der zusätzlich bestätigte Fixture-Cache-Fehler sind behoben. Keine weiteren offenen Release-Blocker in diesem Prüfauftrag.

Die historischen NO-GO-Nachweise bleiben bytegleich erhalten; ebenso die gescheiterte erste Follow-up-Runde und ihre Diagnoseläufe. Der Hashvergleich bestätigt unveränderte versionierte Quelldateien seit dem finalen Snapshot sowie identische `src/`, `tools/` und `tests/` in allen vier finalen Matrixkopien. `git diff --check` besteht.

Diese Empfehlung ist keine Veröffentlichung und keine Commit-Abnahme: Alle Änderungen bleiben lokal und uncommittet. Ein später zur Veröffentlichung vorgesehener Commit muss diesem geprüften Stand entsprechen. Composer-Abhängigkeiten wurden für diese Läufe tatsächlich aufgelöst; zukünftige andere Versionen sind durch diesen Bericht nicht vorab zertifiziert.
