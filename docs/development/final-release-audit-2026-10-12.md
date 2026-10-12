# Unabhängiger Release-Audit für v0.1.0 — 2026-10-12

## Quellstand und Methode

Ausgangspunkt: `21e75cb3fad489882822faa7003f5e770dd3e6eb`, sauberer Working Tree. Geprüft wird dieser Commit **plus die unten genannten Audit-Korrekturen**, nicht der unveränderte Commit. Website: `c9e36e40f7796d39d48b5a56a4cacb58625672c0`. Keine Veröffentlichung, Tags, Pushes, Merges oder Deployments. Audit-Korrekturen werden als Working-Tree-Diff übergeben.

SHA-256 des finalen Laufzeitstands (`src/**/*.php`, `composer.json`, `composer.lock`; sortierte relative Pfade plus NUL-getrennte Dateiinhalte): `8af0e44790b107d818a4e64a0686cd3ab9416d8388302a6bb37652bf6413087e`.

Alle 37 produktiven PHP-Dateien, ihre Integration, Tests und Release-Dokumente wurden erneut gelesen. Zwei getrennte Reviewer prüften Architektur/Discovery/Dependency-Analyse sowie DI/Routing/Doctrine/Kernel ohne die vorherigen Erfolgsmeldungen; die Korrekturen wurden ebenfalls erneut geprüft. Der zweite Durchlauf nutzt eine isolierte Kopie, ohne kopierte Vendor-Verzeichnisse oder Testcaches, mit neuer `composer install`-Installation aus dem Lockfile. Gemeinsame Composer-Downloadcaches ersetzen keine installierten Abhängigkeiten. Produktiver Code, Composer-Manifest und Lockfile beider Durchläufe sind bytegleich.

## Neu gefunden und behoben

| Befund | Auswirkung und Korrektur | Nachweis |
| --- | --- | --- |
| P1: Ausgeschlossene Discovery-Roots erneut betreten | `excludes: [Module, Project]` wurde beim Scan von `src` beachtet, danach durch eigene/überlappende Roots umgangen. `SourceInspector` prüft jetzt die Vorfahren innerhalb der konfigurierten Discovery-Grenzen und bewahrt ausgeschlossene Pfade für nachgelagerte Imports. | Vier neue Tests zunächst rot; danach grün. Descriptoren, fehlerhafte ausgeschlossene PHP-Dateien, Services, Routing und Doctrine geprüft. Ein zusätzlicher Regressionstest schützt relative Ausschlüsse vor Anwendungen unter gleichnamigen Systemverzeichnissen. |
| P2: Erste Architekturkonfiguration invalidiert Dev-Container nicht | Nach Zero-Config-Boot blieb die erstmalige `config/packages/sillbeam.yaml` mit `enabled: false` im warmen Dev-Container unbeachtet. `SillbeamExtension` überwacht jetzt das Konfigurationsverzeichnis einschließlich Ergänzungen. | Eigenständiger ZIP-Consumer reproduziert; neuer Integrationstest in getrennten PHP-Prozessen zunächst rot, anschließend 18 Assertions grün. Anlage, Änderung und Entfernung der Datei geprüft. |
| P2: Exakt ausgeschlossene Entity-Unterordner im Parent-Mapping | Ohne `/**` wurde ein ausgeschlossener Unterordner beim Parsing verworfen, vom übergeordneten Doctrine-Mapping aber trotzdem geladen. Generierte Mappings filtern jetzt anhand desselben Source-Indexes wie Services und Routing. Native Overrides bleiben maßgeblich. | Neuer Integrationstest zunächst rot; nach Korrektur grün. Zusätzlicher Test bestätigt bewusstes natives Mapping eines ausgeschlossenen Verzeichnisses. |
| P2: Analyse mit Symfony 7.4.0 scheitert | Dessen `TreeBuilder` hat keinen generischen Standardtyp. Die Rückgabe von `Configuration::getConfigTreeBuilder()` dokumentiert nun den tatsächlich erzeugten Typ `TreeBuilder<'array'>`. Keine Laufzeit-/Signaturänderung. | `composer analyse` mit aktuellem PHPStan und Symfony 7.4.0 zunächst mit `missingType.generics` fehlgeschlagen, anschließend erfolgreich. Der Typchecker ist hier die Regressionprüfung. |

