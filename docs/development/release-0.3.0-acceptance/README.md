# Release-Abnahme Sillbeam Core v0.3.0 — 2026-10-29

**Empfehlung: GO zur manuellen Release-Abnahme des geprüften lokalen Stands.**
Es gibt nach den dokumentierten Korrekturen keinen offenen funktionalen Blocker.
Dies ist **keine Release-Freigabe**. Veröffentlichung, Tag, Merge und Push erfolgen
nicht automatisch; die endgültige Entscheidung liegt ausschließlich beim Maintainer.
Ein GitHub-Actions-Lauf auf einem später freigegebenen Commit ist noch ausstehend.

## Geprüfter Stand und Änderungen

Ausgangspunkt ist `16b69f4` auf `dev-0.3.x`, ergänzt um die uncommitteten
Release-Vorbereitungen dieses Berichts. Die fünf Arbeitspakete sind enthalten:
`c0dc46e` PHPDoc/phpDocumentor, `bb6f423` Installation/Doctor,
`ac8118d` Doctor JSON, `1c4ecd8` Modul-Inspektion, `16b69f4` Mermaid-Export.
Die API-/Performance-Baseline ist der abgeschlossene 0.2.0-Quellstand `cf2f410`,
nicht ein in dieser Prüfung erstellter oder veröffentlichter Tag.

Aktualisiert wurden Composer-Aliases (`dev-main` und `dev-dev-0.3.x` →
`0.3.x-dev`), aktive App-/Skeleton-Manifeste und Test-App-Lockfile, Consumer-/ZIP-
Testversionen, README, CHANGELOG, Support-/Security-Status, CLI-/API-Referenzen,
[Release Notes](../../releases/0.3.0.md) und [Upgrade-Anleitung](../../migration/0.3.0.md).
Die Flex-Tests simulieren bereits 0.3.0; die kompatible Recipe unter `0.2/` bleibt
unverändert versioniert. Historische 0.2.0-Berichte/Release Notes/Migrationsanleitung
wurden nicht umgeschrieben. Kein fixer Runtime-Paketversionswert wurde eingeführt.

Die Testwerkzeuge prüfen jetzt auch Graph/Inspektion und Doctor-JSON im installierten
Paket. Der ZIP-Test übernimmt die ausgewählte Symfony-Linie einschließlich
transitiver Versionsgrenzen; er kann einen 7.4-Matrixjob nicht mehr still mit einem
8.1-Consumer ersetzen. Seine erfolgreiche Doctor-JSON-Prüfung bedeutet Schema-
Ausgabe, nicht automatisch eine verifizierte Installation. Die kontrollierten
Flex-Szenarien prüfen zusätzlich die echten Doctor-Zustände 0/1/2 und Reparaturen.

## API, Datenbasis und Architektur

- Reflection-Vergleich von **20 dokumentierten öffentlichen Klassen/Interfaces**
  mit identischen Abhängigkeiten: öffentliche/geschützte eigene Methoden,
  Parameter/Typen/Defaults, Rückgabetypen, Properties und Klassenmerkmale sind
  gegenüber `cf2f410` unverändert. Siehe
  [Baseline](public-api-0.2.0.json) / [Kandidat](public-api-0.3.0.json).
- `composer.json` enthält exakt dieselben Runtime-`require`-Constraints wie die
  Baseline. phpDocumentor ist ausschließlich Entwicklungswerkzeug. Nur der
  Shim ist als Composer-Plugin freigegeben; die Builds und Plattformprüfungen
  wurden unter beiden PHP-Versionen ausgeführt.
- Architekturtext, JSON, Markdown, Modul-Inspektion und Mermaid verwenden den
  vorhandenen `ArchitectureReport` / `ArchitectureAnalyzer`-Pfad. Neue interne
  Präsentationsmetadaten verändern nicht das öffentliche Architektur-JSON v1.
  Die bestehende Schema-Datei ist gegenüber 0.2.0 unverändert.
- Doctor JSON serialisiert dieselben `InstallationInspector`-Ergebnisse wie Text.
  Seine statische Prüfung bleibt eigenständig und konservativ; dynamische
  Konfiguration bleibt `not verified`. Es gibt keine zweite Installationserkennung.
