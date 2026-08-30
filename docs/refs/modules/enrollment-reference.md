# Enrollment — Technical Reference

## Description

Detailed structural and implementation reference for the **Enrollment** module.

---

## Overview

Manages student registration, placement slot assignment, placement change requests, account
applications, and registration document uploads.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/AccountApplication/Actions/ApplyAccountAction.php` | `ApplyAccountAction` | `BaseCommandAction` |
| `Domain/AccountApplication/Actions/ApproveAccountApplicationAction.php` | `ApproveAccountApplicationAction` | `BaseCommandAction` |
| `Domain/AccountApplication/Actions/RejectAccountApplicationAction.php` | `RejectAccountApplicationAction` | `BaseCommandAction` |
| `Domain/Placement/Actions/ApprovePlacementChangeAction.php` | `ApprovePlacementChangeAction` | `BaseCommandAction` |
| `Domain/Placement/Actions/CreatePlacementAction.php` | `CreatePlacementAction` | `BaseCommandAction` |
| `Domain/Placement/Actions/DeletePlacementAction.php` | `DeletePlacementAction` | `BaseCommandAction` |
| `Domain/Placement/Actions/DirectPlacementAction.php` | `DirectPlacementAction` | `BaseCommandAction` |
| `Domain/Placement/Actions/RejectPlacementChangeAction.php` | `RejectPlacementChangeAction` | `BaseCommandAction` |
| `Domain/Placement/Actions/RequestPlacementChangeAction.php` | `RequestPlacementChangeAction` | `BaseCommandAction` |
| `Domain/Placement/Actions/UpdatePlacementAction.php` | `UpdatePlacementAction` | `BaseCommandAction` |
| `Domain/Registration/Actions/ReadRegistrationAvailabilityAction.php` | `ReadRegistrationAvailabilityAction` | `BaseReadAction` |
| `Domain/Registration/Actions/RegisterInternshipAction.php` | `RegisterInternshipAction` | `BaseCommandAction` |
| `Domain/Registration/Actions/UploadRegistrationDocumentAction.php` | `UploadRegistrationDocumentAction` | `BaseCommandAction` |
| `Domain/Registration/Actions/VerifyRegistrationAction.php` | `VerifyRegistrationAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/AccountApplication/Models/AccountApplication.php` | `AccountApplication` | `BaseModel` |
| `Domain/Placement/Models/Placement.php` | `Placement` | `BaseModel` |
| `Domain/Registration/Models/Registration.php` | `Registration` | `BaseModel` |
| `Domain/Registration/Models/RegistrationDocument.php` | `RegistrationDocument` | `BaseModel` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/AccountApplication/Data/RejectAccountApplicationData.php` | `RejectAccountApplicationData` | `BaseData` |
| `Domain/Registration/Data/RegistrationData.php` | `RegistrationData` | `BaseData` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/AccountApplication/Enums/AccountApplicationStatus.php` | `AccountApplicationStatus` | `LabelEnum` | — |
| `Domain/Placement/Enums/PlacementChangeStatus.php` | `PlacementChangeStatus` | `LabelEnum` | — |
| `Domain/Registration/Enums/RegistrationDocumentStatus.php` | `RegistrationDocumentStatus` | `LabelEnum` | — |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/AccountApplication/Events/AccountApplicationApproved.php` | `AccountApplicationApproved` | `BaseEvent` |
| `Domain/AccountApplication/Events/AccountApplicationRejected.php` | `AccountApplicationRejected` | `BaseEvent` |
| `Domain/Registration/Events/StudentRegistered.php` | `StudentRegistered` | `BaseEvent` |

## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Domain/Registration/Listeners/ClearDashboardOnRegistration.php` | `ClearDashboardOnRegistration` | — |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/AccountApplication/Livewire/ApplyPage.php` | `ApplyPage` | `Component` |
| `Domain/AccountApplication/Livewire/Forms/AccountApplicationForm.php` | `AccountApplicationForm` | `BaseFormView` |
| `Domain/Placement/Livewire/DirectPlacementManager.php` | `DirectPlacementManager` | `BaseRecordManager` |
| `Domain/Placement/Livewire/Forms/DirectPlacementForm.php` | `DirectPlacementForm` | `BaseFormView` |
| `Domain/Placement/Livewire/Forms/PlacementChangeForm.php` | `PlacementChangeForm` | `BaseFormView` |
| `Domain/Placement/Livewire/Forms/PlacementForm.php` | `PlacementForm` | `BaseFormView` |
| `Domain/Placement/Livewire/PlacementChangeManager.php` | `PlacementChangeManager` | `BaseRecordManager` |
| `Domain/Placement/Livewire/PlacementIndex.php` | `PlacementIndex` | `BaseRecordManager` |
| `Domain/Placement/Livewire/StudentPlacementChangeRequest.php` | `StudentPlacementChangeRequest` | `Component` |
| `Domain/Registration/Livewire/Forms/RegistrationWizardForm.php` | `RegistrationWizardForm` | `BaseFormView` |
| `Domain/Registration/Livewire/RegistrationCenter.php` | `RegistrationCenter` | `Component` |
| `Domain/Registration/Livewire/RegistrationDocumentUpload.php` | `RegistrationDocumentUpload` | `Component` |
| `Domain/Registration/Livewire/RegistrationVerification.php` | `RegistrationVerification` | `Component` |
| `Domain/Registration/Livewire/RegistrationWizard.php` | `RegistrationWizard` | `Component` |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/AccountApplication/Policies/AccountApplicationPolicy.php` | `AccountApplicationPolicy` | `BasePolicy` |
| `Domain/Placement/Policies/PlacementChangeRequestPolicy.php` | `PlacementChangeRequestPolicy` | `BasePolicy` |
| `Domain/Placement/Policies/PlacementPolicy.php` | `PlacementPolicy` | `BasePolicy` |
| `Domain/Registration/Policies/RegistrationDocumentPolicy.php` | `RegistrationDocumentPolicy` | `BasePolicy` |
| `Domain/Registration/Policies/RegistrationPolicy.php` | `RegistrationPolicy` | `BasePolicy` |

## Routes

File: `routes/web/enrollment.php` Named routes: `apply`, `registration.center`,
`registration.wizard`, `registration.documents`, `student.internships.placement-change`,
`enrollment.internships.registrations.pending`, `enrollment.internships.placements`,
`enrollment.internships.placements.direct`, `enrollment.internships.placements.changes`

## Views

Views are located in `resources/views/enrollment/`. See [UI/UX](../../guides/ui-ux.md) for the
design system.

## Tests

Tests are located in `tests/Enrollment/`. See [Testing](../../guides/infra/testing.md) for the
testing conventions.

## Factories

| Factory                         | Model                    |
| ------------------------------- | ------------------------ |
| `RegistrationFactory`           | `Registration`           |
| `RegistrationDocumentFactory`   | `RegistrationDocument`   |
| `PlacementFactory`              | `Placement`              |
| `PlacementChangeRequestFactory` | `PlacementChangeRequest` |
| `AccountApplicationFactory`     | `AccountApplication`     |

## Migrations

| Migration                                | Table                       |
| ---------------------------------------- | --------------------------- |
| `create_registrations_table`             | `registrations`             |
| `create_registration_documents_table`    | `registration_documents`    |
| `create_placements_table`                | `placements`                |
| `create_placement_change_requests_table` | `placement_change_requests` |
| `create_account_applications_table`      | `account_applications`      |

---

## Architectural Integration

- **Submodules**: `Registration`, `Placement`, `AccountApplication`
- **Business Logic**: `app/Modules/Enrollment/`
- **Routing**: `routes/web/enrollment.php`
- **Views**: `resources/views/enrollment/`
- **Testing**: `tests/Enrollment/`
- **Dependencies**: Core, Program, Partners, User
- **Events Consumed By**: `User/Dashboard` (cache invalidation)

_For overview and business context, see [enrollment.md](enrollment.md)._
