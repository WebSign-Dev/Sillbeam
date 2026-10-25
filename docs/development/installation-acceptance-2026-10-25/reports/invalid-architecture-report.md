# Sillbeam architecture report

Sillbeam version: 0.2.0

Checked at: 2026-10-25T11:03:57+00:00

Status: violations

Analysis complete: yes

## Architecture Summary

Findings: 1 · Diagnoses: 1

Violations: 1 · Blockers: 0

Modules checked: 1 · Projects checked: 0

## Architecture Overview

Available declarations only: activation and graph validity are unknown. Declared edges below are evidence, not an approved runtime graph.

### Modules

| Module | Scope | State | Dependencies |
| --- | --- | --- | --- |
| Billing | Shared | Unknown | Missing \(unresolved\) |

## Diagnoses & Resolutions

Numbers identify groups in this check run only. Every original finding belongs to one diagnosis below.

### 1. Unknown module dependency

Billing → Missing \(unresolved\)

**Problem**

A declared module dependency was not discovered. Module: Billing. Target: Missing \(unresolved\).

**Why it matters**

Sillbeam needs the module descriptor to check this dependency and activate the module.

**Next steps**

- Check App\\Module\\Missing\\MissingModule in Billing/BillingModule.php. Make sure the descriptor is in a configured discovery directory and is not excluded. Correct the declaration or restore the descriptor, then rerun architecture analysis.

**Locations**

- Billing/BillingModule.php

## Technical Evidence

### Catalog identities

These identities map the overview labels to original descriptor classes and files.

**Module: Billing**

- Class: App\\Module\\Billing\\BillingModule
- Descriptor: /home/oeffe/PhpstormProjects/websign/test/sillbeam\-core\-v020\-acceptance\-20261025\-10834dcb/consumers/sf81\-A\-r2/src/Module/Billing/BillingModule.php

**Unresolved module: Missing \(unresolved\)**

- Reported class: App\\Module\\Missing\\MissingModule

### Diagnosis 1: Unknown module dependency

Evidence: declared\_dependency. 

**Original finding 1** — Rule: dependency.unknown (error)

Module "App\\Module\\Billing\\BillingModule" depends on unknown module class "App\\Module\\Missing\\MissingModule".

- file: /home/oeffe/PhpstormProjects/websign/test/sillbeam\-core\-v020\-acceptance\-20261025\-10834dcb/consumers/sf81\-A\-r2/src/Module/Billing/BillingModule.php
- module: App\\Module\\Billing\\BillingModule
- project: null
- target: App\\Module\\Missing\\MissingModule

Only Sillbeam architecture rules were checked. This report does not assess overall code quality, security or Symfony service-container health.
