# Symfony Flex

Die vorbereitete Contrib-Recipe liegt unter [flex-recipe](../../flex-recipe/README.md). Sie registriert SillbeamBundle, kopiert `sillbeam: {}` und importiert einmal `type: sillbeam` für das Routing. Doctrine aktiviert sich nur bei installiertem und registriertem DoctrineBundle. Die Recipe installiert keine Foundation.

Die Recipe muss unabhängig vom PHP-Package in einem Symfony-Flex-Endpoint veröffentlicht werden. Bis zur Freigabe die drei Einträge aus der [README](../../README.md) manuell setzen. Ein lokaler Composer-/Flex-Installationstest prüft Registrierung, Dateien, symfony.lock und minimalen Kernel-Boot: `composer test:flex-recipe`.