- Modulidentität bleibt die Descriptor-Klasse. Kollisionen logischer IDs,
  Shared-/Private-Scope, Project-Auswahl, deklarierte/beobachtete/eingehende Kanten,
  deaktivierte/unzugewiesene Module, Zyklen und unvollständige Nachweise sind getestet.
- Native Symfony-Registrierung und Core-Adapter sind gemeinsam abgedeckt. Die
  exakten fünf Diagnosebefehle funktionieren im Adapter vor Container-Boot,
  einschließlich Architekturverstößen/defekter Service-Konfiguration. Text,
  JSON und Mermaid behalten ihre eigenen Format-/Stream-/Exit-Verträge.
- Deptrac meldet keine Verletzungen, Warnungen oder Fehler. Die bestehende
  Regelkonfiguration wurde nicht gelockert; kein Parser, keine neue Discovery,
  Abhängigkeitsanalyse oder Architekturregel wurde ergänzt.
- Der öffentliche HTML-Build enthält 20 Klassen-/Interface-Seiten. Die internen
  Inspector-/Graph-/Inspection-Implementierungen fehlen dort wie vorgesehen.
  HTML, Cache und lokale phpDocumentor-Konfiguration bleiben ignoriert.

Ein unabhängiges Read-only-Review bestätigte diese Grenzen und fand den unten
beschriebenen CI-Provisionierungsfehler. Weitere konkrete Quellregressionen wurden
nicht festgestellt. Das Review ersetzt nicht die ausgeführten Prüfungen.

## Tatsächlich ausgeführte Qualitätsprüfungen

Host: PHP **8.5.4**, Composer **2.9.5**, Root Symfony FrameworkBundle **8.1.6** /
HttpFoundation **8.1.9**. Die Matrix wurde aus Kopien des aktuellen Arbeitsstands
mit eigener Composer-Auflösung und Autoloading ausgeführt. Es wurden nicht bloß
Root-Vendor-Dateien für die Kompatibilitätsmatrix wiederverwendet.

| Prüfung | Ergebnis |
| --- | --- |
| `composer test` auf dem Host | **605 Tests / 8.095 Assertions**, erfolgreich |
| `composer analyse` | PHPStan Level 8, keine Fehler |
| `composer architecture` | 0 Verletzungen, 0 Warnungen, 0 Fehler |
| `composer cs:check` | Erfolgreich |
| `composer validate --strict` | Erfolgreich; auch Test-App |
| `composer check-platform-reqs` | Erfolgreich; auch Test-App und alle Matrixjobs |
| `composer audit --locked` | Keine gemeldeten Sicherheits-Advisories |
| `composer audit --locked --working-dir=apps/test-app/app` | Keine gemeldeten Sicherheits-Advisories |
| `composer docs:build` | Host sowie alle vier Matrixkombinationen erfolgreich nach Provisionierungskorrektur |
| Öffentliche Signaturen / Runtime-Requirements | Identisch zur Baseline |
| YAML-/CI-Konfiguration | Aktueller Branch, vier Kombinationen, Dokumentations-Provisionierung geprüft |

Nach den Release-Dokumentationsänderungen bestanden außerdem erneut der echte
ZIP-/No-Dev-Test auf dem Host, `composer docs:build`, `composer cs:check` und
`composer validate --strict`. Die vier Matrix-ZIP-Läufe sind damit um einen
fünften abschließenden ZIP-Lauf ergänzt.

### PHP-/Symfony-Matrix

Composer 2.9.5 wurde in allen vier Jobs verwendet. Die Images enthalten PHP
8.4.26 bzw. 8.5.11; die genauen Image-IDs stehen in [environment.json](environment.json).
FrameworkBundle-Versionen: **7.4.22** / **8.1.10**; DoctrineBundle **3.3.4**, ORM **3.7.5**,
phpDocumentor-Shim **3.10.0**. Alle aufgelösten Versionen stehen in
[matrix-versions.json](matrix-versions.json).

