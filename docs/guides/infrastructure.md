# Infrastrukturmodule

Das HTTP-Modul erzeugt oder übernimmt ausschließlich syntaktisch sichere Request-IDs und gibt sie als X-Request-Id zurück. IP-Adressen werden über Symfonys Trusted-Proxy-Modell ermittelt. Das Client-Modul stellt diese Informationen als unveränderliches Objekt bereit.

Das Filesystem-Modul akzeptiert nur relative Pfade ohne leere, Punkt- oder Traversal-Segmente. Der lokale Standardadapter schreibt Dateien atomar und setzt private Rechte; der Flysystem-Adapter ermöglicht S3 oder andere Backends ohne Änderung des Anwendungscodes. Beide Adapter begrenzen vollständige Reads standardmäßig auf 10 MiB.

Das Logger-Modul entfernt Token, Passwörter, Cookies und Credentials rekursiv aus strukturiertem Log-Kontext. Das Messenger-Modul verwendet Symfony Messenger direkt und stellt nur einen stabilen Dispatch-Contract bereit. Retry und Transport bleiben Anwendungskonfiguration.

`php bin/console nexum:architecture` zeigt die kompilierte Zuordnung von Projects und Modules einschließlich Scope und Dependencies. Die optionale API unter `/api/nexum/modules` ist ebenfalls ausschließlich lesend und benötigt `nexum.manage`. Aktivierung und Deaktivierung bleiben Build-Konfiguration; Nexum verändert weder Quellcode noch YAML zur Laufzeit.
