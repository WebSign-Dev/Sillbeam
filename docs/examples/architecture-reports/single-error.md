# Sillbeam architecture report

Sillbeam version: dev\-main

Checked at: 2026-10-25T08:27:53+00:00

Status: violations

Analysis complete: yes

## Architecture Summary

Findings: 1 · Diagnoses: 1

Violations: 1 · Blockers: 0

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

### 1. Undeclared module access

User → Catalog

**Problem**

Code crosses a module boundary without a declared direct dependency. Reported class: Item. Module: User. Target: Catalog.

**Why it matters**

A transitive dependency does not permit direct code access. Verify the declarations and reference evidence before changing the graph. The declared graph has no witnessed return path or private\-boundary blocker under complete evidence; this remains a domain choice.

**Next steps**

- Domain option: In User/UserModule.php, add Catalog::class to the SillbeamModule dependencies list if this coupling is intended. Rerun architecture analysis after the change; activation may change.

**Locations**

- User/Consumer.php:1
- User/UserModule.php
- Catalog/CatalogModule.php

## Technical Evidence

### Catalog identities

These identities map the overview labels to original descriptor classes and files.

**Module: Admin**

- Class: ExampleShopSingleError\\Module\\Admin\\AdminModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Module/Admin/AdminModule.php

**Module: AgeGroup**

- Class: ExampleShopSingleError\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Module/AgeGroup/AgeGroupModule.php

**Module: Catalog**

- Class: ExampleShopSingleError\\Module\\Catalog\\CatalogModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Module/Catalog/CatalogModule.php

**Module: Office / AgeGroup**

- Class: ExampleShopSingleError\\Project\\Office\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Project/Office/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopSingleError\\Project\\Office\\OfficeProject

**Module: Portrait**

- Class: ExampleShopSingleError\\Module\\Portrait\\PortraitModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Module/Portrait/PortraitModule.php

**Module: Shop / AgeGroup**

- Class: ExampleShopSingleError\\Project\\Shop\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Project/Shop/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopSingleError\\Project\\Shop\\ShopProject

**Module: User**

- Class: ExampleShopSingleError\\Module\\User\\UserModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Module/User/UserModule.php

**Project: Office**

- Class: ExampleShopSingleError\\Project\\Office\\OfficeProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Project/Office/OfficeProject.php

**Project: Shop**

- Class: ExampleShopSingleError\\Project\\Shop\\ShopProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Project/Shop/ShopProject.php

**Referenced class: Item**

- Reported class: ExampleShopSingleError\\Module\\Catalog\\Item

### Diagnosis 1: Undeclared module access

Evidence: observed\_reference. 

**Original finding 1** — Rule: code.undeclared\_dependency (error)

/tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Module/User/Consumer.php:1: Module "user" uses "ExampleShopSingleError\\Module\\Catalog\\Item" from module "catalog": does not declare a direct dependency.

- class: ExampleShopSingleError\\Module\\Catalog\\Item
- file: /tmp/sillbeam\-markdown\-examples\-b4544524/single\-error/src/Module/User/Consumer.php
- line: 1
- module: ExampleShopSingleError\\Module\\User\\UserModule
- project: null
- target: ExampleShopSingleError\\Module\\Catalog\\CatalogModule

Only Sillbeam architecture rules were checked. This report does not assess overall code quality, security or Symfony service-container health.
