# Umfang von Nexum

Nexum enthält Architecture, Module, Project, native Integration, Diagnose und einen optionalen Kernel. Doctrine-Mapping ist eine optionale Integration, keine verpflichtende Laufzeitabhängigkeit.

Die frühere Foundation wurde aus diesem Package entfernt: APIs/CRUD, Security/User/MFA/Passkeys, Entity-Basen/Stores/Soft-Delete, Filesystem/Mailer/QR und generische Fach- oder Infrastrukturverträge gehören in Anwendungscode oder eigenständige Pakete. [Migration](../migration/modular-monolith.md) beschreibt den Wechsel.

Strata ist eine mögliche spätere Bibliothek. Diese Änderung erstellt keine Strata-Implementierung und keine gegenseitige Paketabhängigkeit.
