# Enrollment — Technical Reference

> **Last updated:** 2026-06-17
> **Changes:** sync — add Livewire Components and Forms sections (14 files)

## Description
Detailed structural and implementation reference for the **Enrollment** module.

---


## Overview

Manages student registration, placement slot assignment, placement change requests, account applications, and registration document uploads.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Registration/Actions/ReadRegistrationAvailabilityAction.php` | `ReadRegistrationAvailabilityAction` | `BaseReadAction` |
| `Registration/Actions/RegisterInternshipAction.php` | `RegisterInternshipAction` | Process `BaseAction` |
| `Registration/Actions/VerifyRegistrationAction.php` | `VerifyRegistrationAction` | `BaseCommandAction` |
| `Registration/Actions/UploadRegistrationDocumentAction.php` | `UploadRegistrationDocumentAction` | `BaseCommandAction` |
| `Placement/Actions/CreatePlacementAction.php` | `CreatePlacementAction` | `BaseCommandAction` |
| `Placement/Actions/UpdatePlacementAction.php` | `UpdatePlacementAction` | `BaseCommandAction` |
| `Placement/Actions/DeletePlacementAction.php` | `DeletePlacementAction` | `BaseCommandAction` |
| `Placement/Actions/DirectPlacementAction.php` | `DirectPlacementAction` | `BaseCommandAction` |
| `Placement/Actions/RequestPlacementChangeAction.php` | `RequestPlacementChangeAction` | `BaseCommandAction` |
| `Placement/Actions/ApprovePlacementChangeAction.php` | `ApprovePlacementChangeAction` | `BaseCommandAction` |
| `Placement/Actions/RejectPlacementChangeAction.php` | `RejectPlacementChangeAction` | `BaseCommandAction` |
| `AccountApplication/Actions/ApplyAccountAction.php` | `ApplyAccountAction` | `BaseCommandAction` |
| `AccountApplication/Actions/ApproveAccountApplicationAction.php` | `ApproveAccountApplicationAction` | `BaseCommandAction` |
| `AccountApplication/Actions/RejectAccountApplicationAction.php` | `RejectAccountApplicationAction` | `BaseCommandAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Registration/Models/Registration.php` | `Registration` | `BaseModel` |
| `Registration/Models/RegistrationDocument.php` | `RegistrationDocument` | `BaseModel` |
| `Placement/Models/Placement.php` | `Placement` | `BaseModel` |
| `Placement/Models/PlacementChangeRequest.php` | `PlacementChangeRequest` | `BaseModel` |
| `AccountApplication/Models/AccountApplication.php` | `AccountApplication` | `BaseModel` |

---

## Data / DTOs

| File | Class | Extends |
| ---- | ----- | ------- |
| `Registration/Data/RegistrationData.php` | `RegistrationData` | `BaseData` |

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Registration/Entities/RegistrationState.php` | `RegistrationState` | `BaseEntity` |
| `Placement/Entities/PlacementState.php` | `PlacementState` | `BaseEntity` |
| `Placement/Entities/PlacementCapacity.php` | `PlacementCapacity` | `BaseEntity` |

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Registration/Enums/RegistrationDocumentStatus.php` | `RegistrationDocumentStatus` | `LabelEnum`, `StatusEnum` | pending, verified, rejected |
| `Placement/Enums/PlacementChangeStatus.php` | `PlacementChangeStatus` | `LabelEnum`, `StatusEnum` | pending, approved, rejected |
| `AccountApplication/Enums/AccountApplicationStatus.php` | `AccountApplicationStatus` | `LabelEnum`, `StatusEnum` | pending, approved, rejected |

## Events

| File | Class | Dispatched By |
| ---- | ----- | ------------- |
| `Registration/Events/StudentRegistered.php` | `StudentRegistered` | `RegisterInternshipAction` |

## Listeners

| File | Class | Listens To |
| ---- | ----- | ---------- |
| `Registration/Listeners/ClearDashboardOnRegistration.php` | `ClearDashboardOnRegistration` | `StudentRegistered` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Registration/Livewire/RegistrationCenter.php` | `RegistrationCenter` | `Component` |
| `Registration/Livewire/RegistrationDocumentUpload.php` | `RegistrationDocumentUpload` | `Component` |
| `Registration/Livewire/RegistrationVerification.php` | `RegistrationVerification` | `Component` |
| `Registration/Livewire/RegistrationWizard.php` | `RegistrationWizard` | `Component` |
| `Placement/Livewire/DirectPlacementManager.php` | `DirectPlacementManager` | `Component` |
| `Placement/Livewire/PlacementChangeManager.php` | `PlacementChangeManager` | `Component` |
| `Placement/Livewire/PlacementIndex.php` | `PlacementIndex` | `Component` |
| `Placement/Livewire/StudentPlacementChangeRequest.php` | `StudentPlacementChangeRequest` | `Component` |
| `AccountApplication/Livewire/ApplyPage.php` | `ApplyPage` | `Component` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Registration/Livewire/Forms/RegistrationWizardForm.php` | `RegistrationWizardForm` |
| `Placement/Livewire/Forms/DirectPlacementForm.php` | `DirectPlacementForm` |
| `Placement/Livewire/Forms/PlacementChangeForm.php` | `PlacementChangeForm` |
| `Placement/Livewire/Forms/PlacementForm.php` | `PlacementForm` |
| `AccountApplication/Livewire/Forms/AccountApplicationForm.php` | `AccountApplicationForm` |

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Registration/Policies/RegistrationPolicy.php` | `RegistrationPolicy` | `BasePolicy` |
| `Registration/Policies/RegistrationDocumentPolicy.php` | `RegistrationDocumentPolicy` | `BasePolicy` |
| `Placement/Policies/PlacementPolicy.php` | `PlacementPolicy` | `BasePolicy` |
| `Placement/Policies/PlacementChangeRequestPolicy.php` | `PlacementChangeRequestPolicy` | `BasePolicy` |
| `AccountApplication/Policies/AccountApplicationPolicy.php` | `AccountApplicationPolicy` | `BasePolicy` |

---

## Routes

File: `routes/web/enrollment.php`
Naming pattern: `enrollment.{resource}.{action}`

## Views

Views are located in `resources/views/enrollment/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Enrollment/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `RegistrationFactory` | `Registration` |
| `RegistrationDocumentFactory` | `RegistrationDocument` |
| `PlacementFactory` | `Placement` |
| `PlacementChangeRequestFactory` | `PlacementChangeRequest` |
| `AccountApplicationFactory` | `AccountApplication` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_registrations_table` | `registrations` |
| `create_registration_documents_table` | `registration_documents` |
| `create_placements_table` | `placements` |
| `create_placement_change_requests_table` | `placement_change_requests` |
| `create_account_applications_table` | `account_applications` |

---

---

## Architectural Integration

- **Submodules**: `Registration`, `Placement`, `AccountApplication`
- **Business Logic**: `app/Enrollment/`
- **Routing**: `routes/web/enrollment.php`
- **Views**: `resources/views/enrollment/`
- **Testing**: `tests/Feature/Enrollment/`, `tests/Unit/Enrollment/`
- **Dependencies**: Core, Program, Partners, User
- **Events Consumed By**: `User/Dashboard` (cache invalidation)

*For overview and business context, see [enrollment.md](enrollment.md).*