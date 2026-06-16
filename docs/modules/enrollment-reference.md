# Enrollment — Technical Reference

> **Last updated:** 2026-06-16

Detailed structural and implementation reference for the **Enrollment** module.

---

## Overview

Manages student registration, placement slot assignment, placement change requests, account applications, and registration document uploads.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Registration/Actions/ReadRegistrationAvailabilityAction.php` | `ReadRegistrationAvailabilityAction` | `BaseReadAction` |
| `Registration/Actions/RegisterInternshipAction.php` | `RegisterInternshipAction` | Process `BaseAction` |
| `Registration/Actions/VerifyRegistrationAction.php` | `VerifyRegistrationAction` | `BaseAction` |
| `Registration/Actions/UploadRegistrationDocumentAction.php` | `UploadRegistrationDocumentAction` | `BaseAction` |
| `Placement/Actions/CreatePlacementAction.php` | `CreatePlacementAction` | `BaseAction` |
| `Placement/Actions/UpdatePlacementAction.php` | `UpdatePlacementAction` | `BaseAction` |
| `Placement/Actions/DeletePlacementAction.php` | `DeletePlacementAction` | `BaseAction` |
| `Placement/Actions/DirectPlacementAction.php` | `DirectPlacementAction` | `BaseAction` |
| `Placement/Actions/RequestPlacementChangeAction.php` | `RequestPlacementChangeAction` | `BaseAction` |
| `Placement/Actions/ApprovePlacementChangeAction.php` | `ApprovePlacementChangeAction` | `BaseAction` |
| `Placement/Actions/RejectPlacementChangeAction.php` | `RejectPlacementChangeAction` | `BaseAction` |
| `AccountApplication/Actions/ApplyAccountAction.php` | `ApplyAccountAction` | `BaseAction` |
| `AccountApplication/Actions/ApproveAccountApplicationAction.php` | `ApproveAccountApplicationAction` | `BaseAction` |
| `AccountApplication/Actions/RejectAccountApplicationAction.php` | `RejectAccountApplicationAction` | `BaseAction` |

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