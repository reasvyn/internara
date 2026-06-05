# Certification — Technical Reference

> Last updated: 2026-06-04
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Certification** module.

---

## Overview

Manages certificate generation and credential tracking

### Module Statistics
- **Actions**: 4 business logic operations
- **Models**: 2 data entities
- **Livewire Components**: 3 UI components
- **Policies**: 2 authorization rules
- **Submodules**: 1 module submodule

### Submodules
- `Certificate`

---

## Dependency Graph

This module depends on:
- **Core**
- **Enrollment**
- **Program**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Certificate/Actions/BatchIssueCertificateAction.php` | `BatchIssueCertificateAction` | `BaseAction` |
| `Certificate/Actions/CreateCertificateTemplateAction.php` | `CreateCertificateTemplateAction` | `BaseAction` |
| `Certificate/Actions/IssueCertificateAction.php` | `IssueCertificateAction` | `BaseAction` |
| `Certificate/Actions/RevokeCertificateAction.php` | `RevokeCertificateAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Certificate/Models/Certificate.php` | `Certificate` |
| `Certificate/Models/CertificateTemplate.php` | `CertificateTemplate` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Certificate/Livewire/CertificateList.php` | `CertificateList` | `BaseRecordManager` |
| `Certificate/Livewire/CertificateTemplateManager.php` | `CertificateTemplateManager` | `BaseRecordManager` |
| `Certificate/Livewire/StudentCertificates.php` | `StudentCertificates` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Certificate/Policies/CertificatePolicy.php` | `CertificatePolicy` |
| `Certificate/Policies/CertificateTemplatePolicy.php` | `CertificateTemplatePolicy` |

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

*For overview and business context, see [certification.md](certification.md)*
