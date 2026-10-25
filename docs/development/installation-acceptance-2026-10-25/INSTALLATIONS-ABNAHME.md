# Installations-Abnahme Sillbeam Core v0.2.0

**Datum:** 25.10.2026, Europe/Berlin
**Geprüfter Stand:** `d01099c2d9511b1c372331b75f8a46ffa19ed07b` (`dev-v0.2.x`)
**Umgebung:** `sillbeam-core-v020-acceptance-20261025-10834dcb`
**Urteil:** **Kein uneingeschränktes Release-Go.** Installation, Architekturdiagnose,
Reports, Modul-Laufzeit, Produktions-HTTP-Kontrollen und Recipe-Deinstallation
funktionieren lokal. Die Installationsdiagnose erfüllt den gewünschten praktischen
Nachweis in einer üblichen Symfony-Anwendung jedoch nur teilweise: Ein bekannter
Service-Import-Konflikt wird durch eine sachfremde Standard-Routen-Konfiguration
verdeckt; der normale Runtime-Bootstrap bleibt auch nach korrektem Adapterwechsel
ungeprüft. Diese Punkte benötigen eine bewusste Freigabeentscheidung oder einen
separaten Korrekturauftrag. Öffentliche Paket-/Recipe-Verfügbarkeit fehlt zusätzlich.

**Keine Core-Nachbesserung vorgenommen.** Es wurden ausschließlich neue
Testanwendungen, Runner, Probes und dieser Bericht unter dem neuen Testverzeichnis
erstellt. Keine Pushes, Merges, Tags, Recipe-Publikationen oder externen PRs.

> Dokumentationsarchiv: übernommen nach Abschluss der Abnahme. Aussagen zum
> unveränderten Original beziehen sich auf den Prüfzeitpunkt. Dieses Archiv enthält
> Bericht, zitierte Belege, ergänzende Ergebnisse und Runner-Quellen; sämtliche
> Consumer und vollständigen Rohdaten bleiben im ursprünglichen Testverzeichnis.

## 1. Isolation und Nachweise

Die vorhandenen Verzeichnisse `sillbeam-core-v020-e2e` und
`sillbeam-core-v020-diagnostics-e2e` wurden nicht verwendet oder überschrieben.
Eine neue Umgebung mit eindeutigem Suffix wurde exklusiv angelegt.

- `source/`: Kopie der 412 getrackten Originaldateien sowie der lokalen
  Entwicklungsabhängigkeiten; hier lief die bestehende Testsuite.
- `package/`: separate Laufzeit-Paketkopie für Composer-Path-Repositories.
  Composer kopiert sie mit `symlink: false` in die Consumer.
- `consumers/`: frische Symfony-Skeletons für A, B und C. D läuft auf A.
- `matrix/`: getrennte, reparierte Consumer-Kopien für PHP 8.4.
- `controls/`: zusätzliche Doctor- und Doctrine-Kontrollprojekte.
- `logs/`, `results/`, `evidence/`: originale Ausgaben, rohe Prüfergebnisse,
  Dateivergleiche und Versionsinformationen.
- `commands.jsonl`: konkrete ausgeführte Befehle mit Arbeitsverzeichnis,
  Exit-Code, Laufzeit und Logpfad; keine nachträgliche Zusammenfassung anstelle
  der ursprünglichen Ausgaben.

[Originalprüfung](evidence/original-source-check.json): alle **412** ursprünglichen
getrackten Dateien unverändert, ursprüngliches Git-Arbeitsverzeichnis sauber,
HEAD unverändert. [Paketvergleich](evidence/installed-package-fingerprints.json):
zehn noch installierte Paketkopien stimmen in sämtlichen Core-PHP-Dateien mit dem
Original überein. Der Paketname wird nur für diesen lokalen Test als `0.2.0`
versioniert; es wurde kein Release-Tag erzeugt.

## 2. Versionen und Testumfang