Betroffene Implementierung: `src/Architecture/SourceInspector.php`, `src/DependencyInjection/SillbeamExtension.php`, `src/DependencyInjection/Configuration.php`. Tests: `tests/Unit/FlexibleDiscoveryTest.php`, `tests/Integration/SymfonySkeletonTest.php`, `tests/Integration/ModuleLayerTest.php`, neues `tests/Integration/ContainerCacheTest.php`. `CHANGELOG.md` ergänzt. Keine neuen Pflichtabhängigkeiten, Produktfunktionen oder Änderungen der öffentlichen Laufzeit-API.

## Zwei vollständige Abnahmedurchläufe

Umgebung: PHP **8.5.4**, Composer **2.9.5**, Symfony FrameworkBundle **8.1.6**, PHP-Parser **5.8.0**, DoctrineBundle **3.3.2**, ORM **3.7.0**, PHPUnit **12.5.35** aus dem Repository-Lockfile.

| Tatsächlich ausgeführter Befehl | Durchlauf 1 | Frischer Durchlauf 2 |
| --- | --- | --- |
| `composer validate --strict` | Exit 0 | Exit 0 |
| `composer check-platform-reqs` | Exit 0 | Exit 0 |
| `composer test` | 93 Tests / 432 Assertions | 93 Tests / 432 Assertions |
| `composer analyse` | keine Fehler | keine Fehler |
| `composer architecture` | 0 Verletzungen / Warnungen / Fehler | identisch |
| `composer cs:check` | 0 Änderungen, 131 Dateien | identisch |
| `composer test:flex-recipe` | Exit 0, ohne Doctrine | identisch |
| `SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe` | Exit 0 | Exit 0 |
| `composer test:consumer` | 12 Installationen, beide Kernel | identisch |
| `composer test:release-package` | echtes ZIP, dokumentiertes Hello, beide Kernel, kalt/warm Dev/Prod | identisch |
| `composer audit` | keine Advisories | keine Advisories |
| `git diff --check` | Exit 0 | Exit 0 |

Deptrac meldet zusätzlich 104 nicht von seinen Layer-Regeln erfasste Referenzen; das ist keine Testabdeckung und keine behauptete vollständige statische Architekturprüfung. Die Sillbeam-Modulprüfung wird durch eigene Tests abgesichert.

Die sechs Consumer-Layouts sind Symfony-only, Modules-only, Project-only, Single-Project, Multi-Project und Hybrid, jeweils mit/ohne Doctrine und normalem/optionalem Sillbeam-Kernel. Consumer lösen ihre eigenen Abhängigkeiten auf (hier Symfony 8.1.8, ORM 3.7.5), besitzen eigene Composer-Autoloader und enthalten keine PHPUnit-/Repository-Testklassen.

