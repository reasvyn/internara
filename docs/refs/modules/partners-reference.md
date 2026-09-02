# Partners — Technical Reference

## Description

Detailed structural and implementation reference for the **Partners** module.

---

## Overview

Manages industrial partner companies and partnership agreements for internship placements.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/Company/Actions/BatchDeleteCompanyAction.php` | `BatchDeleteCompanyAction` | `BaseCommandAction` |
| `Domain/Company/Actions/CreateCompanyAction.php` | `CreateCompanyAction` | `BaseCommandAction` |
| `Domain/Company/Actions/DeleteCompanyAction.php` | `DeleteCompanyAction` | `BaseCommandAction` |
| `Domain/Company/Actions/UpdateCompanyAction.php` | `UpdateCompanyAction` | `BaseCommandAction` |
| `Domain/Partnership/Actions/BatchDeletePartnershipAction.php` | `BatchDeletePartnershipAction` | `BaseCommandAction` |
| `Domain/Partnership/Actions/CreatePartnershipAction.php` | `CreatePartnershipAction` | `BaseCommandAction` |
| `Domain/Partnership/Actions/DeletePartnershipAction.php` | `DeletePartnershipAction` | `BaseCommandAction` |
| `Domain/Partnership/Actions/RenewPartnershipAction.php` | `RenewPartnershipAction` | `BaseCommandAction` |
| `Domain/Partnership/Actions/TerminatePartnershipAction.php` | `TerminatePartnershipAction` | `BaseCommandAction` |
| `Domain/Partnership/Actions/UpdatePartnershipAction.php` | `UpdatePartnershipAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/Company/Models/Company.php` | `Company` | `BaseModel` |
| `Domain/Partnership/Models/Partnership.php` | `Partnership` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/Partnership/Enums/PartnershipStatus.php` | `PartnershipStatus` | `LabelEnum` | — |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/Company/Data/CompanyData.php` | `CompanyData` | `BaseData` |
| `Domain/Partnership/Data/PartnershipData.php` | `PartnershipData` | `BaseData` |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/Company/Events/CompanyCreated.php` | `CompanyCreated` | `BaseEvent` |
| `Domain/Company/Events/CompanyDeleted.php` | `CompanyDeleted` | `BaseEvent` |
| `Domain/Company/Events/CompanyUpdated.php` | `CompanyUpdated` | `BaseEvent` |
| `Domain/Partnership/Events/PartnershipCreated.php` | `PartnershipCreated` | `BaseEvent` |
| `Domain/Partnership/Events/PartnershipDeleted.php` | `PartnershipDeleted` | `BaseEvent` |
| `Domain/Partnership/Events/PartnershipRenewed.php` | `PartnershipRenewed` | `BaseEvent` |
| `Domain/Partnership/Events/PartnershipTerminated.php` | `PartnershipTerminated` | `BaseEvent` |
| `Domain/Partnership/Events/PartnershipUpdated.php` | `PartnershipUpdated` | `BaseEvent` |

## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Domain/Company/Listeners/ClearDashboardOnCompanyChange.php` | `ClearDashboardOnCompanyChange` | — |
| `Domain/Partnership/Listeners/ClearDashboardOnPartnershipChange.php` | `ClearDashboardOnPartnershipChange` | — |
| `Domain/Partnership/Listeners/NotifyOnPartnershipTerminated.php` | `NotifyOnPartnershipTerminated` | — |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/Company/Policies/CompanyPolicy.php` | `CompanyPolicy` | `BasePolicy` |
| `Domain/Partnership/Policies/PartnershipPolicy.php` | `PartnershipPolicy` | `BasePolicy` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Company/Livewire/CompanyManager.php` | `CompanyManager` | `BaseRecordManager` |
| `Domain/Company/Livewire/Forms/CompanyForm.php` | `CompanyForm` | `BaseFormView` |
| `Domain/Partnership/Livewire/Forms/PartnershipForm.php` | `PartnershipForm` | `BaseFormView` |
| `Domain/Partnership/Livewire/PartnershipManager.php` | `PartnershipManager` | `BaseRecordManager` |

## Routes

File: `routes/web/partners.php` Naming pattern: `partners.companies`, `partners.partnerships`

## Views

Views are located in `resources/views/partners/`. See [UI/UX](../../guides/ui-ux/design-system.md) for the design
system.

## Tests

Tests are located in `tests/Partners/`. See [Testing](../../guides/infra/testing.md)
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
- **Business Logic**: `app/Modules/Partners/`
- **Routing**: `routes/web/partners.php`
- **Views**: `resources/views/partners/`
- **Testing**: `tests/Partners/`
- **Dependencies**: Core
- **Used By**: Program

_For overview and business context, see [partners.md](partners.md)._
