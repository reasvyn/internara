# Certification — Technical Reference

## Description

Detailed structural and implementation reference for the **Certification** module.

---

## Overview

Manages certificate generation, template management, and credential issuance for completed
internships.

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/DispatchBatchIssueCertificatesAction.php` | `DispatchBatchIssueCertificatesAction` | `BaseCommandAction` |
| `Domain/Certificate/Actions/BatchIssueCertificateAction.php` | `BatchIssueCertificateAction` | `BaseCommandAction` |
| `Domain/Certificate/Actions/CreateCertificateTemplateAction.php` | `CreateCertificateTemplateAction` | `BaseCommandAction` |
| `Domain/Certificate/Actions/IssueCertificateAction.php` | `IssueCertificateAction` | `BaseCommandAction` |
| `Domain/Certificate/Actions/RevokeCertificateAction.php` | `RevokeCertificateAction` | `BaseCommandAction` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Data/BatchIssueCertificatesData.php` | `BatchIssueCertificatesData` | `BaseData` |

## Jobs

| File                                 | Class                        | Queue     | Purpose                      |
| ------------------------------------ | ---------------------------- | --------- | ---------------------------- |
| `Jobs/BatchIssueCertificatesJob.php` | `BatchIssueCertificatesJob`  | `default` | Batch certificate issuance (8FVZA FR-BP2, queued via `DispatchBatchIssueCertificatesAction`) |

---

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/Certificate/Models/Certificate.php` | `Certificate` | `BaseModel` |
| `Domain/Certificate/Models/CertificateTemplate.php` | `CertificateTemplate` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/Certificate/Enums/CertificateStatus.php` | `CertificateStatus` | `LabelEnum` | — |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/Certificate/Events/CertificateIssued.php` | `CertificateIssued` | `BaseEvent` |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/Certificate/Policies/CertificatePolicy.php` | `CertificatePolicy` | `BasePolicy` |
| `Domain/Certificate/Policies/CertificateTemplatePolicy.php` | `CertificateTemplatePolicy` | `BasePolicy` |

## HTTP Controllers

| File | Controller | Extends |
|---|---|---|
| `Domain/Certificate/Http/Controllers/CertificateDownloadController.php` | `CertificateDownloadController` | `BaseController` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Certificate/Livewire/CertificateList.php` | `CertificateList` | `BaseRecordManager` |
| `Domain/Certificate/Livewire/CertificateTemplateManager.php` | `CertificateTemplateManager` | `BaseRecordManager` |
| `Domain/Certificate/Livewire/StudentCertificates.php` | `StudentCertificates` | `Component` |

## Support

| File                                           | Class                 | Purpose                  |
| ---------------------------------------------- | --------------------- | ------------------------ |
| `Certificate/Services/CertificateRenderer.php` | `CertificateRenderer` | Renders certificate PDFs |

---

## Routes

File: `routes/web/certification.php` Named routes: `certificates.download`,
`student.certificates`, `sysadmin.certificates`, `sysadmin.certificates.templates`

## Views

Views are located in `resources/views/certification/`. See [UI/UX](../../guides/ui-ux/design-system.md) for the
design system.

## Tests

Tests are located in `tests/Certification/`. See
[Testing](../../guides/infra/testing.md) for the testing conventions.

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
- **Business Logic**: `app/Modules/Certification/`
- **Routing**: `routes/web/certification.php`
- **Views**: `resources/views/certification/`
- **Testing**: `tests/Certification/`
- **Dependencies**: User, Evaluation, Program, Core

_For overview and business context, see [certification.md](certification.md)._
