# Sillbeam architecture report

Sillbeam version: dev\-main

Checked at: 2026-10-25T08:27:53+00:00

Status: violations

Analysis complete: yes

## Architecture Summary

Findings: 3 · Diagnoses: 2

Violations: 3 · Blockers: 0

Modules checked: 7 · Projects checked: 2

## Architecture Overview

Available declarations only: activation and graph validity are unknown. Declared edges below are evidence, not an approved runtime graph.

### Modules

| Module | Scope | State | Dependencies |
| --- | --- | --- | --- |
| Admin | Shared | Unknown | User, Catalog |
| AgeGroup | Shared | Unknown | User |
| Catalog | Shared | Unknown | — |
| Office / AgeGroup | Private | Unknown | User |
| Portrait | Shared | Unknown | Catalog, AgeGroup, User |
| Shop / AgeGroup | Private | Unknown | User |
| User | Shared | Unknown | — |

### Projects

| Project | Configured hosts | Declared modules |
| --- | --- | --- |
| Office | office.example.test | Admin |
| Shop | shop.example.test | Portrait, Admin |

## Diagnoses & Resolutions

Numbers identify groups in this check run only. Every original finding belongs to one diagnosis below.

### 1. Application class cannot be resolved

User → MissingRenderer

**Problem**

A referenced application\-owned class cannot be resolved by the bounded source/Composer check. Reported class: MissingRenderer. Module: User.

**Why it matters**

The reference cannot be matched to indexed, loaded or mapped application source. This is not a check of all PHP autoloading, external Composer types or Symfony autowiring.

**Next steps**

- Check the reported class spelling/import, intended source file and application Composer PSR\-4 mapping; regenerate the autoloader if that mapping changed. Rerun architecture analysis after correcting the reported issue.

**Locations**

- User/Second.php:1
- User/UserModule.php

### 2. Module accesses are not declared

User → Catalog

**Problem**

Code crosses a module boundary without a declared direct dependency. Reported class: Item. Module: User. Target: Catalog.

**Why it matters**

A transitive dependency does not permit direct code access. Verify the declarations and reference evidence before changing the graph. The declared graph has no witnessed return path or private\-boundary blocker under complete evidence; this remains a domain choice.

**Next steps**

- Domain option: In User/UserModule.php, add Catalog::class to the SillbeamModule dependencies list if this coupling is intended. Rerun architecture analysis after the change; activation may change.

**Locations**

- User/Consumer.php:1
- User/Second.php:1
- User/UserModule.php
- Catalog/CatalogModule.php

## Technical Evidence

### Catalog identities

These identities map the overview labels to original descriptor classes and files.

**Module: Admin**

- Class: ExampleShopMultipleErrors\\Module\\Admin\\AdminModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/Admin/AdminModule.php

**Module: AgeGroup**

- Class: ExampleShopMultipleErrors\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/AgeGroup/AgeGroupModule.php

**Module: Catalog**

- Class: ExampleShopMultipleErrors\\Module\\Catalog\\CatalogModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/Catalog/CatalogModule.php

**Module: Office / AgeGroup**

- Class: ExampleShopMultipleErrors\\Project\\Office\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Project/Office/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopMultipleErrors\\Project\\Office\\OfficeProject

**Module: Portrait**

- Class: ExampleShopMultipleErrors\\Module\\Portrait\\PortraitModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/Portrait/PortraitModule.php

**Module: Shop / AgeGroup**

- Class: ExampleShopMultipleErrors\\Project\\Shop\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Project/Shop/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopMultipleErrors\\Project\\Shop\\ShopProject

**Module: User**

- Class: ExampleShopMultipleErrors\\Module\\User\\UserModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/User/UserModule.php

**Project: Office**

- Class: ExampleShopMultipleErrors\\Project\\Office\\OfficeProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Project/Office/OfficeProject.php

**Project: Shop**

- Class: ExampleShopMultipleErrors\\Project\\Shop\\ShopProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Project/Shop/ShopProject.php

**Referenced class: MissingRenderer**

- Reported class: ExampleShopMultipleErrors\\MissingRenderer

**Referenced class: Item**

- Reported class: ExampleShopMultipleErrors\\Module\\Catalog\\Item

### Diagnosis 1: Application class cannot be resolved

Evidence: finding. 

**Original finding 1** — Rule: code.unknown\_class (error)

Cannot resolve referenced class "ExampleShopMultipleErrors\\MissingRenderer".

- class: ExampleShopMultipleErrors\\MissingRenderer
- file: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/User/Second.php
- line: 1
- module: ExampleShopMultipleErrors\\Module\\User\\UserModule
- project: null

### Diagnosis 2: Module accesses are not declared

Evidence: observed\_reference. 

**Original finding 1** — Rule: code.undeclared\_dependency (error)

/tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/User/Consumer.php:1: Module "user" uses "ExampleShopMultipleErrors\\Module\\Catalog\\Item" from module "catalog": does not declare a direct dependency.

- class: ExampleShopMultipleErrors\\Module\\Catalog\\Item
- file: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/User/Consumer.php
- line: 1
- module: ExampleShopMultipleErrors\\Module\\User\\UserModule
- project: null
- target: ExampleShopMultipleErrors\\Module\\Catalog\\CatalogModule

**Original finding 2** — Rule: code.undeclared\_dependency (error)

/tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/User/Second.php:1: Module "user" uses "ExampleShopMultipleErrors\\Module\\Catalog\\Item" from module "catalog": does not declare a direct dependency.

- class: ExampleShopMultipleErrors\\Module\\Catalog\\Item
- file: /tmp/sillbeam\-markdown\-examples\-b4544524/multiple\-errors/src/Module/User/Second.php
- line: 1
- module: ExampleShopMultipleErrors\\Module\\User\\UserModule
- project: null
- target: ExampleShopMultipleErrors\\Module\\Catalog\\CatalogModule

Only Sillbeam architecture rules were checked. This report does not assess overall code quality, security or Symfony service-container health.
