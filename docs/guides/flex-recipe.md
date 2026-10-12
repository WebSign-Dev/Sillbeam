# Symfony Flex

Die vorbereitete Contrib-Recipe liegt unter [flex-recipe](https://github.com/WebSign-Dev/Sillbeam/blob/main/flex-recipe/README.md). Sie registriert SillbeamBundle, kopiert `sillbeam: {}` und importiert einmal `type: sillbeam` für das Routing. Doctrine aktiviert sich nur bei installiertem und registriertem DoctrineBundle. Die Recipe installiert keine Foundation.

Die Recipe muss unabhängig vom PHP-Package in einem Symfony-Flex-Endpoint veröffentlicht werden. Bis zur Freigabe die drei Einträge aus der [README](../../README.md) manuell setzen. Ein lokaler Composer-/Flex-Installationstest prüft Registrierung, Dateien, symfony.lock und minimalen Kernel-Boot: `composer test:flex-recipe`.

Die Recipe bewahrt bestehende `services.yaml`-Dateien. Bei einem Symfony-Skeleton den aktiven `App\:`-Import beibehalten und `Module`/`Project` beziehungsweise eigene Discovery-Bereiche zu dessen Ausschlüssen hinzufügen, wenn Sillbeam die automatische Aktivierung kontrollieren soll. Siehe [Integrationsregeln](getting-started.md#symfony-integration-erhalten). Der lokale Flex-Test verwendet diesen normalen Import, prüft einen nativen Service und ein deaktiviertes Modul und bootet beide Kernel in Development/Production jeweils mit kaltem und warmem Cache.