| PHP | Symfony FrameworkBundle | Prüfung | Ergebnis |
| --- | --- | --- | --- |
| 8.5.4, Host | 7.4.21 | Frische Installation A, manuelle Installation B, bestehende App C, Fehlerfall D | Laufzeit/Architektur/Reports bestanden; Doctor-Einschränkungen siehe Abschnitt 4 |
| 8.5.4, Host | 8.1.9 | dieselben Szenarien | dieselben Einschränkungen |
| 8.4.26, lokales Docker-Image | 7.4.21 | Doctor-Dateivergleich, Text/JSON, Markdown, Hilfe, Modul-HTTP, Architekturfehler und Reparatur | alle elf Matrixprüfungen bestanden; Doctor bleibt ausdrücklich unvollständig |
| 8.4.26, lokales Docker-Image | 8.1.9 | dieselben Prüfungen | alle elf Matrixprüfungen bestanden |
| 8.5.4 und 8.4.26 | 7.4.21, mit Doctrine | Modul-Mapping, Architektur/Report, SQLite-Schema und Persistenz-Roundtrip | bestanden |

Composer **2.9.5**, Symfony Flex **2.11.0**; Skeletons **7.4.99** und **8.1.99**.
PHP 8.4 nutzt das vorhandene Image `dwell-php-matrix:8.4` mit `--pull=never`,
`--network=none`, unprivilegierter Benutzer-ID und ausschließlich diesem
Testverzeichnis als Mount. Keine Images geladen oder bestehende Container geändert.
Die PHP-8.4-Consumer verwenden dieselben auf PHP 8.5 aufgelösten, PHP-8.4-kompatiblen
Abhängigkeiten; eine unabhängige Composer-Auflösung unter PHP 8.4 ist damit nicht
behauptet. Symfony 8.0 wurde nicht zusätzlich installiert; die vorhandene CI
kennzeichnet diese Linie als nicht mehr gepflegt und durch Advisories blockiert.

Die bestehende vollständige Suite lief ebenfalls **in der Kopie**, nicht im Core:
**542 Tests, 6050 Assertions, erfolgreich**, PHP 8.5.4 mit den kopierten
Root-Lockfile-Abhängigkeiten (Symfony 8.1.6). Das ist getrennt von den frisch
aufgelösten Consumer-Versionen zu lesen. Siehe [Suite-Log](logs/regression-full-suite.log).

## 3. Szenarien und Ergebnisse

### A. Frische Symfony-Anwendung mit lokaler Flex-Recipe

Ausgeführt auf beiden Symfony-Linien:

```bash
composer create-project symfony/skeleton:7.4.* <neuer-consumer> \
    --no-install --no-scripts --no-plugins --no-interaction --prefer-dist
# 8.1.* entsprechend im zweiten Profil.
# Danach im Consumer: Path-Repository zu package/, lokale Version 0.2.0,
# symlink:false, lokale Recipe plus flex://defaults und allow-contrib:true.
composer install --no-interaction --prefer-dist --no-progress
php bin/console sillbeam:doctor --no-ansi
# Manueller Austausch ausschließlich des Application-Imports in bin/console.
php bin/console sillbeam:doctor --no-ansi
php bin/console sillbeam:make:module Billing --no-ansi
php bin/console sillbeam:architecture --no-ansi
php bin/console sillbeam:architecture --format=json
php bin/console sillbeam:report --output=acceptance-report.md --no-ansi
php bin/console cache:clear --env=prod --no-debug --no-ansi
```

Die üblichen Symfony-Recipes wurden tatsächlich installiert; es wurde kein
handgebautes Minimalprojekt als „frisches Skeleton“ ausgegeben. Der lokale
Recipe-Endpunkt wurde aus den unveränderten Recipe-Quellen aufgebaut.

**Bestanden:** zentrale Bundle-Registrierung, installierter `type: sillbeam`-Import,
`0.2` in `symfony.lock`, ausschließlich die Routendatei als Recipe-Datei,
keine leere `config/packages/sillbeam.yaml`, tatsächliche Ausgabe sämtlicher
Post-install-Zeilen, erfolgreiche Modul-Erzeugung und Architektur-/Report-Befehle.
`/billing` antwortet nach regulärem Produktionsbuild mit **200 / `module-ok`**.
Nach `billing.enabled: false` und Neubau ist die Modulroute **404**.

**Einschränkung:** Doctor liefert vor und nach dem dokumentierten Importwechsel
Exit **2**; der korrekte Runtime-Adapter wird nicht als verifiziert erkannt.
Bootstrap und Kernel wurden beim Importwechsel erhalten, nicht durch eine
andersartige zweite Console-Anwendung ersetzt.

[Installation 7.4](logs/host-7.4-r2-sf74-A-r2-composer-install.log) ·
[Installation 8.1](logs/host-8.1-r2-sf81-A-r2-composer-install.log) ·
[Doctor nach Importwechsel](logs/host-8.1-r2-A-after-import-only-adapter.log)