| PHP | Symfony | PHPUnit | PHPStan¹ / Deptrac / Stil / Docs² / Validierung / Audit / Plattform |
| --- | --- | --- | --- |
| 8.4.26 | 7.4.22 | 605 Tests / 8.084 Assertions | Erfolgreich |
| 8.4.26 | 8.1.10 | 605 Tests / 7.944 Assertions | Erfolgreich |
| 8.5.11 | 7.4.22 | 605 Tests / 8.084 Assertions | Erfolgreich |
| 8.5.11 | 8.1.10 | 605 Tests / 7.944 Assertions | Erfolgreich |

¹ Der Erstversuch hatte das Image-Standardlimit 128 MiB und scheiterte. Die
Wiederholung mit dem tatsächlichen CI-Limit **512 MiB** bestand in allen Jobs.
² Die Erstinstallation mit `--no-plugins` ließ das phpdoc-Binary aus. Die erlaubte
Shim-Provisionierung und erneuten Builds bestanden in allen Jobs. Ursprüngliche
Fehlerlogs bleiben erhalten. [matrix-final-gates.json](matrix-final-gates.json)
prüft explizit alle finalen bzw. wiederholten Komponenten; es überschreibt keine
fehlgeschlagenen Erstversuche.

### Consumer, Flex und reales Release-ZIP

In **jeder** der vier Kombinationen wurden ausgeführt:

```bash
composer test:consumer
composer test:flex-recipe
SILLBEAM_TEST_FRESH=1 composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
SILLBEAM_TEST_FRESH=1 SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
SILLBEAM_TEST_EXISTING_ROUTE=1 composer test:flex-recipe
composer test:release-package
```

Alle bestanden. Das sind **48 unabhängige Consumer-Installationen** (6 Layouts ×
mit/ohne Doctrine × 4 Kombinationen), jeweils mit beiden Kernelvarianten, sowie
**20 Flex-Läufe** und **4 ZIP-Läufe**. Flex/ZIP prüfen kalte und warme dev/prod-
Starts mit beiden Kerneln; Flex außerdem kaputte Installation, bewusste manuelle
Korrektur, Doctor-JSON/Text, Nichtüberschreiben bestehender Dateien und Entfernung.
Die bestehende Route-Zieldatei unterliegt beim Entfernen weiterhin Flex-Ownership;
diese bekannte Grenze ist dokumentiert und wird nicht als neue Core-Garantie ausgegeben.

Die ZIP-Tests installieren das echte Composer-Archiv ohne Core-Dev-Abhängigkeiten
und ohne Doctrine, führen die dokumentierten Hello-Beispiele aus und prüfen
Runtime-Service-/Routing-Verhalten, Entwicklungsverzeichnis-Ausschlüsse sowie die
neuen Diagnosebefehle. Consumer prüfen zusätzlich ORM-Mappings und Persistenz.
Simulierte 0.3.0-Metadaten sind kein öffentlicher Tag und keine Packagist-Publikation.

## Produktions-HTTP und Performance

`ValidationLifecycleTest`, `ProductionCliTest` und `BenchmarkTest` liefen in der
vollständigen Suite auf dem Host und in allen vier Matrixkombinationen. Die
Lifecycle-Probes testen in eigenen Prozessen insbesondere:

- kaltes non-debug Produktions-HTTP → generische 503, ohne Analyzer/Parser;
- vorgebautes Produktions-HTTP → 200 aus kompiliertem Zustand, ohne Analyzer/Parser;
- nachträglich ungültige Quellen ändern warmes HTTP nicht in eine Neuanalyse;
- CLI-Warmup/Cache-Clear lehnen denselben ungültigen neuen Build weiterhin ab.

Die vorhandenen Produktionsguards wurden nicht verändert. Die neuen Console-
Services werden nicht zu einer HTTP-Analyse. Die PHPDoc-Änderungen führen keine
zusätzliche Runtime-Arbeit ein; neue Report-Metadaten werden im bestehenden
Build-/Diagnosepfad erzeugt, nicht pro Produktionsanfrage.

### Vollständiger gepaarter Benchmark

