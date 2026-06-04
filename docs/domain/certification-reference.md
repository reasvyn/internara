# Certification — Technical Reference

> Last updated: 2026-06-04
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Certification** domain.

---

## Overview

Manages certificate generation and credential tracking

### Domain Statistics
- **Actions**: 4 business logic operations
- **Models**: 2 data entities
- **Livewire Components**: 3 UI components
- **Policies**: 2 authorization rules
- **Aggregates**: 1 domain aggregate

### Aggregates
- `Certificate`

---

## Dependency Graph

This domain depends on:
- **Core**
- **Enrollment**
- **Program**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/Certificate/Actions/BatchIssueCertificateAction.php` | `BatchIssueCertificateAction` | `BaseAction` |
| `Aggregates/Certificate/Actions/CreateCertificateTemplateAction.php` | `CreateCertificateTemplateAction` | `BaseAction` |
| `Aggregates/Certificate/Actions/IssueCertificateAction.php` | `IssueCertificateAction` | `BaseAction` |
| `Aggregates/Certificate/Actions/RevokeCertificateAction.php` | `RevokeCertificateAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Certificate/Models/Certificate.php` | `Certificate` |
| `Aggregates/Certificate/Models/CertificateTemplate.php` | `CertificateTemplate` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Certificate/Livewire/CertificateList.php` | `CertificateList` | `BaseRecordManager` |
| `Aggregates/Certificate/Livewire/CertificateTemplateManager.php` | `CertificateTemplateManager` | `BaseRecordManager` |
| `Aggregates/Certificate/Livewire/StudentCertificates.php` | `StudentCertificates` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Certificate/Policies/CertificatePolicy.php` | `CertificatePolicy` |
| `Aggregates/Certificate/Policies/CertificateTemplatePolicy.php` | `CertificateTemplatePolicy` |

---

## File Organization

```
app/Domain/Certification/
├── Aggregates/           ← Aggregate roots
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
