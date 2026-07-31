# Certification — Technical Reference

> **Last updated:** 2026-07-31 **Changes:** sync — fix CertificateStatus interface, add CertificateIssued event, template migration/factory, Livewire extends, route names, test paths

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
| `Certificate/Enums/CertificateStatus.php` | `CertificateStatus` | `StatusEnum` | issued, revoked |

---

## Events

| File                                                  | Event                | Dispatched By          |
| ----------------------------------------------------- | -------------------- | ---------------------- |
| `Certificate/Events/CertificateIssued.php`            | `CertificateIssued`  | `IssueCertificateAction` |

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
| `Certificate/Livewire/CertificateTemplateManager.php` | `CertificateTemplateManager` | `BaseRecordManager` |

## Support

| File                                           | Class                 | Purpose                  |
| ---------------------------------------------- | --------------------- | ------------------------ |
| `Certificate/Services/CertificateRenderer.php` | `CertificateRenderer` | Renders certificate PDFs |

---

## Routes

File: `routes/web/certification.php` Named routes: `certificates.download`,
`student.certificates`, `sysadmin.certificates`, `sysadmin.certificates.templates`

## Views

Views are located in `resources/views/certification/`. See [UI/UX](../foundation/ui-ux.md) for the
design system.

## Tests

Tests are located in `tests/Certification/`. See
[Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory                    | Model                 |
| -------------------------- | --------------------- |
| `CertificateFactory`       | `Certificate`         |
| `CertificateTemplateFactory` | `CertificateTemplate` |

## Migrations

| Migration                          | Table                 |
| ---------------------------------- | --------------------- |
| `create_certificates_table`        | `certificates`        |
| `create_certificate_templates_table` | `certificate_templates` |

---

## Architectural Integration

- **Submodules**: `Certificate`
- **Business Logic**: `app/Certification/`
- **Routing**: `routes/web/certification.php`
- **Views**: `resources/views/certification/`
- **Testing**: `tests/Certification/`
- **Dependencies**: User, Evaluation, Program, Core

_For overview and business context, see [certification.md](certification.md)._