Ausgeführt wurde der vorhandene `tools/benchmark-architecture.php` ohne Smoke-
Abkürzung auf Baseline und Kandidat: **10/50/100 Module**, jeweils **200/1.000/2.000
Services**, dev/prod, native Symfony-Kontrolle und Sillbeam, drei alternierende
Wiederholungen, je **150 warme HTTP-Samples**. Beide Läufe haben 13 valide JSONL-
Zeilen (Metadaten + 12 Vergleichsfälle), identische Responses/Route-Zahlen und
identische Root-Abhängigkeiten, PHP 8.5.4 / Symfony 8.1.6. Für diese kontrollierte
Versions-Gegenüberstellung wurden Vendor-Dateien kopiert; das ist ausdrücklich
etwas anderes als die unabhängig aufgelöste Kompatibilitätsmatrix.

[Messwerte/Abweichungen](performance-comparison.json),
[Baseline-STDOUT](logs/baseline-benchmark-streams.log),
[Kandidaten-STDOUT](logs/candidate-benchmark-streams.log).
Vollständiges STDERR ist separat komprimiert erhalten.

| Module, prod | Build kalt p50 Baseline → Kandidat | HTTP warm p50 Baseline → Kandidat | HTTP warm p95 Baseline → Kandidat |
| --- | --- | --- | --- |
| 10 | 661,974 → 670,056 ms (+1,22 %) | 0,719 → 0,734 ms (+2,11 %) | 0,800 → 0,979 ms |
| 50 | 2.311,706 → 2.324,257 ms (+0,54 %) | 1,537 → 1,531 ms (−0,40 %) | 3,145 → 3,105 ms |
| 100 | 4.492,629 → 4.675,529 ms (+4,07 %) | 3,527 → 4,195 ms (+18,92 %) | 5,204 → 6,328 ms |

Die erste vollständige Messung ist **nicht pauschal regressionsfrei**: Bei 100
Modulen stiegen Produktions-HTTP-p50 um 18,92 % und erster HTTP-Aufruf um 15,47 %;
die native HTTP-p50-Kontrolle verschob sich zugleich um 35,87 %. Dev-Build-kalt
lag bei 50/100 Modulen um 6,45 %/5,83 % höher, warmer prod-Build bei 100 um 6,79 %.
Diese Abweichungen bleiben als Beobachtung erhalten; es gibt keinen nachträglich
gewählten Schwellenwert, der sie zu einem grünen Performance-Beweis umdeutet.

### Gezielte Wiederholung des auffälligen Produktionsfalls

Der vorhandene Fixture-/`--sample`-Pfad wurde für 100 Module erneut verwendet:
Baseline/Kandidat und native/Sillbeam-Kontrolle in abwechselnder Reihenfolge,
5 Batches × 200 warme Requests = **1.000 Samples je Version/Variante**. Es wurde
kein Runtime-Code und kein Benchmark-Endpunkt verändert. Die tatsächlich geladene
ArchitectureReport-Datei jedes Fixtures ist in den Rohdaten aufgezeichnet.

| Variante | Baseline p50 / p95 | Kandidat p50 / p95 | p50-Abweichung |
| --- | --- | --- | --- |
| Native Symfony | 3,833 / 5,690 ms | 3,754 / 5,523 ms | −2,06 % |
| Sillbeam | 3,932 / 6,445 ms | 3,720 / 5,525 ms | −5,39 % |

[Zusammenfassung](alternating-http-summary.json),
[vollständige Samples](logs/alternating-http-repeat.log).
Die HTTP-Verschlechterung des ersten Laufs wurde damit **nicht reproduziert**.
Alle Produktionsprobes in beiden vollständigen und den Wiederholungsläufen
melden `analyzer_loaded=false` und `parser_loaded=false`. Der HTTP-Spitzenspeicher
im 100-Modul-Fall bleibt bei **28 MiB**.

Das begründet keinen allgemeinen Leistungsgewinn. Es sind lokale In-process-
Kernel-Messungen ohne Netzwerk, ohne OS-Cache-Flush, mit nur drei Build-Samples
und ohne definierte projektweite Performance-SLO. Build-Abweichungen und
Last-/Messvarianz bleiben sichtbar; eine statistisch abgesicherte Aussage über
alle Umgebungen wäre damit nicht gerechtfertigt.

