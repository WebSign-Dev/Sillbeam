# Sillbeam architecture report

Sillbeam version: dev\-main

Checked at: 2026-10-25T08:27:53+00:00

Status: valid

Analysis complete: yes

## Architecture Summary

Findings: 0 · Diagnoses: 0

Violations: 0 · Blockers: 0

Modules checked: 7 · Projects checked: 2

## Architecture Overview

### Modules

| Module | Scope | State | Dependencies |
| --- | --- | --- | --- |
| Admin | Shared | Active | User, Catalog |
| AgeGroup | Shared | Active | User |
| Catalog | Shared | Active | — |
| Office / AgeGroup | Private | Active | User |
| Portrait | Shared | Active | Catalog, AgeGroup, User |
| Shop / AgeGroup | Private | Active | User |
| User | Shared | Active | — |

### Projects

| Project | Configured hosts | Declared modules |
| --- | --- | --- |
| Office | office.example.test | Admin |
| Shop | shop.example.test | Portrait, Admin |

## Diagnoses & Resolutions

No architecture findings.

## Technical Evidence

### Catalog identities

These identities map the overview labels to original descriptor classes and files.

**Module: Admin**

- Class: ExampleShopValid\\Module\\Admin\\AdminModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/valid/src/Module/Admin/AdminModule.php

**Module: AgeGroup**

- Class: ExampleShopValid\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/valid/src/Module/AgeGroup/AgeGroupModule.php

**Module: Catalog**

- Class: ExampleShopValid\\Module\\Catalog\\CatalogModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/valid/src/Module/Catalog/CatalogModule.php

**Module: Office / AgeGroup**

- Class: ExampleShopValid\\Project\\Office\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/valid/src/Project/Office/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopValid\\Project\\Office\\OfficeProject

**Module: Portrait**

- Class: ExampleShopValid\\Module\\Portrait\\PortraitModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/valid/src/Module/Portrait/PortraitModule.php

**Module: Shop / AgeGroup**

- Class: ExampleShopValid\\Project\\Shop\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/valid/src/Project/Shop/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopValid\\Project\\Shop\\ShopProject

**Module: User**

- Class: ExampleShopValid\\Module\\User\\UserModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/valid/src/Module/User/UserModule.php

**Project: Office**

- Class: ExampleShopValid\\Project\\Office\\OfficeProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/valid/src/Project/Office/OfficeProject.php

**Project: Shop**

- Class: ExampleShopValid\\Project\\Shop\\ShopProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/valid/src/Project/Shop/ShopProject.php

Only Sillbeam architecture rules were checked. This report does not assess overall code quality, security or Symfony service-container health.