### B. Ohne veröffentlichte Sillbeam-Recipe

Zuerst wurde ein natives Symfony-Skeleton mit den normalen öffentlichen Recipes
installiert. Danach:

```bash
composer require websign-dev/sillbeam-core:0.2.0 --no-interaction --no-progress
```

Das Package kam ausschließlich aus dem lokalen Path-Repository. Die Sillbeam-
Routendatei fehlte wie erwartet. Flex kann bei einem `symfony-bundle` auch ohne
veröffentlichte Recipe die Bundle-Registrierung selbst vornehmen. Die manuelle
Einrichtung bewahrte diesen Eintrag beziehungsweise ergänzte ihn nur bei Bedarf.
Der Route-Import wurde exakt nach Dokumentation erstellt. Es folgten der manuelle
Application-Import, passende Service-Ausschlüsse und die oben genannten
Architektur-/JSON-/Markdown-/Hilfe-Befehle.

**Bestanden:** manuelle Integration, gültige Architektur und funktionsfähiger
Markdown-Report, jeweils Exit **0**. **Nicht vollständig verifiziert:** Doctor
bleibt wegen der Standard-Konfigurationsformen bei Exit **2**. Er behauptet dabei
keine erfolgreiche Vollprüfung.

[Manuelle Architekturprüfung](logs/host-7.4-r2-B-json.log) ·
[Manueller Report](reports/manual-acceptance-report.md)

### C. Bestehende Symfony-Anwendung