## Fehlgeschlagene Prüfungen und Korrekturen

| Befund / Erstversuch | Ergebnis / Ursache | Korrektur und Nachweis |
| --- | --- | --- |
| CI auf `main/dev`, Alias/Testversionen noch 0.2 | Metadaten-/Coverage-Lücke | Aktiver `dev-0.3.x`-Trigger, Alias/Testversionen/Manifeste korrigiert; lokale Matrix prüft aktuellen Source-Hash |
| Frisches `composer docs:build` | Exit **127**, kein `phpdoc`; in allen vier Jobs reproduziert | `--no-plugins` aus CI-Auflösung entfernt, nur erlaubter Shim aktiv; `composer reinstall phpdocumentor/shim --no-scripts --no-interaction --prefer-dist` und Build in allen Jobs erfolgreich |
| PHPStan in allen Images mit Standard-INI | Exit **255**, 128-MiB-OOM | CI verwendet bereits 512 MiB; damit alle Wiederholungen erfolgreich, keine Typfehler unterdrückt |
| Root-Lock-Aktualisierung / Test-App-Installation in Sandbox | Exit **100** / **1**, DNS/Cache-Zugriff gesperrt | Mit freigegebenem Netzwerk und temporärem Composer-Cache erfolgreich; keine Security-Blocking-Umgehung |
| Baseline-Benchmark-Vorbereitung | Zwei Aufrufe Exit **1**, Tool-Datei fehlte | Git-Archiv berücksichtigt `export-ignore`; nach fehlgeschlagenem Hardlink-Versuch über Dateisystemgrenzen wurden die getrackten Baseline-Dateien und Vendor-Dateien kopiert; danach vollständige Läufe erfolgreich |
| Erste vollständige Benchmark-Log-Erfassung | Prozess Exit 0, aber gemischtes STDOUT/STDERR unvollständig/kein verlässliches JSONL | Nicht für die Bewertung verwendet; beide Läufe mit getrennten Pipes wiederholt, jeweils alle 13 JSONL-Zeilen geprüft |
| Kein GPG in Matrix-Images | Shim-Warnung, dort keine automatische Signaturprüfung | Host-GPG prüft Signatur mit geliefertem Shim-Schlüssel; alle fünf PHAR-SHA-256 identisch. CI prüft GPG-Verfügbarkeit vor Installation |

Die Signaturprüfung ergab eine gültige RSA-Signatur mit Fingerprint
`F33A 0AF6 9AF7 A8B1 5017 DB52 6DA3 ACC4 991F FAE5`. GPG weist auf fehlende
Web-of-Trust-Zertifizierung der User-ID hin; die Prüfung stützt sich auf den im
Shim gelieferten Schlüssel, nicht auf eine behauptete unabhängige Identitätszertifizierung.
Siehe [Signatur-Log](logs/phpdoc-signature.log) und [PHAR-Hashes](phpdoc-sha256.json).

Es wurden weder Fehlerlogs verworfen noch Erstversuche als erfolgreich umetikettiert.
Die ursprünglichen Matrixläufe enden wegen PHPStan/Docs mit Fehlerstatus; die
aufgeführten korrigierten Komponentenläufe sind separat dokumentiert.

## CI, offene Grenzen und Entscheidung

CI enthält jetzt den aktuellen Entwicklungsbranch und PHP 8.4/8.5 × Symfony
7.4/8.1, passendes Speicherlimit, Dokumentations-Extensions/-Build, freigegebene
Shim-Provisionierung, GPG-Prüfung und frische/bestehende Flex-Fälle mit/ohne Doctrine.
Die Workflow-Datei wurde geparst und auf diese Einstellungen geprüft. Der einzige
Source-Hash-Unterschied zwischen anfänglichen Matrixkopien und finalem Stand ist
die korrigierte CI-Datei; `src/`, Tests und Testwerkzeuge sind identisch geblieben.

**Nicht ausgeführt / verbleibende Grenzen:**

