# Sillbeam architecture report

Sillbeam version: dev\-main

Checked at: 2026-10-25T08:27:53+00:00

Status: valid

Analysis complete: yes

## Architecture Summary

Findings: 0 · Diagnoses: 0

Violations: 0 · Blockers: 0

Modules checked: 20 · Projects checked: 2

## Architecture Overview

### Modules

| Module | Scope | State | Dependencies |
| --- | --- | --- | --- |
| AddressBook | Shared | Unassigned | — |
| Admin | Shared | Active | User, Catalog |
| AgeGroup | Shared | Active | User |
| Analytics | Shared | Unassigned | — |
| Catalog | Shared | Active | — |
| Delivery | Shared | Unassigned | — |
| Fulfilment | Shared | Unassigned | — |
| Inventory | Shared | Unassigned | — |
| Loyalty | Shared | Unassigned | — |
| Notifications | Shared | Unassigned | — |
| Office / AgeGroup | Private | Active | User |
| Payments | Shared | Disabled | — |
| Portrait | Shared | Active | Catalog, AgeGroup, User |
| Promotions | Shared | Unassigned | — |
| Returns | Shared | Unassigned | — |
| Shipping | Shared | Unassigned | — |
| Shop / AgeGroup | Private | Active | User |
| Taxation | Shared | Unassigned | — |
| User | Shared | Active | — |

#### InternationalPaymentReconciliationAndSettlementCoordinator

- Scope: Shared
- State: Unassigned
- Dependencies: 12 declared

#### Dependencies of InternationalPaymentReconciliationAndSettlementCoordinator

- Payments
- Inventory
- Fulfilment
- Returns
- Notifications
- Delivery
- Shipping
- Taxation
- AddressBook
- Loyalty
- Promotions
- Analytics

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

**Module: AddressBook**

- Class: ExampleShopLarge\\Module\\AddressBook\\AddressBookModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/AddressBook/AddressBookModule.php

**Module: Admin**

- Class: ExampleShopLarge\\Module\\Admin\\AdminModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Admin/AdminModule.php

**Module: AgeGroup**

- Class: ExampleShopLarge\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/AgeGroup/AgeGroupModule.php

**Module: Analytics**

- Class: ExampleShopLarge\\Module\\Analytics\\AnalyticsModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Analytics/AnalyticsModule.php

**Module: Catalog**

- Class: ExampleShopLarge\\Module\\Catalog\\CatalogModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Catalog/CatalogModule.php

**Module: Delivery**

- Class: ExampleShopLarge\\Module\\Delivery\\DeliveryModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Delivery/DeliveryModule.php

**Module: Fulfilment**

- Class: ExampleShopLarge\\Module\\Fulfilment\\FulfilmentModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Fulfilment/FulfilmentModule.php

**Module: InternationalPaymentReconciliationAndSettlementCoordinator**

- Class: ExampleShopLarge\\Module\\InternationalPaymentReconciliationAndSettlementCoordinator\\InternationalPaymentReconciliationAndSettlementCoordinatorModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/InternationalPaymentReconciliationAndSettlementCoordinator/InternationalPaymentReconciliationAndSettlementCoordinatorModule.php

**Module: Inventory**

- Class: ExampleShopLarge\\Module\\Inventory\\InventoryModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Inventory/InventoryModule.php

**Module: Loyalty**

- Class: ExampleShopLarge\\Module\\Loyalty\\LoyaltyModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Loyalty/LoyaltyModule.php

**Module: Notifications**

- Class: ExampleShopLarge\\Module\\Notifications\\NotificationsModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Notifications/NotificationsModule.php

**Module: Office / AgeGroup**

- Class: ExampleShopLarge\\Project\\Office\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Project/Office/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopLarge\\Project\\Office\\OfficeProject

**Module: Payments**

- Class: ExampleShopLarge\\Module\\Payments\\PaymentsModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Payments/PaymentsModule.php

**Module: Portrait**

- Class: ExampleShopLarge\\Module\\Portrait\\PortraitModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Portrait/PortraitModule.php

**Module: Promotions**

- Class: ExampleShopLarge\\Module\\Promotions\\PromotionsModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Promotions/PromotionsModule.php

**Module: Returns**

- Class: ExampleShopLarge\\Module\\Returns\\ReturnsModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Returns/ReturnsModule.php

**Module: Shipping**

- Class: ExampleShopLarge\\Module\\Shipping\\ShippingModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Shipping/ShippingModule.php

**Module: Shop / AgeGroup**

- Class: ExampleShopLarge\\Project\\Shop\\Module\\AgeGroup\\AgeGroupModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Project/Shop/Module/AgeGroup/AgeGroupModule.php
- Owning Project: ExampleShopLarge\\Project\\Shop\\ShopProject

**Module: Taxation**

- Class: ExampleShopLarge\\Module\\Taxation\\TaxationModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/Taxation/TaxationModule.php

**Module: User**

- Class: ExampleShopLarge\\Module\\User\\UserModule
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Module/User/UserModule.php

**Project: Office**

- Class: ExampleShopLarge\\Project\\Office\\OfficeProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Project/Office/OfficeProject.php

**Project: Shop**

- Class: ExampleShopLarge\\Project\\Shop\\ShopProject
- Descriptor: /tmp/sillbeam\-markdown\-examples\-b4544524/large/src/Project/Shop/ShopProject.php

Only Sillbeam architecture rules were checked. This report does not assess overall code quality, security or Symfony service-container health.
