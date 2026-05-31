# Certificate — API Reference
> Last updated: 2026-05-31
> Changes: docs: audit — all items Implemented

> **Legend:** ✅ Implemented = code exists | ⏳ Planned = not yet implemented

Total: 14 files — ✅ 14 Implemented

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Certificate/Actions/BatchIssueCertificateAction.php` | `BatchIssueCertificateAction` | `BaseAction` | Issues certificates in batch for completed registrations |
| `Certificate/Actions/CreateCertificateTemplateAction.php` | `CreateCertificateTemplateAction` | `BaseAction` | Creates a certificate template |
| `Certificate/Actions/IssueCertificateAction.php` | `IssueCertificateAction` | `BaseAction` | Issues a single certificate with rendered PDF |
| `Certificate/Actions/RevokeCertificateAction.php` | `RevokeCertificateAction` | `BaseAction` | Revokes a previously issued certificate |

## Controllers

| File | Class | Extends | Description |
|---|---|---|---|
| `Certificate/Http/Controllers/CertificateDownloadController.php` | `CertificateDownloadController` | `BaseController` | Streams certificate PDF download |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Certificate/Enums/CertificateStatus.php` | `CertificateStatus` | `StatusEnum` | Certificate lifecycle status |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Certificate/Livewire/CertificateList.php` | `CertificateList` | `BaseRecordManager` | Manages certificate list with issue/revoke/batch actions |
| `Certificate/Livewire/CertificateTemplateManager.php` | `CertificateTemplateManager` | `BaseRecordManager` | CRUD manager for certificate templates |
| `Certificate/Livewire/StudentCertificates.php` | `StudentCertificates` | `Component` | Student-facing certificate list view |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Certificate/Models/Certificate.php` | `Certificate` | `BaseModel` | Eloquent model for issued certificates |
| `Certificate/Models/CertificateTemplate.php` | `CertificateTemplate` | `BaseModel` | Eloquent model for certificate templates |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Certificate/Policies/CertificatePolicy.php` | `CertificatePolicy` | `BasePolicy` | Authorization for certificate operations |
| `Certificate/Policies/CertificateTemplatePolicy.php` | `CertificateTemplatePolicy` | `BasePolicy` | Authorization for template operations |

## Support

| File | Class | Description |
|---|---|---|
| `Certificate/Support/CertificateRenderer.php` | `CertificateRenderer` | Renders certificate PDF using Blade + DomPDF |

## Where to Find It

- `app/Domain/Certificate/Models/`
- `app/Domain/Certificate/Actions/`

## Dependency Graph

```
Certificate Domain
├── Core         → BaseModel, BaseAction, SmartLogger
├── User         → User model (certificate recipient)
├── Registration → Registration records (completion context)
├── Internship   → Internship records (completion validation)
├── Document     → Document generation and storage
└── Assessment   → Assessment results (issuance criteria)
```

Consumed by:
  Mentee (download certificate), Internship (closure issuance)

