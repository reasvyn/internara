# Partnership — API Reference
> Last updated: 2026-05-25
> Changes: docs: add user registration audit findings (UC1-UC7) to known-issues, remove resolved entries


Total: 19 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Partnership/Actions/CreateCompanyAction.php` | `CreateCompanyAction` | `BaseAction` | Creates a new company record |
| `Partnership/Actions/CreatePartnershipAction.php` | `CreatePartnershipAction` | `BaseAction` | Creates a partnership/MoU agreement |
| `Partnership/Actions/DeleteCompanyAction.php` | `DeleteCompanyAction` | `BaseAction` | Deletes a company record |
| `Partnership/Actions/DeletePartnershipAction.php` | `DeletePartnershipAction` | `BaseAction` | Deletes a partnership agreement |
| `Partnership/Actions/RenewPartnershipAction.php` | `RenewPartnershipAction` | `BaseAction` | Renews an expiring partnership |
| `Partnership/Actions/TerminatePartnershipAction.php` | `TerminatePartnershipAction` | `BaseAction` | Terminates an active partnership |
| `Partnership/Actions/UpdateCompanyAction.php` | `UpdateCompanyAction` | `BaseAction` | Updates a company's details |
| `Partnership/Actions/UpdatePartnershipAction.php` | `UpdatePartnershipAction` | `BaseAction` | Updates a partnership agreement |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Partnership/Entities/CompanyState.php` | `CompanyState` | `BaseEntity` | Read-only DTO for company state |
| `Partnership/Entities/PartnershipState.php` | `PartnershipState` | `BaseEntity` | Read-only DTO for partnership state |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Partnership/Enums/PartnershipStatus.php` | `PartnershipStatus` | `LabelEnum`, `StatusEnum` | Partnership lifecycle status |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Partnership/Livewire/CompanyManager.php` | `CompanyManager` | `BaseRecordManager` | CRUD manager for companies |
| `Partnership/Livewire/PartnershipManager.php` | `PartnershipManager` | `BaseRecordManager` | CRUD manager for partnership/MoU agreements |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Partnership/Models/Company.php` | `Company` | `BaseModel` | Eloquent model for companies |
| `Partnership/Models/Partnership.php` | `Partnership` | `BaseModel` | Eloquent model for partnership/MoU agreements |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Partnership/Policies/PartnershipPolicy.php` | `PartnershipPolicy` | `BasePolicy` | Authorization for partnership operations |
| `Partnership/Policies/CompanyPolicy.php` | `CompanyPolicy` | `BasePolicy` | Authorization for company operations |

## Forms

| File | Class | Extends | Description |
|---|---|---|---|
| `Partnership/Livewire/Forms/CompanyForm.php` | `CompanyForm` | `Form` | Form object for company CRUD |
| `Partnership/Livewire/Forms/PartnershipForm.php` | `PartnershipForm` | `Form` | Form object for partnership CRUD |

## Where to Find It

- `app/Domain/Partnership/Models/`
- `app/Domain/Partnership/Actions/`
