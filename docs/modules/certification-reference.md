# Certification — Technical Reference

> Last updated: 2026-06-06  
> Changes: Removed references to the separate certificate templates table, actions, and policies.

Detailed structural and implementation reference for the **Certification** module.

---

## Overview

Manages certificate generation and credential tracking.

### Module Statistics

- **Actions**: 4 business logic operations
- **Models**: 1 data entity (`Certificate`)
- **Livewire Components**: 3 UI components
- **Policies**: 1 authorization rule
- **Submodules**: 1 module submodule

### Submodules

- **Certificate**: Digital certificates generation, PDF rendering, QR signing, and revocation.

---

## Dependency Graph

This module depends on:

- **Core** (base classes)
- **Enrollment** (registration records)
- **Reports** (Final Grade Card finalization check)
- **User** (recipient and issuer details)

---

## Actions

| File                                                  | Class                         | Extends      |
| ----------------------------------------------------- | ----------------------------- | ------------ |
| `Certificate/Actions/IssueCertificateAction.php`      | `IssueCertificateAction`      | `BaseAction` |
| `Certificate/Actions/BatchIssueCertificateAction.php` | `BatchIssueCertificateAction` | `BaseAction` |
| `Certificate/Actions/RevokeCertificateAction.php`     | `RevokeCertificateAction`     | `BaseAction` |
| `Certificate/Actions/CreateCertificateTemplateAction.php` | `CreateCertificateTemplateAction` | `BaseAction` |

---

## Models

| File                                 | Class         |
| ------------------------------------ | ------------- |
| `Certificate/Models/Certificate.php` | `Certificate` |

---

## Livewire Components

| File                                           | Component             | Extends             |
| ---------------------------------------------- | --------------------- | ------------------- |
| `Certificate/Livewire/CertificateList.php`     | `CertificateList`     | `BaseRecordManager` |
| `Certificate/Livewire/StudentCertificates.php`      | `StudentCertificates`      | `Component`         |
| `Certificate/Livewire/CertificateTemplateManager.php` | `CertificateTemplateManager` | `Component`         |

---

## Authorization Policies

| File                                         | Policy              |
| -------------------------------------------- | ------------------- |
| `Certificate/Policies/CertificatePolicy.php` | `CertificatePolicy` |

---

## File Organization

```
app/Certification/
├──            ← Submodule roots
│   └── Certificate/
│       ├── Actions/
│       ├── Enums/
│       ├── Http/
│       │   └── Controllers/
│       ├── Livewire/
│       ├── Models/
│       ├── Policies/
│       └── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Certificate`
- **Business Logic (`app/`)**: Located in
  [app/Certification/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Certification/)
- **Routing (`routes/`)**:
  [routes/web/certification.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/certification.php)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/certification/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/certification/)
- **Testing (`tests/`)**: Feature `tests/Feature/Certification/`, Unit `tests/Unit/Certification/`

_For overview and business context, see [certification.md](certification.md)_
