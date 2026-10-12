# Release-Paket-Vorbereitung — 2026-10-12

Ziel: vorbereiteter, unveröffentlichter Release-Kandidat **v0.1.0** auf Basis von `051dbda`. Kein Tag, Push, Merge, GitHub-Release, Packagist-Upload oder Live-Deployment. Die Laufzeit-API und Abhängigkeitsversionen bleiben unverändert.

## Paket und Flex

`composer.json` deklariert jetzt `symfony-bundle`; Homepage ist die Produktwebsite. Symfony verlangt diesen Typ für die Bundle-Installation und untersagt semantisch leere Recipe-Dateien unter `config/packages`: [Bundle-Vorgaben](https://symfony.com/doc/current/bundles/best_practices.html#installation), [Recipe-Checkliste](https://github.com/symfony/recipes/blob/main/README.rst#recipe-checklist). Die Recipe registriert das Bundle und installiert nur den Route-Import. Standardkonfiguration funktioniert ohne `config/packages/sillbeam.yaml`. Zwei fehlgeschlagene Regressionstests wurden vor der Korrektur aufgezeichnet.

Der unabhängige Flex-Test prüft Zero-Config-Boot, beide Kernel, kalte/warme Development-/Production-Caches und Installation/Deinstallation ohne/mit Doctrine. Entfernen bewahrt native Services und selbst angelegte Konfiguration; nach expliziter Bereinigung eigener Sillbeam-Optionen und Rückkehr zum nativen Kernel bootet die Anwendung weiterhin. Die native Anwendung verlangt ihre eigenen Framework-/Console-/YAML-Abhängigkeiten. Keine globalen Development-Stabilitätsflags sind nötig; `dev-main` wird gezielt angefordert.

`composer test:release-package` installiert den tatsächlichen Composer-ZIP in einer isolierten manuellen Symfony-Anwendung, ohne Flex oder Doctrine. Eine ausschließlich im Test simulierte Version `0.1.0` ersetzt keinen Git-Tag. Die PHP-Snippets und der native Serviceimport werden aus dem aktuellen Getting-started-Guide übernommen; Hello-Route, Autowiring und beide Diagnosebefehle bestehen mit beiden Kerneln und kalten/warmen `dev`-/`prod`-Caches. Die Composer-Metadaten/PSR-4-Plattformprüfung des Verbrauchers werden ebenfalls validiert.

PHP `^8.4`, Symfony `^7.4 || ^8.0`, PHP-Parser `^5.7` und optionale Doctrine-Integration bleiben erhalten. Kein neues Pflichtpackage. Strict-Validierung bestätigt die Konsistenz des vorhandenen Lockfiles; Typ/Homepage/Scripts/Archive-Regeln benötigen keinen Versions- oder Lock-Hash-Wechsel. Composer-Stabilität und Paketmetadaten wurden gegen die [offizielle Schema-Dokumentation](https://getcomposer.org/doc/04-schema.md) geprüft.

## Distributions- und Sicherheitsprüfung

Ein realer Composer-ZIP enthielt den abgeschlossenen internen Entwicklungsplan trotz Git-Exportregel. Ein neuer Regressionstest erzeugt einen internen Sentinel und prüft den tatsächlichen Archiver; er scheiterte vor dem zusätzlichen Composer-Ausschluss. `docs/superpowers` ist jetzt ausdrücklich ausgeschlossen, der erledigte Plan entfernt. Zwei generierte Symfony-`reference.php`-Dateien sind aus der Versionierung genommen und künftig ignoriert; lokal durch Tests erzeugte Dateien bleiben Arbeitsartefakte. Technische Messwerte und Kompatibilitätsnachweise bleiben erhalten; ältere Audits sind als abgeschlossene historische Snapshots mit Verweis auf aktuelle Installation/Release-Notes gekennzeichnet.

Der Archivtest prüft Runtime, Lizenz, Security-Policy und Einstieg sowie den Ausschluss von Tests, Tools, Beispielen, Entwicklungsplänen, `.env`, Caches, Referenzdateien und Lockfile. Aktuelle öffentliche Texte enthalten keine persönlichen `/home`-/`/Users`-Pfade. Beispielpfade wie `/absolute/path/to/sillbeam` sind ausdrücklich Platzhalter.

`composer audit --locked --no-dev --format=json` und `composer audit --locked --format=json` meldeten keine Advisories oder aufgegebenen Pakete. MIT-Lizenz, Copyright und Composer-Lizenzangabe sind konsistent. Signaturprüfung aller 44 erreichbaren Commits: keine Treffer für private Schlüssel, GitHub-Token oder AWS-Access-Key-Formate. Das ist keine Garantie für jedes Secret-Format. Aktuelle eingecheckte Test-`.env`-Dateien enthalten Entwicklungsdefaults und ein leeres `APP_SECRET`; alte Testwerte bleiben in der Historie. Eine externe Wiederverwendung kann lokal nicht ausgeschlossen werden und erfordert gegebenenfalls Rotation außerhalb dieses Repositories.

`SECURITY.md` nennt GitHubs privates Reporting ausdrücklich nur bei Verfügbarkeit. Dessen Aktivierung ist nicht verifiziert. Der veröffentlichte allgemeine Kontakt `sillbeam@websign.de` ist nur ein Weg, einen vertraulichen Meldekanal anzufragen, kein erfundener Security-Mailbox-Service oder Reaktionszeitversprechen.

## Dokumentation und Website

README, Einstieg, Flex-/Konfigurationsreferenz, Changelog und vorbereitete Release-Notes sind auf Bundle plus Routing ohne leere Konfiguration abgestimmt. Native `App\`-Serviceimports und deren Modul-/Project-Ausschlüsse bleiben erklärt. Projects bieten keine Mandanten-/Sicherheitsisolation; dynamische Klassenstrings, PHPDoc, String-Service-IDs und Code außerhalb der Modulflächen bleiben außerhalb der statischen Dependency-Analyse. Website und GitHub-Einstieg verlinken sich sinnvoll.

Der tatsächliche Website-Code liegt im verfügbaren Repository [WebSign-Dev/Sillbeam_homepage](https://github.com/WebSign-Dev/Sillbeam_homepage), nicht im veralteten Nexum-Homepage-Checkout. Gezielte lokale Korrekturen: echte Repository-/Issue-Links, korrekter Paketname, ausführbare Clone-/Composer-Anleitung, Release-Status und Flex-/Import-Regeln in Deutsch/Englisch; Canonical/Open-Graph/Robots/Sitemap für 23 vorgerenderte Seiten und Client-Navigation. Ein nachgewiesener SSR-/Client-Hydration-Fehler wurde durch übereinstimmende Compiler-Kommentaroptionen behoben. `npm test` baut nun seine benötigten Integrationsartefakte automatisch; der zuvor beobachtete ENOENT-Fehler im sauberen Checkout ist behoben. Branding, Logo und CSS-Konzept wurden bewahrt.

Browserprüfungen am lokalen Production-Preview: Desktop 1280 px, Tablet 768 px, Smartphone 390 px, jeweils kein horizontaler Überlauf; Mobilmenü öffnet/schließt und führt zur Dokumentation, Tastatur-Sprunglink fokussiert `main`, helles/dunkles Farbschema funktionieren. Repräsentative helle Textkontraste 4,89:1 bis 15,6:1; keine kaputten Bilder im geprüften Einstieg. Frischer Browserlauf nach Hydration-Fix: keine Warnungen/Fehler. Keine vollständige WCAG-Zertifizierung, kein quantifizierter CLS- oder Lastnachweis. Die Live-Seite wurde nicht verändert; dort gelten die lokalen Korrekturen erst nach einem separat freigegebenen Deployment.

## Ausgeführte Prüfungen

- Vollständige Composer-Prüfkette: `validate --strict`, `test`, `analyse`, `architecture`, `cs:check`, `test:flex-recipe`, `test:consumer`, `test:release-package`; zusätzlich Flex mit Doctrine und `git diff --check`.
- 85 PHPUnit-Tests / 397 Assertions unter PHP 8.5.4/Symfony 8.1.6 am Arbeitsstand. Die Archiv-Assertions zählen reale Dateien und können mit Dokumentationsumfang variieren.
- Dieselben 85 Tests außerdem unter PHP 8.4.26 und PHP 8.5.4, jeweils Symfony 7.4.0, 8.0.15 und 8.1.6; die minimale Symfony-Linie wurde tatsächlich mit niedrigsten auflösbaren Abhängigkeiten geprüft.
- Zwölf unabhängige Verbraucherinstallationen: sechs Betriebsformen ohne/mit Doctrine, beide Kernel, PHP 8.5.4/Symfony 8.1.8.
- Website: Production-Build mit 23 Seiten, 12 Tests, ESLint, Prettier, Diff-Prüfung und statischer Premium-Audit; zusätzliche reale Browserprüfungen wie oben.

Exakte PHP-Patch-Untergrenzen 8.4.0/8.5.0, Symfony 8.0.0 sowie neue PHP-8.4-Flex-Installationen wurden in diesem Schritt nicht ausgeführt. Vor Veröffentlichung bleiben externe Voraussetzungen: Packagist-/Recipe-Verfügbarkeit, verifiziertes privates Vulnerability-Reporting, gegebenenfalls Rotation wiederverwendeter historischer Testwerte und separat genehmigte Website-Veröffentlichung. Keine dieser externen Maßnahmen wurde als erledigt dargestellt.
