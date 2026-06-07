# Partners — Technical Reference

> Last updated: 2026-06-03 Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Partners** module.

---

## Overview

Manages partner companies and partnership agreements

### Module Statistics

- **Actions**: 10 business logic operations
- **Models**: 2 data entities
- **Livewire Components**: 2 UI components
- **Policies**: 2 authorization rules
- **Submodules**: 2 module submodules

### Submodules

- `Company`
- `Partnership`

---

## Dependency Graph

This module depends on:

- **Core**
- **Enrollment**
- **User**

---

## Actions

| File                                                   | Class                          | Extends      |
| ------------------------------------------------------ | ------------------------------ | ------------ |
| `Company/Actions/BatchDeleteCompanyAction.php`         | `BatchDeleteCompanyAction`     | `BaseAction` |
| `Partnership/Actions/BatchDeletePartnershipAction.php` | `BatchDeletePartnershipAction` | `BaseAction` |
| `Company/Actions/CreateCompanyAction.php`              | `CreateCompanyAction`          | `BaseAction` |
| `Partnership/Actions/CreatePartnershipAction.php`      | `CreatePartnershipAction`      | `BaseAction` |
| `Company/Actions/DeleteCompanyAction.php`              | `DeleteCompanyAction`          | `BaseAction` |
| `Partnership/Actions/DeletePartnershipAction.php`      | `DeletePartnershipAction`      | `BaseAction` |
| `Partnership/Actions/RenewPartnershipAction.php`       | `RenewPartnershipAction`       | `BaseAction` |
| `Partnership/Actions/TerminatePartnershipAction.php`   | `TerminatePartnershipAction`   | `BaseAction` |
| `Company/Actions/UpdateCompanyAction.php`              | `UpdateCompanyAction`          | `BaseAction` |
| `Partnership/Actions/UpdatePartnershipAction.php`      | `UpdatePartnershipAction`      | `BaseAction` |

---

## Models

| File                                 | Class         |
| ------------------------------------ | ------------- |
| `Company/Models/Company.php`         | `Company`     |
| `Partnership/Models/Partnership.php` | `Partnership` |

---

## Livewire Components

| File                                          | Component            | Extends             |
| --------------------------------------------- | -------------------- | ------------------- |
| `Company/Livewire/CompanyManager.php`         | `CompanyManager`     | `BaseRecordManager` |
| `Partnership/Livewire/PartnershipManager.php` | `PartnershipManager` | `BaseRecordManager` |

---

## Authorization Policies

| File                                         | Policy              |
| -------------------------------------------- | ------------------- |
| `Company/Policies/CompanyPolicy.php`         | `CompanyPolicy`     |
| `Partnership/Policies/PartnershipPolicy.php` | `PartnershipPolicy` |

---

## File Organization

```
app/Partners/
├──            ← Submodule roots
│   └── {SubModule}/
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

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Company`, `Partnership`
- **Business Logic (`app/`)**: Located in
  [app/Partners/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Partners/)
- **Routing (`routes/`)**:
  [routes/web/partners.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/partners.php)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/partners/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/partners/)
- **Testing (`tests/`)**: Feature `tests/Feature/Partners/`, Unit `tests/Unit/Partners/`

_For overview and business context, see [partners.md](partners.md)_
