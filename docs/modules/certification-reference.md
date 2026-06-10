# Certification — Technical Reference

> Last updated: 2026-06-10

Detailed structural and implementation reference for the **Certification** module.

---

## Overview

Manages certificate generation, template management, and credential issuance for completed internships.

### Submodules

- `Certificate` — Certificate lifecycle, templates, and rendering

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Certificate/Actions/CreateCertificateTemplateAction.php` | `CreateCertificateTemplateAction` | `BaseAction` |
| `Certificate/Actions/IssueCertificateAction.php` | `IssueCertificateAction` | `BaseAction` |
| `Certificate/Actions/RevokeCertificateAction.php` | `RevokeCertificateAction` | `BaseAction` |
| `Certificate/Actions/BatchIssueCertificateAction.php` | `BatchIssueCertificateAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Certificate/Models/Certificate.php` | `Certificate` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Certificate/Enums/CertificateStatus.php` | `CertificateStatus` | `LabelEnum`, `StatusEnum` | draft, issued, revoked, expired |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Certificate/Policies/CertificatePolicy.php` | `CertificatePolicy` | `BasePolicy` |
| `Certificate/Policies/CertificateTemplatePolicy.php` | `CertificateTemplatePolicy` | `BasePolicy` |

---

## HTTP Controllers

| File | Controller | Extends |
| ---- | ---------- | ------- |
| `Certificate/Http/Controllers/CertificateDownloadController.php` | `CertificateDownloadController` | `BaseController` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Certificate/Livewire/CertificateList.php` | `CertificateList` | `BaseRecordManager` |
| `Certificate/Livewire/StudentCertificates.php` | `StudentCertificates` | `Component` |
| `Certificate/Livewire/CertificateTemplateManager.php` | `CertificateTemplateManager` | `Component` |

## Support

| File | Class | Purpose |
| ---- | ----- | ------- |
| `Certificate/Support/CertificateRenderer.php` | `CertificateRenderer` | Renders certificate PDFs |

---

## Routes

File: `routes/web/certification.php`
Naming pattern: `certification.{resource}.{action}`

## Views

Views are located in `resources/views/certification/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Certification/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `CertificateFactory` | `Certificate` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_certificates_table` | `certificates` |

---

## File Organization

```
app/Certification/
└── Certificate/
    ├── Actions/
    │   ├── BatchIssueCertificateAction.php
    │   ├── CreateCertificateTemplateAction.php
    │   ├── IssueCertificateAction.php
    │   └── RevokeCertificateAction.php
    ├── Enums/CertificateStatus.php
    ├── Http/Controllers/CertificateDownloadController.php
    ├── Livewire/
    │   ├── CertificateList.php
    │   ├── CertificateTemplateManager.php
    │   └── StudentCertificates.php
    ├── Models/Certificate.php
    ├── Policies/
    │   ├── CertificatePolicy.php
    │   └── CertificateTemplatePolicy.php
    └── Support/CertificateRenderer.php
```

---

## Architectural Integration

- **Submodules**: `Certificate`
- **Business Logic**: `app/Certification/`
- **Routing**: `routes/web/certification.php`
- **Views**: `resources/views/certification/`
- **Testing**: `tests/Feature/Certification/`, `tests/Unit/Certification/`
- **Dependencies**: User, Evaluation, Program, Core

*For overview and business context, see [certification.md](certification.md).*
