# Sillbeam architecture report

Sillbeam version: dev\-main

Checked at: 2026-10-25T08:58:00+00:00

Status: unavailable

Analysis complete: no

## Architecture Summary

Findings: 1 · Diagnoses: 1

Violations: 0 · Blockers: 1

Modules checked: 0 · Projects checked: 0

**Analysis incomplete.** Counts reflect available findings and catalog entries only; undiscovered problems may remain.

## Architecture Overview

Available declarations only: activation and graph validity are unknown. Declared edges below are evidence, not an approved runtime graph.

No reliable catalog is available from this incomplete analysis.

## Diagnoses & Resolutions

Numbers identify groups in this check run only. Every original finding belongs to one diagnosis below.

### 1. PHP source cannot be parsed

Application

**Problem**

A source file contains a PHP syntax error. Reported error: Cannot parse Sillbeam source "src/Broken.php": Syntax error, unexpected '\{', expecting T\_STRING on line 1

**Why it matters**

Sillbeam cannot reliably read the classes and references in this file. Analysis incomplete: fix the blocking error and rerun before relying on dependencies or activation.

**Next steps**

- Fix the syntax error at the reported file/line using the original parser message. Rerun architecture analysis.

**Locations**

- src/Broken.php:1

## Technical Evidence

### Diagnosis 1: PHP source cannot be parsed

Evidence: finding. 

**Original finding 1** — Rule: source.parse (fatal)

Cannot parse Sillbeam source "/tmp/sillbeam\-markdown\-examples\-97a002ae/incomplete/src/Broken.php": Syntax error, unexpected '\{', expecting T\_STRING on line 1

- file: /tmp/sillbeam\-markdown\-examples\-97a002ae/incomplete/src/Broken.php
- line: 1

Only Sillbeam architecture rules were checked. This report does not assess overall code quality, security or Symfony service-container health.