- Kein Remote-GitHub-Actions-Lauf wurde ausgelöst oder als grün behauptet. Ohne
  Commit/Push liegt dieser uncommittete Stand dort nicht vor. Nach ausdrücklicher
  Autorisierung muss der freigegebene Commit seine tatsächliche Remote-CI bestehen.
- Der Workflow hat weiterhin keinen PR-Trigger und ersetzt keine separat
  konfigurierte Branch-Protection-/Review-Regel.
- Kein Tag, Packagist-Update oder öffentlicher Recipe-Endpunkt wurde veröffentlicht
  oder in diesem Lauf als verfügbar verifiziert. Recipe-Verfügbarkeit ist eine
  eigenständige externe Voraussetzung; manuelle Installation ist getestet.
- Die Matrix prüft die genannten konkreten Versionen, nicht jede Version im
  deklarierten Bereich und keine zusätzlichen Betriebssysteme. Advisories sind
  eine zeitgebundene Abfrage, keine Sicherheitsgarantie.
- Mermaid wurde als Quelltext getestet, ohne externen Parser/Renderer und ohne
  visuelle Rendering-Abnahme. Dynamische Doctor-Konfiguration bleibt unbekannt;
  native Symfony-Diagnostik kann vor Befehlsausführung normale Cache-Dateien schreiben.
- Produktionsdeployments benötigen weiterhin einen vorgebauten Cache. Das neue
  Release ändert diese operative 0.2.0-Anforderung nicht.
- Die beobachteten Build-/HTTP-Performance-Abweichungen sind oben vollständig
  ausgewiesen. Der gezielte HTTP-Gegentest reproduziert keinen Performance-Blocker;
  es gibt weder eine allgemeine Beschleunigungs- noch eine SLO-Garantie.

**GO zur manuellen Abnahme** stützt sich auf bestandene Funktionen, identische
öffentliche Signaturen/Runtime-Dependencies, die korrigierte vollständige lokale
Matrix und bestätigte Produktions-HTTP-Grenzen. **Keine automatische Veröffentlichung:**
finale Freigabe ausschließlich durch den Maintainer, danach eigener Remote-CI-
Nachweis des genau freigegebenen Commits und separat autorisierte Veröffentlichungsschritte.

## Befehle, Logs und Reproduktion

[commands.json](commands.json) enthält die tatsächlich gestarteten Befehle,
Arbeitsverzeichnisse, ausgewählten Umgebungswerte, Zeit/Exitcode und Logpfade;
[results/](results/) hält die Einzelergebnisse einschließlich Fehlern.
[environment.json](environment.json), [source-sha256.json](source-sha256.json) und
[matrix-versions.json](matrix-versions.json) identifizieren Source und Abhängigkeiten.
Der anfängliche Hashstand ist zusätzlich erhalten. Große Benchmark-STDERR-Logs
sind verlustfrei als gzip gespeichert. Generated HTML/Cache bleibt außerhalb des Git-Stands.

Die Hilfsskripte für den [Signaturvergleich](api-signature-helper.php.txt),
[Fixture-Aufbau](benchmark-fixture-helper.php.txt),
[alternierenden HTTP-Lauf](alternating-http-helper.py.txt) und die
[Performance-Zusammenfassung](performance-summary-helper.py.txt) sind als Textbelege
beigefügt. Ihre lokalen Pfade müssen bei einer Reproduktion entsprechend angepasst werden.

Die Matrix verwendet frische temporäre Repository-Kopien und pro PHP-Version
passende Composer-Ausführung. In jeder Kopie wird `SILLBEAM_SYMFONY_VERSION` auf
`7.4.*` bzw. `8.1.*` gesetzt, danach `php tools/prepare-compatibility.php` und die
CI-Kommandos ausgeführt. `prepare-compatibility.php` verändert Manifeste/Lockfile
und gehört ausschließlich in diese wegwerfbaren Kopien. Die dokumentierten
Plugin-/Speicher-Korrekturen dürfen bei der Reproduktion nicht fehlen.

Keine Commits, Pushes, Tags, Merges, Packagist-Veröffentlichungen oder Releases
wurden durchgeführt. Vorherige historische Ergebnisse wurden unverändert belassen.