Zusätzlich wurden **zweimal** eigenständige Anwendungen aus dem echten Composer-ZIP mit normalem `App\`-Import und notwendigen Bereichsausschlüssen frisch installiert. Geprüft: Zero-Config, dokumentierter Hello-Service/-Controller, Dev/Prod kalt/warm, tatsächliche CLI-Ausführung, Aktivierung/Deaktivierung ohne Service-/Routing-/Mapping-Leaks, verständliche Diagnose unzulässiger direkter Codezugriffe und Persistenz mit Doctrine. Tatsächlich ausgeführte Dokumentationskommandos: `cache:clear`, `sillbeam:about`, `sillbeam:architecture` (Text/JSON), `debug:container`, `debug:router`, `debug:router hello` und `doctrine:mapping:info`.

Weitere unabhängige Prozesse prüfen zweimal tatsächliches Hinzufügen/Ändern/Entfernen von Modulen, Projects und Controllern, Hostwechsel, Routennamenpräfixe und Request-Project-Kontext. Development erneuert automatisch; Production erhält den dokumentierten expliziten Cache-Neubau. Lokale Flex-Endpunkte prüfen Installation, beide Kernel und Deinstallation einschließlich Erhalt anwendungseigener Konfiguration.

## Versionsmatrix

Die sechs Kombinationen wurden jeweils **zweimal** geprüft:

| PHP | Symfony FrameworkBundle im Qualitätstest | Je Durchlauf | Frische Verbraucherversion |
| --- | --- | --- | --- |
| 8.4.26 / 8.5.4 | 7.4.0 | 93 Tests / 431 Assertions, Analyse/Architektur/CS/Validierung/Platform erfolgreich | 7.4.20 |
| 8.4.26 / 8.5.4 | 8.0.15 | identisch | 8.0.15 |
| 8.4.26 / 8.5.4 | 8.1.8 | identisch | 8.1.8 |

Es wurden 72 lokale Qualitätskommandos ausgeführt sowie 48 zusätzliche Installationskommandos: pro Kombination und Durchlauf Flex ohne/mit Doctrine, zwölf unabhängige Layout-/Doctrine-Consumer und eine tatsächliche ZIP-/manuelle Installation. Alle Exit 0. Consumer prüfen beide Kernel; Flex/ZIP außerdem kalte/warme Dev-/Prod-Caches. DoctrineBundle 3.3.2 / ORM 3.7.5. Die erste pauschale Minimalauflösung prüfte zusätzlich DoctrineBundle 3.3.0 / ORM 3.6.0 funktional, verwendete jedoch ungeeignete ältere Entwicklungswerkzeuge.

Symfony-Komponenten mit unabhängig veröffentlichten Patchständen bleiben innerhalb der ausgewählten Minor-Linie; beispielsweise Config 8.1.5 bei FrameworkBundle 8.1.8. PHP-Parser ist in den frisch aufgelösten Matrixinstallationen 5.9.0; im Haupt-Lockfile 5.8.0.

Die Matrix-Archive enthalten den erst später hinzugefügten Auditbericht nicht: Der Archivtest macht eine Assertion pro Eintrag, daher dort 431 statt 432 Assertions. Im ersten Haupt-/Kopie-Vergleich fehlte diese Datei ebenfalls in der Kopie; nach Angleichung und erneutem vollständigem Durchlauf liefern beide 432. Es gibt keine abweichenden Laufzeitergebnisse.

Historische Symfony-7.4.0-Patchstände wurden ausschließlich zur funktionalen Untergrenzenprüfung verwendet. `composer audit` dieser absichtlich alten temporären Installation meldet **9 Advisories in 6 Symfony-Paketen**; sie ist kein freigegebener Produktionsstack. Ihre Neuauflösung benötigt ausdrücklich `--no-security-blocking` in der wegwerfbaren Matrixkopie. Release-Manifest, Repository-Lockfile und normale Verbraucherinstallationen bleiben ohne diese Ausnahme. Das saubere Audit des aktuellen Lockfiles darf nicht auf beliebige ältere, durch Semver erlaubte Patchstände übertragen werden.

## Performance und Ressourcen

Je Messung eigener Prozess, 10/50/100 Module mit je 22 PHP-Dateien (220/1100/2200 Dateien), kettenförmigen Dependencies und Controller-Routen. PHP 8.5.4. Werte zweier aktueller Durchläufe in Millisekunden; lokale Messungen, keine garantierten Produktionsbudgets.

| Module | Dev Container kalt / warm, Lauf 1 → Lauf 2 | Prod Container kalt / warm, Lauf 1 → Lauf 2 | Peak MiB Dev / Prod, Lauf 1 → Lauf 2 |
| --- | --- | --- | --- |
| 10 | 462.1 / 10.33 → 444.8 / 10.00 | 353.7 / 0.28 → 362.3 / 0.31 | 28 / 24 → 26 / 24 |
| 50 | 1405.6 / 45.43 → 1205.6 / 43.73 | 1052.2 / 0.36 → 1039.0 / 0.32 | 42 / 36 → 42 / 34 |
| 100 | 2244.7 / 90.67 → 2246.6 / 90.23 | 1922.5 / 0.35 → 1907.5 / 0.34 | 62 / 50 → 60 / 48 |

10 Module: Parsing 87.12–92.02 ms, Discovery nach Index 5.83–6.02 ms, Compile 0.56–0.67 ms, Analyse 0.76–0.81 ms, Routing kalt/warm 9.47–13.51 / 3.62–5.91 ms; 50 Module: Parsing 372.30–394.04 ms, Discovery nach Index 26.37–28.15 ms, Compile 0.75–0.91 ms, Analyse 5.17–5.35 ms, Routing kalt/warm 34.26–58.60 / 15.61–34.08 ms; 100 Module: Parsing 726.78–750.12 ms, Discovery nach Index 51.71–53.72 ms, Compile 1.06–1.10 ms, Analyse 15.17–16.04 ms, Routing kalt/warm 85.15–134.44 / 38.98–92.54 ms. Der warme Source-Index liegt über alle Fälle bei 0.010–0.017 ms. Benchmark ohne Doctrine; warme Boots erfolgen im selben Prozess nach Shutdown mit bereits geladenen Klassen. Dies sind keine HTTP-/FPM-End-to-End-Latenzen.

Parsing dominiert die Discovery-Kosten; der warme, pro Build gehaltene Source-Index erspart erneutes Parsing. Die Analyse sucht Module linear je Datei/Referenz und skaliert überproportional, bleibt in dieser Messung klein gegenüber Parsing und Container-Build. Dev-Freshness kostet mit wachsender Dateizahl mehr; warme Production-Container bleiben unter 1 ms in diesen Messungen. Kein belegter Anlass für eine spekulative Cache-/Compiler-Umgestaltung. Vollständige Einzelwerte einschließlich Discovery, Compile, Analyse und Routing liegen im lokalen Nachweisarchiv.

## Dokumentation, Paket und Website

README, Changelog, Security, MIT-Lizenz, Release-Notes, Composer-Metadaten, öffentliche API und Migration wurden erneut mit dem Code verglichen. 118 lokale Dokumentationslinks haben vorhandene Ziele. Foundation bleibt entfernt; Strata ist weder verfügbarer Ersatz noch Pflichtabhängigkeit. Das reale ZIP ist installierbar; interne Pläne, Vendor-/Testcaches und Entwicklungsanwendungen werden nicht ausgeliefert.

Website-Commit `c9e36e4` wurde in einer frischen Kopie mit `npm ci` installiert: zweimal `npm test` (Build mit 23 Seiten, 12/12 Tests), `npm run lint` und `npm run format:check` erfolgreich. Browsernavigation, deutsche/englische Installation, Sprachwechsel und Canonical-/OG-Angaben wurden erneut geprüft; im vorbereiteten Build keine Browserwarnungen/-fehler. Alle 23 lokalen Routen liefern HTTP 200; interne Seiten-, Asset- und Ankerziele vorhanden.

Die öffentliche Website liefert noch den älteren Stand: generische `github.com`-Links, falsches `benjaminoeffinger/sillbeam:@dev`, leere Package-Konfiguration, veraltete Umbenennungsbehauptung und eine Hydration-Fehlermeldung. Diese Punkte sind im vorbereiteten Website-Commit bereits korrigiert; dessen Auslieferung wurde hier ausdrücklich nicht durchgeführt. Das ist eine offene externe Release-Abstimmung, kein neuer Defekt des vorbereiteten Website-Quellstands.

## Abweichungen, offene Voraussetzungen und Grenzen

- Erste Sandbox-Versuche scheiterten an Netzwerk/localhost-Sockets. Mit passenden Ausführungsrechten wiederholt und bestanden. Eine anfängliche Quellkopie ohne `.git` konnte `git diff --check` nicht ausführen; danach lokaler temporärer Git-Baseline und erfolgreicher echter Check. Eine CS-Abweichung in der neuen Schleife wurde korrigiert und beide vollständigen Durchläufe wiederholt. Der zusätzliche CLI-Harness musste Symfony-Zeilenumbrüche normalisieren; die erwartete Dependency-Diagnose war bereits korrekt. Die temporäre Minimal-Matrix musste exakte Auflösungs-Pins von gültigen Composer-Constraints trennen; nach Lock-Metadatenkorrektur bestehen beide Validierungen.
- Ein pauschales `--prefer-lowest` installierte PHPStan 2.0.0 und CS-Fixer 3.69.0. Der alte Fixer kann die PHP-8.4/8.5-Prüfung nicht ausführen; PHPStan zeigte zusätzlich ältere Inferenzprobleme. Die Symfony-Untergrenzenmatrix verwendet daher kompatible aktuelle Entwicklungswerkzeuge. Die originale Entwicklungstool-Untergrenze wird nicht als vollständig bestanden dargestellt.
- GitHub-Metadaten bestätigen: Repository weiterhin **private**, keine veröffentlichten Releases. Packagist-Metadaten und Recipes-Contrib-Paketpfad liefern HTTP 404. Public-Schaltung, Package-Veröffentlichung und Recipe-Merge sind offen. Private Vulnerability Reporting ist weiterhin nicht nachgewiesen.
- Aktueller [GitHub-CI-Run](https://github.com/WebSign-Dev/Sillbeam/actions/runs/38220380806) für `21e75cb` ist `failure`, sechs Jobs haben **keine ausgeführten Schritte**. Die genaue Quota-/Limit-Ursache wurde nicht erneut durch eine Annotation bestätigt; daraus wird kein Codefehler abgeleitet. Ein erfolgreicher öffentlicher CI-Run des endgültigen Stands ist noch ungeprüft und nach Public-Schaltung separat erforderlich.
- PHP 8.4.0/8.5.0, Symfony 8.0.0, Windows/macOS, FPM-/OPcache-Deployment und externe Datenbankserver wurden nicht geprüft. SQLite-Persistenz wurde ausgeführt. Vollständige WCAG-Konformität oder Produktions-Lastkapazität wird nicht behauptet.
- Statische Prüfung erfasst unterstützte echte PHP-Klassenreferenzen zwischen Modulbereichen; dynamische Klassenstrings, PHPDoc, String-Service-IDs und normale Symfony-/direkte Project-Bereiche bleiben außerhalb. Alle Projects teilen Container, Datenbank und Event-System; keine Tenant-/Security-Isolation oder automatische Hostbindung globaler Handler.
- Breite native Service-/Routing-/Doctrine-Imports können Aktivierung bewusst umgehen. Die dokumentierten Bereichsausschlüsse und expliziten Symfony-Overrides bleiben erforderlich. Production benötigt Cache-Neubau bei Code-, Routing-, Composer- oder Konfigurationsänderungen.

## Entscheidung

**GO MIT EINSCHRÄNKUNGEN für den hier geprüften, korrigierten Quellstand.**

Alle verpflichtenden Repository-Prüfungen sind in beiden finalen Durchläufen erfolgreich. Die Versionsmatrix und unabhängigen Installationen bestätigen den technischen Kern. Es bleiben keine bestätigten offenen P0-/P1-/P2-Defekte im vorbereiteten produktiven Sillbeam-Stand. Die vier gefundenen Befunde sind behoben und ihre Nachweise dokumentiert.

Offen sind externe Voraussetzungen: Public-Schaltung, Package-Veröffentlichung, separater Recipe-Merge, verifizierter vertraulicher Sicherheitsmeldekanal, öffentlicher CI-Nachweis und Auslieferung des bereits korrigierten Website-Stands. Die alte 7.4.0-Simulationsumgebung mit bekannten Upstream-Advisories ist ausdrücklich nicht für Production freigegeben; der freigegebene aktuelle Lockfile-Audit ist sauber. Das Urteil erlaubt keine Veröffentlichung aus diesem Auftrag.

Lokale Rohlogs, Exitcodes, Reproduktionsskripte, Versions-Lockfiles, Quellmanifest und Regression-Red/Green-Nachweise werden als `sillbeam-final-audit-evidence.zip` außerhalb des Release-Pakets übergeben. Sie belegen die hier genannten Ergebnisse; historische Berichte wurden nicht als Testnachweis übernommen.
