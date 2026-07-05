# Certification — Technical Reference

> **Last updated:** 2026-07-05 **Changes:** sync — fix base class extends: BaseAction →
> BaseCommandAction/BaseReadAction

## Description

Detailed structural and implementation reference for the **Certification** module.

---

## Overview

Manages certificate generation, template management, and credential issuance for completed
internships.

## Actions

| File                                                      | Class                             | Extends             |
| --------------------------------------------------------- | --------------------------------- | ------------------- |
| `Certificate/Actions/CreateCertificateTemplateAction.php` | `CreateCertificateTemplateAction` | `BaseCommandAction` |
| `Certificate/Actions/IssueCertificateAction.php`          | `IssueCertificateAction`          | `BaseCommandAction` |
| `Certificate/Actions/RevokeCertificateAction.php`         | `RevokeCertificateAction`         | `BaseCommandAction` |
| `Certificate/Actions/BatchIssueCertificateAction.php`     | `BatchIssueCertificateAction`     | `BaseProcessAction` |

---

## Models

| File                                         | Class                 | Extends     |
| -------------------------------------------- | --------------------- | ----------- |
| `Certificate/Models/Certificate.php`         | `Certificate`         | `BaseModel` |
| `Certificate/Models/CertificateTemplate.php` | `CertificateTemplate` | `BaseModel` |

---

## Enums

| File                                      | Enum                | Implements                | Values          |
| ----------------------------------------- | ------------------- | ------------------------- | --------------- |
| `Certificate/Enums/CertificateStatus.php` | `CertificateStatus` | `LabelEnum`, `StatusEnum` | issued, revoked |

---

## Policies

| File                                                 | Policy                      | Extends      |
| ---------------------------------------------------- | --------------------------- | ------------ |
| `Certificate/Policies/CertificatePolicy.php`         | `CertificatePolicy`         | `BasePolicy` |
| `Certificate/Policies/CertificateTemplatePolicy.php` | `CertificateTemplatePolicy` | `BasePolicy` |

---

## HTTP Controllers

| File                                                             | Controller                      | Extends          |
| ---------------------------------------------------------------- | ------------------------------- | ---------------- |
| `Certificate/Http/Controllers/CertificateDownloadController.php` | `CertificateDownloadController` | `BaseController` |

## Livewire Components

| File                                                  | Component                    | Extends             |
| ----------------------------------------------------- | ---------------------------- | ------------------- |
| `Certificate/Livewire/CertificateList.php`            | `CertificateList`            | `BaseRecordManager` |
| `Certificate/Livewire/StudentCertificates.php`        | `StudentCertificates`        | `Component`         |
| `Certificate/Livewire/CertificateTemplateManager.php` | `CertificateTemplateManager` | `Component`         |

## Support

| File                                           | Class                 | Purpose                  |
| ---------------------------------------------- | --------------------- | ------------------------ |
| `Certificate/Services/CertificateRenderer.php` | `CertificateRenderer` | Renders certificate PDFs |

---

## Routes

File: `routes/web/certification.php` Naming pattern: `certification.{resource}.{action}`

## Views

Views are located in `resources/views/certification/`. See [UI/UX](../foundation/ui-ux.md) for the
design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Certification/`. See
[Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory              | Model         |
| -------------------- | ------------- |
| `CertificateFactory` | `Certificate` |

## Migrations

| Migration                   | Table          |
| --------------------------- | -------------- |
| `create_certificates_table` | `certificates` |

---

---

## Architectural Integration

- **Submodules**: `Certificate`
- **Business Logic**: `app/Certification/`
- **Routing**: `routes/web/certification.php`
- **Views**: `resources/views/certification/`
- **Testing**: `tests/Feature/Certification/`, `tests/Unit/Certification/`
- **Dependencies**: User, Evaluation, Program, Core

_For overview and business context, see [certification.md](certification.md)._
