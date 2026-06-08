# Partners — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **Partners** module.

---

## Overview

Manages industrial partner companies and partnership agreements for internship placements.

### Submodules

- `Company` — Partner company profiles
- `Partnership` — Partnership agreements and renewals

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Company/Actions/CreateCompanyAction.php` | `CreateCompanyAction` | `BaseAction` |
| `Company/Actions/UpdateCompanyAction.php` | `UpdateCompanyAction` | `BaseAction` |
| `Company/Actions/DeleteCompanyAction.php` | `DeleteCompanyAction` | `BaseAction` |
| `Company/Actions/BatchDeleteCompanyAction.php` | `BatchDeleteCompanyAction` | `BaseAction` |
| `Partnership/Actions/CreatePartnershipAction.php` | `CreatePartnershipAction` | `BaseAction` |
| `Partnership/Actions/UpdatePartnershipAction.php` | `UpdatePartnershipAction` | `BaseAction` |
| `Partnership/Actions/DeletePartnershipAction.php` | `DeletePartnershipAction` | `BaseAction` |
| `Partnership/Actions/RenewPartnershipAction.php` | `RenewPartnershipAction` | `BaseAction` |
| `Partnership/Actions/TerminatePartnershipAction.php` | `TerminatePartnershipAction` | `BaseAction` |
| `Partnership/Actions/BatchDeletePartnershipAction.php` | `BatchDeletePartnershipAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Company/Models/Company.php` | `Company` | `BaseModel` |
| `Partnership/Models/Partnership.php` | `Partnership` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Partnership/Enums/PartnershipStatus.php` | `PartnershipStatus` | `LabelEnum`, `StatusEnum` | active, expired, terminated, suspended |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Company/Entities/CompanyState.php` | `CompanyState` | `BaseEntity` |
| `Partnership/Entities/PartnershipState.php` | `PartnershipState` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Company/Policies/CompanyPolicy.php` | `CompanyPolicy` | `BasePolicy` |
| `Partnership/Policies/PartnershipPolicy.php` | `PartnershipPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Company/Livewire/CompanyManager.php` | `CompanyManager` | `BaseRecordManager` |
| `Partnership/Livewire/PartnershipManager.php` | `PartnershipManager` | `BaseRecordManager` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Company/Livewire/Forms/CompanyForm.php` | `CompanyForm` |
| `Partnership/Livewire/Forms/PartnershipForm.php` | `PartnershipForm` |

---

## Routes

File: `routes/web/partners.php`
Naming pattern: `partners.{resource}.{action}`

---

## File Organization

```
app/Partners/
├── Company/
│   ├── Actions/
│   │   ├── BatchDeleteCompanyAction.php
│   │   ├── CreateCompanyAction.php
│   │   ├── DeleteCompanyAction.php
│   │   └── UpdateCompanyAction.php
│   ├── Entities/CompanyState.php
│   ├── Livewire/
│   │   ├── Forms/CompanyForm.php
│   │   └── CompanyManager.php
│   ├── Models/Company.php
│   └── Policies/CompanyPolicy.php
└── Partnership/
    ├── Actions/
    │   ├── BatchDeletePartnershipAction.php
    │   ├── CreatePartnershipAction.php
    │   ├── DeletePartnershipAction.php
    │   ├── RenewPartnershipAction.php
    │   ├── TerminatePartnershipAction.php
    │   └── UpdatePartnershipAction.php
    ├── Entities/PartnershipState.php
    ├── Enums/PartnershipStatus.php
    ├── Livewire/
    │   ├── Forms/PartnershipForm.php
    │   └── PartnershipManager.php
    ├── Models/Partnership.php
    └── Policies/PartnershipPolicy.php
```

---

## Architectural Integration

- **Submodules**: `Company`, `Partnership`
- **Business Logic**: `app/Partners/`
- **Routing**: `routes/web/partners.php`
- **Views**: `resources/views/partners/`
- **Testing**: `tests/Feature/Partners/`, `tests/Unit/Partners/`
- **Dependencies**: Core
- **Used By**: Program, Guidance

*For overview and business context, see [partners.md](partners.md).*
