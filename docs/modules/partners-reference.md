# Partners — Technical Reference

> **Last updated:** 2026-07-11 **Changes:** sync — initial metadata sync with new format

## Description

Detailed structural and implementation reference for the **Partners** module.

---

## Overview

Manages industrial partner companies and partnership agreements for internship placements.

## Actions

| File                                                   | Class                          | Extends             |
| ------------------------------------------------------ | ------------------------------ | ------------------- |
| `Company/Actions/CreateCompanyAction.php`              | `CreateCompanyAction`          | `BaseCommandAction` |
| `Company/Actions/UpdateCompanyAction.php`              | `UpdateCompanyAction`          | `BaseCommandAction` |
| `Company/Actions/DeleteCompanyAction.php`              | `DeleteCompanyAction`          | `BaseCommandAction` |
| `Company/Actions/BatchDeleteCompanyAction.php`         | `BatchDeleteCompanyAction`     | `BaseCommandAction` |
| `Partnership/Actions/CreatePartnershipAction.php`      | `CreatePartnershipAction`      | `BaseCommandAction` |
| `Partnership/Actions/UpdatePartnershipAction.php`      | `UpdatePartnershipAction`      | `BaseCommandAction` |
| `Partnership/Actions/DeletePartnershipAction.php`      | `DeletePartnershipAction`      | `BaseCommandAction` |
| `Partnership/Actions/RenewPartnershipAction.php`       | `RenewPartnershipAction`       | `BaseCommandAction` |
| `Partnership/Actions/TerminatePartnershipAction.php`   | `TerminatePartnershipAction`   | `BaseCommandAction` |
| `Partnership/Actions/BatchDeletePartnershipAction.php` | `BatchDeletePartnershipAction` | `BaseCommandAction` |

---

## Models

| File                                 | Class         | Extends     |
| ------------------------------------ | ------------- | ----------- |
| `Company/Models/Company.php`         | `Company`     | `BaseModel` |
| `Partnership/Models/Partnership.php` | `Partnership` | `BaseModel` |

---

## Enums

| File                                      | Enum                | Implements                | Values                      |
| ----------------------------------------- | ------------------- | ------------------------- | --------------------------- |
| `Partnership/Enums/PartnershipStatus.php` | `PartnershipStatus` | `LabelEnum`, `StatusEnum` | active, expired, terminated |

---

## Data / DTOs

| File                                   | Class             | Extends    |
| -------------------------------------- | ----------------- | ---------- |
| `Company/Data/CompanyData.php`         | `CompanyData`     | `BaseData` |
| `Partnership/Data/PartnershipData.php` | `PartnershipData` | `BaseData` |

## Events

| File                                           | Class                   | Dispatched By                |
| ---------------------------------------------- | ----------------------- | ---------------------------- |
| `Company/Events/CompanyCreated.php`            | `CompanyCreated`        | `CreateCompanyAction`        |
| `Company/Events/CompanyUpdated.php`            | `CompanyUpdated`        | `UpdateCompanyAction`        |
| `Company/Events/CompanyDeleted.php`            | `CompanyDeleted`        | `DeleteCompanyAction`        |
| `Partnership/Events/PartnershipCreated.php`    | `PartnershipCreated`    | `CreatePartnershipAction`    |
| `Partnership/Events/PartnershipUpdated.php`    | `PartnershipUpdated`    | `UpdatePartnershipAction`    |
| `Partnership/Events/PartnershipDeleted.php`    | `PartnershipDeleted`    | `DeletePartnershipAction`    |
| `Partnership/Events/PartnershipRenewed.php`    | `PartnershipRenewed`    | `RenewPartnershipAction`     |
| `Partnership/Events/PartnershipTerminated.php` | `PartnershipTerminated` | `TerminatePartnershipAction` |

## Listeners

| File                                                  | Class                           | Listens To       |
| ----------------------------------------------------- | ------------------------------- | ---------------- |
| `Company/Listeners/ClearDashboardOnCompanyChange.php` | `ClearDashboardOnCompanyChange` | `CompanyCreated` |

## Entities

| File                                        | Class              | Extends      |
| ------------------------------------------- | ------------------ | ------------ |
| `Company/Entities/CompanyState.php`         | `CompanyState`     | `BaseEntity` |
| `Partnership/Entities/PartnershipState.php` | `PartnershipState` | `BaseEntity` |

---

## Policies

| File                                         | Policy              | Extends      |
| -------------------------------------------- | ------------------- | ------------ |
| `Company/Policies/CompanyPolicy.php`         | `CompanyPolicy`     | `BasePolicy` |
| `Partnership/Policies/PartnershipPolicy.php` | `PartnershipPolicy` | `BasePolicy` |

---

## Livewire Components

| File                                          | Component            | Extends             |
| --------------------------------------------- | -------------------- | ------------------- |
| `Company/Livewire/CompanyManager.php`         | `CompanyManager`     | `BaseRecordManager` |
| `Partnership/Livewire/PartnershipManager.php` | `PartnershipManager` | `BaseRecordManager` |

## Livewire Forms

| File                                             | Form              |
| ------------------------------------------------ | ----------------- |
| `Company/Livewire/Forms/CompanyForm.php`         | `CompanyForm`     |
| `Partnership/Livewire/Forms/PartnershipForm.php` | `PartnershipForm` |

---

## Routes

File: `routes/web/partners.php` Naming pattern: `partners.{resource}.{action}`

## Views

Views are located in `resources/views/partners/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/{Feature,Unit}/Partners/`. See [Testing](../infrastructure/testing.md)
for the testing conventions.

## Factories

| Factory              | Model         |
| -------------------- | ------------- |
| `CompanyFactory`     | `Company`     |
| `PartnershipFactory` | `Partnership` |

## Migrations

| Migration                   | Table          |
| --------------------------- | -------------- |
| `create_companies_table`    | `companies`    |
| `create_partnerships_table` | `partnerships` |

---

## Architectural Integration

- **Submodules**: `Company`, `Partnership`
- **Business Logic**: `app/Partners/`
- **Routing**: `routes/web/partners.php`
- **Views**: `resources/views/partners/`
- **Testing**: `tests/Partners/`, `tests/Partners/`
- **Dependencies**: Core
- **Used By**: Program

_For overview and business context, see [partners.md](partners.md)._
