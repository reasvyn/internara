# Certification — Technical Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Complete technical reference for the Certification domain.

Detailed structural and implementation reference for the **Certification** domain.

---

## Overview

Manages certificate generation, documents, and credential tracking

### Domain Statistics
- **Actions**: 8 business logic operations
- **Models**: 3 data entities
- **Livewire Components**: 5 UI components
- **Policies**: 3 authorization rules
- **Aggregates**: 2 domain aggregates

### Aggregates
- `Certificate`
- `Document`

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
| `Aggregates/Document/Actions/DeleteReportAction.php` | `DeleteReportAction` | `BaseAction` |
| `Aggregates/Document/Actions/GenerateReportAction.php` | `GenerateReportAction` | `BaseAction` |
| `Aggregates/Certificate/Actions/IssueCertificateAction.php` | `IssueCertificateAction` | `BaseAction` |
| `Aggregates/Document/Actions/RenderDocumentAction.php` | `RenderDocumentAction` | `BaseAction` |
| `Aggregates/Certificate/Actions/RevokeCertificateAction.php` | `RevokeCertificateAction` | `BaseAction` |
| `Aggregates/Document/Actions/SaveDocumentTemplateAction.php` | `SaveDocumentTemplateAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Certificate/Models/Certificate.php` | `Certificate` |
| `Aggregates/Certificate/Models/CertificateTemplate.php` | `CertificateTemplate` |
| `Aggregates/Document/Models/Document.php` | `Document` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Certificate/Livewire/CertificateList.php` | `CertificateList` | `BaseRecordManager` |
| `Aggregates/Certificate/Livewire/CertificateTemplateManager.php` | `CertificateTemplateManager` | `BaseRecordManager` |
| `Aggregates/Document/Livewire/ReportsManager.php` | `ReportsManager` | `Component` |
| `Aggregates/Certificate/Livewire/StudentCertificates.php` | `StudentCertificates` | `Component` |
| `Aggregates/Document/Livewire/TemplateManager.php` | `TemplateManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Certificate/Policies/CertificatePolicy.php` | `CertificatePolicy` |
| `Aggregates/Certificate/Policies/CertificateTemplatePolicy.php` | `CertificateTemplatePolicy` |
| `Aggregates/Document/Policies/DocumentPolicy.php` | `DocumentPolicy` |

---

## File Organization

```
app/Domain/Certification/
├── Aggregates/           ← Aggregate roots
│   └── {Aggregate}/
│       ├── Actions/
│       ├── Models/
│       ├── Policies/
│       └── Livewire/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

*For overview and business context, see [certification.md](certification.md)*