Vor Core-Installation existierten ein nativer `/legacy`-Controller, eine eigene
Service-Konfiguration mit Kommentar und Parameter sowie ein breiter `App\`-
Import. `services.yaml` und `bin/console` wurden vor und nach der Installation
bytegleich verglichen: **unverändert**.

Der Import schloss zunächst nur `Kernel.php` aus. Damit waren die vorgesehenen
Modul-/Project-Bereiche offensichtlich nicht ausgenommen. Die Standardausführung
von Doctor erkannte den konkreten Konflikt im normalen Dev-Skeleton **nicht als
Handlungsbedarf**, sondern gab wegen anderer Konfiguration allgemein „not verified“
aus. Auch der fehlende Runtime-Adapter wurde lediglich zur manuellen Prüfung
gestellt. Nach rein manueller Korrektur von Import und Bereichsausschlüssen lief
Doctor erneut; Exit blieb **2**. Details und Kontrollbeweis folgen in Abschnitt 4.

**Produktions-HTTP:** `/legacy` hatte vor Core und nach Integration denselben
Status **200**, Inhalt **`legacy-ok`** und Content-Type **`text/plain; charset=UTF-8`**.
Zusätzlich wurden echte HTTP-Anfragen über den PHP-Webserver mit `APP_ENV=prod`,
`APP_DEBUG=0` und `variables_order=EGPCS` gegen die native sowie die integrierte
Anwendung ausgeführt. Alle **18** abschließenden HTTP-Kontrollen bestanden:
identische native Antwort, generische 404 ohne Stacktrace/Quellpfad und keine
Änderung an Source oder Konfiguration während der Anfragen.

[HTTP-Ergebnisse](results/http-production-r2.json) ·
[echte HTTP-Antwort mit Core](evidence/actual-http-http-production-r2-8.1-core-installed.json) ·
[Service-Konflikt im Skeleton](logs/host-7.4-r2-C-missing-adapter-and-overlap.log)

### D. Fehlerhafte Architektur

Im erstellten Billing-Modul wurde allein im Consumer vorübergehend deklariert:

```php
#[SillbeamModule(dependencies: [\App\Module\Missing\MissingModule::class])]
```

**Bestanden auf allen vier PHP-/Symfony-Kombinationen:**

- `sillbeam:architecture --format=json`: Exit **1**, Regel **`dependency.unknown`**.
- Containerunabhängiger Weg über den vorhandenen Adapter bleibt verfügbar.
- Doctor lässt sich ausführen und meldet ausdrücklich ungeprüfte Integration;
  sämtliche Dateivergleiche bei diesem Adapter-Aufruf bleiben unverändert.
- Auf den Host-Profilen erzeugt `sillbeam:report --output=invalid-report.md`
  trotz Verstoß den Bericht und liefert korrekt Exit **1**.
- Nach Rücknahme der Consumer-Deklaration: gültiges JSON, keine Findings, Exit **0**.

Der Core, seine Architekturregeln und die Diagnostic Engine wurden nicht geändert.

[Fehler-JSON](logs/host-8.1-r2-D-invalid-architecture.log) ·
[Fehlerbericht](reports/invalid-architecture-report.md) ·
[Reparatur](logs/host-8.1-r2-D-repaired.log) ·
[Doctor-Dateivergleich bei ungültiger Architektur](evidence/host-8.1-r2-D-doctor-invalid-changes.json)

### Recipe-Deinstallation und manuelle Dateien

```bash
composer remove websign-dev/sillbeam-core --no-scripts --no-interaction --no-progress
```

**Bestanden auf beiden Linien:** Recipe-Routendatei, Bundle-Registrierung und
Lock-Eintrag wurden entfernt. Die manuell angelegten Core-Paketoptionen,
Service-Konfiguration, der manuell geänderte Console-Import und native Controller
blieben bytegleich erhalten. Die Paket-Deinstallation führt bewusst keine
Anwendungsreparatur durch. Anschließend wurden die manuelle Sillbeam-Konfiguration
außerhalb der Paketimports abgelegt und der Console-Import im Consumer auf Symfony
zurückgestellt; der native HTTP-Pfad funktionierte wieder.

Die Prüfung benutzt `--no-scripts`, weil manuell erstellte Konfiguration und der
Adapter nach Paketentfernung erwartungsgemäß noch Core referenzieren. Ein
ungeprüftes erfolgreiches Deinstallations-`cache:clear` wird nicht behauptet.

Die bereits dokumentierte Flex-Grenze gilt weiterhin: Ein vorhandener Inhalt am
Recipe-Zielnamen `config/routes/sillbeam.yaml` kann von Flex verwaltet und bei
Deinstallation entfernt werden. Das ist von der Erhaltung eigener Dateien unter
anderen Namen zu unterscheiden; hierfür wurde in diesem unabhängigen Durchlauf
kein zusätzlicher Kollisionsfall eingerichtet.

## 4. Konkrete Befunde zur Entscheidung — keine Änderungen am Core

### F01 — P2: Standard-Bootstrap wird auch nach korrektem Adapterwechsel nicht bestätigt

**Reproduktion:** frisches aktuelles Symfony-Skeleton; im bestehenden `bin/console`
nur `use Symfony\Bundle\FrameworkBundle\Console\Application;` durch
`use Sillbeam\Core\Console\Application;` ersetzen; `sillbeam:doctor` ausführen.

**Ist:** „Console adapter: not verified“, Exit **2**, obwohl Architektur und Reports
nachweislich containerunabhängig funktionieren. Das betrifft 7.4 und 8.1.
Beim Symfony-8.1-Standardkernel führt außerdem die reguläre private Methode
`getAllowedEnvs()` zur Einstufung als unbekannte Konfigurations-Lade-Hooks.

**Einordnung:** Das ist eine dokumentierte konservative Einschränkung, keine
falsche grüne Aussage. Praktisch kann der normale Installationsweg aber nicht
abschließend mit Doctor abgenommen werden. Auch der fehlende Adapter bekommt im
üblichen Runtime-Bootstrap keinen konkreten „action required“-Befund.

**Kontrolle:** Ein separater wörtlicher Bootstrap mit direkter Application-
Konstruktion/`run()` erkennt den fehlenden Adapter. Mit korrektem direkten Adapter,
literalen YAML-Imports und Symfony-7.4-Kernel liefert Doctor tatsächlich Exit **0**.
Dies ist ein Kontrollprojekt, **keine** empfohlene Ersetzung des regulären Bootstraps.

Belege: [Standardfall](logs/host-8.1-r2-BC-after-manual-correction.log),
[literaler Erfolg](logs/controls-literal-supported-success.log),
[fehlender Adapter, direkte Kontrolle](logs/host-7.4-r2-C-direct-entry-missing-adapter-control.log).

### F02 — P2: Standard-Fehlerrouten verdecken einen belegbaren Service-Konflikt

**Befund:** `config/routes/framework.yaml` enthält standardmäßig:

```yaml
when@dev:
    _errors:
        resource: '@FrameworkBundle/Resources/config/routing/errors.php'
        prefix: /_error
