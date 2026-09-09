# Infrastrukturpakete

Das HTTP-Paket erzeugt oder übernimmt ausschließlich syntaktisch sichere Request-IDs und gibt sie als X-Request-Id zurück. IP-Adressen werden über Symfonys Trusted-Proxy-Modell ermittelt. Das Client-Paket stellt diese Informationen als unveränderliches Objekt bereit.

Das Filesystem-Paket akzeptiert nur relative Pfade ohne leere, Punkt- oder Traversal-Segmente. Der lokale Standardadapter schreibt Dateien atomar und setzt private Rechte; der Flysystem-Adapter ermöglicht S3 oder andere Backends ohne Änderung des Anwendungscodes. Beide Adapter begrenzen vollständige Reads standardmäßig auf 10 MiB.

Das Logger-Paket entfernt Token, Passwörter, Cookies und Credentials rekursiv aus strukturiertem Log-Kontext. Das Messenger-Paket verwendet Symfony Messenger direkt und stellt nur einen stabilen Dispatch-Contract bereit. Retry und Transport bleiben Anwendungskonfiguration.

Der Befehl **nexum:module enable|disable|remove ID** und die API unter **/api/nexum/modules** verwenden denselben transaktional gesperrten Service. Die API benötigt die Permission **nexum.manage**. Abhängige aktive Module blockieren Deaktivierung und Entfernung; der Befehl entfernt nur den YAML-Eintrag, niemals Quellcode oder Composer-Pakete.
