# Symfony Flex

Die vorbereitete Contrib-Recipe liegt unter [flex-recipe](https://github.com/WebSign-Dev/Sillbeam/blob/main/flex-recipe/README.md). Sie registriert SillbeamBundle und importiert einmal `type: sillbeam` für das Routing. Doctrine aktiviert sich nur bei installiertem und registriertem DoctrineBundle. Die Recipe installiert keine Foundation.

Die Recipe muss unabhängig vom PHP-Package in einem Symfony-Flex-Endpoint veröffentlicht werden. Bis zur Freigabe Bundle-Registrierung und Route-Import aus der [README](../../README.md) manuell setzen. Ein lokaler Composer-/Flex-Installationstest prüft Registrierung, Dateien, symfony.lock Installation ohne Package-Konfigurationsdatei, beide Kernel und anschließende Deinstallation: `composer test:flex-recipe`.

Die Recipe bewahrt bestehende `services.yaml`-Dateien. Bei einem Symfony-Skeleton den aktiven `App\:`-Import beibehalten und `Module`/`Project` beziehungsweise eigene Discovery-Bereiche zu dessen Ausschlüssen hinzufügen, wenn Sillbeam die automatische Aktivierung kontrollieren soll. Siehe [Integrationsregeln](getting-started.md#symfony-integration-erhalten). Der lokale Flex-Test verwendet diesen normalen Import, prüft einen nativen Service und ein deaktiviertes Modul und bootet beide Kernel in Development/Production jeweils mit kaltem und warmem Cache.

Die Recipe erzeugt keine semantisch leere `config/packages/sillbeam.yaml`. Bei Bedarf diese Datei selbst mit den gewünschten Optionen anlegen. Beim Entfernen bleiben native Services und selbst angelegte Sillbeam-Konfiguration erhalten; die Recipe entfernt nur ihren Route-Import und ihre Bundle-Registrierung.

Nach der Deinstallation selbst angelegte `sillbeam`-Optionen aus den Symfony-Package-Imports entfernen und gegebenenfalls einen von `SillbeamKernel` abgeleiteten Anwendungskernel auf den normalen Symfony-Kernel zurückstellen. Das sind Anwendungseinstellungen; die Recipe löscht oder überschreibt sie nicht. Der Verbrauchertest prüft nach dieser expliziten Bereinigung auch den nativen Kernel-Boot.
