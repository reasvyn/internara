# Partners — Technical Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Complete technical reference for the Partners domain.

Detailed structural and implementation reference for the **Partners** domain.

---

## Overview

Manages partner companies and partnership agreements

### Domain Statistics
- **Actions**: 10 business logic operations
- **Models**: 2 data entities
- **Livewire Components**: 2 UI components
- **Policies**: 2 authorization rules
- **Aggregates**: 2 domain aggregates

### Aggregates
- `Company`
- `Partnership`

---

## Dependency Graph

This domain depends on:
- **Core**
- **Enrollment**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/Company/Actions/BatchDeleteCompanyAction.php` | `BatchDeleteCompanyAction` | `BaseAction` |
| `Aggregates/Partnership/Actions/BatchDeletePartnershipAction.php` | `BatchDeletePartnershipAction` | `BaseAction` |
| `Aggregates/Company/Actions/CreateCompanyAction.php` | `CreateCompanyAction` | `BaseAction` |
| `Aggregates/Partnership/Actions/CreatePartnershipAction.php` | `CreatePartnershipAction` | `BaseAction` |
| `Aggregates/Company/Actions/DeleteCompanyAction.php` | `DeleteCompanyAction` | `BaseAction` |
| `Aggregates/Partnership/Actions/DeletePartnershipAction.php` | `DeletePartnershipAction` | `BaseAction` |
| `Aggregates/Partnership/Actions/RenewPartnershipAction.php` | `RenewPartnershipAction` | `BaseAction` |
| `Aggregates/Partnership/Actions/TerminatePartnershipAction.php` | `TerminatePartnershipAction` | `BaseAction` |
| `Aggregates/Company/Actions/UpdateCompanyAction.php` | `UpdateCompanyAction` | `BaseAction` |
| `Aggregates/Partnership/Actions/UpdatePartnershipAction.php` | `UpdatePartnershipAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Company/Models/Company.php` | `Company` |
| `Aggregates/Partnership/Models/Partnership.php` | `Partnership` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Company/Livewire/CompanyManager.php` | `CompanyManager` | `BaseRecordManager` |
| `Aggregates/Partnership/Livewire/PartnershipManager.php` | `PartnershipManager` | `BaseRecordManager` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Company/Policies/CompanyPolicy.php` | `CompanyPolicy` |
| `Aggregates/Partnership/Policies/PartnershipPolicy.php` | `PartnershipPolicy` |

---

## File Organization

```
app/Domain/Partners/
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

*For overview and business context, see [partners.md](partners.md)*