```

Schon dieses Routen-Overlay setzt im Inspector die allgemeine Unsicherheit für
Discovery und Services. Ein davon unabhängiger, vollständig literaler `App\`-
Service-Import ohne Modul-/Project-Ausschlüsse wird nicht mehr als Handlungsbedarf
angezeigt. Die Standardausgabe nennt dann auch nicht die konkrete Überschneidung.

**Isolierter Kontrollnachweis, identische Service-Konfiguration:**

| Variante | Ergebnis |
| --- | --- |
| Wörtlicher Bootstrap, literale Routen, breiter Import, ohne Fehlerrouten-Overlay | 5 verified, 1 action required; Exit **1**; konkrete Service-Datei und „can bypass activation“ |
| Dasselbe Projekt, nur die Standard-Fehlerrouten wieder hinzugefügt | Service imports **not verified**, Exit **2**, kein konkreter Konflikthinweis |

Reproduzierbar im erhaltenen Kontrollprojekt, vom Abnahmeverzeichnis aus:

```bash
cd /home/oeffe/PhpstormProjects/websign/test/sillbeam-core-v020-acceptance-20261025-10834dcb/controls/doctor-74
cp ../../evidence/doctor-control-broad-services.yaml config/services.yaml
php bin/console sillbeam:doctor --no-ansi
# Exit 2, Service imports: not verified.
mv config/routes/framework.yaml framework-error-routes.saved
php bin/console sillbeam:doctor --no-ansi
# Exit 1, Service imports: action required.
# Testzustand anschließend wiederherstellen:
mv framework-error-routes.saved config/routes/framework.yaml
cp ../../evidence/doctor-control-correct-services.yaml config/services.yaml
```

Belege: [belegter Konflikt](logs/controls-literal-known-overlap.log),
[derselbe Konflikt mit Overlay](logs/controls-same-overlap-with-native-routing-overlay.log).
Ursachenhinweis zur späteren Entscheidung: das gemeinsame `$unknown` in
`src/Console/Doctor/InstallationInspector.php`, insbesondere beim Verarbeiten
aktiver `when@...`-Abschnitte. Keine Änderung vorgenommen.

### F03 — Betriebsgrenze: Ohne Adapter kann der Symfony-Boot Cache-Dateien schreiben

Im regulären Symfony-Einstieg erzeugten einzelne Doctor-Aufrufe **137** bzw.
**219** Änderungen unter `var/cache/`. Außerhalb von `var/` wurden dabei keine
Änderungen festgestellt. Diese Effekte entstehen vor der Ausführung des Commands
beim Symfony-Boot und sind bereits in der CLI-Referenz dokumentiert.

In **allen** abschließenden Adapter-basierten Doctor-Dateivergleichen, einschließlich
ungültiger Architektur und PHP 8.4, blieben Inhalt, Dateiliste, Modus und mtime
unverändert. Insgesamt liegen **44** Rohvergleiche aus Erst- und Folgeläufen vor.
Eine uneingeschränkte Schreibfreiheitszusage für einen beliebigen
`php bin/console sillbeam:doctor`-Aufruf ohne bereits eingerichteten Adapter ist
somit nicht belegt. Das sollte bei Reihenfolge und Freigabe der Installationshinweise
bewusst berücksichtigt werden.

Belege: [regulärer Boot, Cache-Differenzen](evidence/host-8.1-r2-C-missing-adapter-and-overlap-changes.json),
[Adapter bei Architekturfehler, keine Differenz](evidence/host-8.1-r2-D-doctor-invalid-changes.json).

### Weitere Grenzen, keine zusätzlich behaupteten Core-Fehler

- Die dokumentierten Symfony-Brace-Ausschlüsse bleiben als „not verified“ erhalten.
  Das ist sicherer als eine vermutete Erfolgsaussage, führt aber auch bei korrekter
  Standardkonfiguration nicht zur Vollverifikation.
- Ein Probe-Root ohne existierendes Verzeichnis führte zu einer leeren, gültigen
  Architektur. Die bestehende Discovery überspringt nicht vorhandene Roots.
  Daraus wird hier kein Architekturfehler konstruiert; ein falscher Root-Pfad
  kann allerdings eine leere Entdeckung verursachen. Der Probe wurde im Consumer
  wieder entfernt. Die tatsächliche Discovery-Konfiguration bleibt manuell zu prüfen.
- Dieses Ergebnis ersetzt keine Produktions-FPM-/Proxy-/Lastprüfung. Echte
  HTTP-Anfragen liefen auf dem lokalen PHP-Webserver; zusätzliche Kernel-Probes
  liefen als CLI-Prozesse, jeweils mit bewusst aufgebautem Produktionscache.

## 5. Veröffentlichungsvoraussetzungen

Unabhängige lesende HTTP-Prüfungen am Abnahmetag:

| Voraussetzung | Ergebnis |
| --- | --- |
| Packagist-Metadaten `websign-dev/sillbeam-core` | **HTTP 404** |
| Contrib-Endpunkt `websign-dev.sillbeam-core.0.2.json` | **HTTP 404** |

Belege: [Packagist](evidence/public-packagist.json),
[Recipe-Endpunkt](evidence/public-contrib-recipe.json).
Dies sind **externe Release-Schritte**, keine lokalen Implementierungsfehler.
Die lokalen Tests ersetzen weder die Paketveröffentlichung noch die externe
Recipe-Prüfung/Freigabe. Ein v0.2.0-Tag allein aktiviert keine Recipe.
Contrib-Zulassung und Composer-Plugin-Freigabe waren in den Consumern explizit gesetzt.

## 6. Testvorbereitung, Nachprüfbarkeit und abschließende Entscheidung

Die ersten Runner-Ausführungen sind erhalten. Ein Python-Namenskonflikt im Runner
wurde vor Consumer-Erzeugung korrigiert. Die erste direkte HTTP-Probe lud `.env`
nicht und versuchte einen kalten Produktionscache während `handle()` aufzubauen.
Das traf die bestehende Build-only-Regel; es war kein neuer Core-Defekt. Danach
entstanden neue `-r2`-Consumer. Die erste Webserver-Kontrolle nutzte wegen fehlender
ENV-Übernahme den Dev-Modus, auch in der nativen App ohne Core. Der abschließende
Serverlauf setzte `variables_order=EGPCS`, Produktionsumgebung und Cache-Aufbau
korrekt; alle HTTP-Prüfungen bestanden. Diese Vorbereitungsfehler werden nicht
als Produktregressionen gewertet, ihre Rohprotokolle wurden nicht gelöscht.

Die im ursprünglichen Testverzeichnis ausführbaren Runner sind hier als Quellen archiviert: [acceptance.py](scripts/acceptance.py) und
[followup.py](scripts/followup.py); sie erkennen das ursprüngliche Abnahmeverzeichnis selbst. Nicht aus diesem Dokumentationsarchiv ausführen.
Neue Wiederholungen müssen einen **neuen** Rundennamen verwenden, zum Beispiel:

```bash
cd /home/oeffe/PhpstormProjects/websign/test/sillbeam-core-v020-acceptance-20261025-10834dcb
python3 acceptance.py host 7.4 r3
python3 acceptance.py host 8.1 r3
python3 acceptance.py matrix 7.4 r3
python3 acceptance.py matrix 8.1 r3
```

Die Host-Phase benötigt Composer-Netzwerk und einen lokalen HTTP-Port. Die Matrix
benötigt Zugriff auf das bereits vorhandene Docker-Image; sie arbeitet offline.
Kontroll-/Doctrine-Runner erzeugen absichtlich fest benannte, bereits vorhandene
Kontrollkopien und dürfen nicht blind erneut ausgeführt werden. Für gezielte
Reproduktionen die dokumentierten Einzelbefehle bzw. neue Kopien verwenden.

**Empfehlung:** Die Laufzeitinstallation, CLI-Architekturanalyse, Reports und
Recipe-Dateiverwaltung sind auf den geprüften Kombinationen belastbar bestätigt.
Für die vollständige Installationsabnahme ist Szenario C hinsichtlich der konkreten
Fehlererkennung und abschließenden Doctor-Verifikation **nur teilweise erfüllt**.
Vor einer uneingeschränkten Freigabe F01/F02 und die Betriebsgrenze F03 ausdrücklich
entscheiden: separate Nachbesserung oder bewusst akzeptierte, entsprechend
kommunizierte Einschränkung. Öffentliche Paket- und Recipe-Schritte danach
koordiniert ausführen. **Dieser Auftrag hat keine dieser Entscheidungen durch
Codeänderungen oder Veröffentlichungsaktionen vorweggenommen.**
