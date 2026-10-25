# Sillbeam architecture report

Sillbeam version: dev\-main

Checked at: 2026-10-25T08:58:00+00:00

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
| Portrait | Shared | Active | Catalog, AgeGroup, User |
| Shop \[office\] / AgeGroup | Private | Active | User |
| Shop \[shop\] / AgeGroup | Private | Active | User |
| User | Shared | Active | — |

### Projects

| Project | Configured hosts | Declared modules |
| --- | --- | --- |
| Shop \[office\] | office.example.test | Admin |
| Shop \[shop\] | shop.example.test | Portrait, Admin |

## Diagnoses & Resolutions

No architecture findings.

## Technical Evidence

### Catalog identities

These identities map the overview labels to original descriptor classes and files.

**Module: Admin**

- Class: ExampleShopDuplicateNames\\Module\\Admin\\AdminModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-97a002ae/duplicate\-names/src/Module/Admin/AdminModule.php

**Module: AgeGroup**

- Class: ExampleShopDuplicateNames\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-97a002ae/duplicate\-names/src/Module/AgeGroup/AgeGroupModule.php

**Module: Catalog**

- Class: ExampleShopDuplicateNames\\Module\\Catalog\\CatalogModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-97a002ae/duplicate\-names/src/Module/Catalog/CatalogModule.php

**Module: Portrait**

- Class: ExampleShopDuplicateNames\\Module\\Portrait\\PortraitModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-97a002ae/duplicate\-names/src/Module/Portrait/PortraitModule.php

**Module: Shop \[office\] / AgeGroup**

- Class: ExampleShopDuplicateNames\\Project\\Office\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-97a002ae/duplicate\-names/src/Project/Office/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopDuplicateNames\\Project\\Office\\ShopProject

**Module: Shop \[shop\] / AgeGroup**

- Class: ExampleShopDuplicateNames\\Project\\Shop\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-97a002ae/duplicate\-names/src/Project/Shop/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopDuplicateNames\\Project\\Shop\\ShopProject

**Module: User**

- Class: ExampleShopDuplicateNames\\Module\\User\\UserModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-97a002ae/duplicate\-names/src/Module/User/UserModule.php

**Project: Shop \[office\]**

- Class: ExampleShopDuplicateNames\\Project\\Office\\ShopProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-97a002ae/duplicate\-names/src/Project/Office/ShopProject.php

**Project: Shop \[shop\]**

- Class: ExampleShopDuplicateNames\\Project\\Shop\\ShopProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-97a002ae/duplicate\-names/src/Project/Shop/ShopProject.php

Only Sillbeam architecture rules were checked. This report does not assess overall code quality, security or Symfony service-container health.
