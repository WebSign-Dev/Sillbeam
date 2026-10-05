# Architektur

Der Container-Build erzeugt einen gemeinsamen Quellindex. PHP-Parser löst Namespaces und Imports auf, identifiziert Attribute auf Descriptoren und erfasst statische Klassenreferenzen. Discovery ordnet Descriptoren ihren konfigurierten Grenzen zu und prüft eindeutige, nicht überlappende Modul- beziehungsweise Project-Verzeichnisse.

Der Compiler prüft IDs, deklarierte Abhängigkeiten, Zyklen und Hostzuordnungen und ermittelt aktive Graphen. Ein besuchter Knoten wird pro Graph einmal expandiert. Der Codeanalysator prüft die direkten Referenzen aktiver Module gegen die direkten Deklarationen und die private Project-Grenze. Transitive Deklarationen erlauben keine direkten Zugriffe.

Dieses Ergebnis versorgt Registries, native Serviceimporte, Routing und optional Doctrine. Deaktivierte oder nicht zugeordnete Module bleiben diagnostizierbar, ohne automatisch importiert zu werden. Auch Elternimporte aus Projects schließen Modulbereiche aus. Explizite native Symfony-/Doctrine-Imports bleiben eine bewusste Escape Hatch und können Code erneut aktivieren.

Directory-, File- und FileExistence-Resources überwachen die Quellgrenzen, inklusive noch fehlender Standardverzeichnisse. Produktionscache wird wie bei Symfony im Deployment neu gebaut. Aktivierungswerte aus Umgebungsvariablen werden beim Build aufgelöst; Änderungen erfordern einen Cache-Neubau.

Project ist eine Oberfläche: Host-Routing und Request-Kontext, gemeinsamer Container, Event-System und EntityManager. Keine Isolation globaler Handler oder Sicherheitsgrenze.
