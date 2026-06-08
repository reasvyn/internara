# Enrollment — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **Enrollment** module.

---

## Overview

Manages student registration, placement slot assignment, placement change requests, account applications, and registration document uploads.

### Submodules

None — all components are directly under `app/Enrollment/`.

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Actions/RegisterInternshipAction.php` | `RegisterInternshipAction` | Process `BaseAction` |
| `Actions/VerifyRegistrationAction.php` | `VerifyRegistrationAction` | `BaseAction` |
| `Actions/CreatePlacementAction.php` | `CreatePlacementAction` | `BaseAction` |
| `Actions/UpdatePlacementAction.php` | `UpdatePlacementAction` | `BaseAction` |
| `Actions/DeletePlacementAction.php` | `DeletePlacementAction` | `BaseAction` |
| `Actions/DirectPlacementAction.php` | `DirectPlacementAction` | `BaseAction` |
| `Actions/RequestPlacementChangeAction.php` | `RequestPlacementChangeAction` | `BaseAction` |
| `Actions/ApprovePlacementChangeAction.php` | `ApprovePlacementChangeAction` | `BaseAction` |
| `Actions/RejectPlacementChangeAction.php` | `RejectPlacementChangeAction` | `BaseAction` |
| `Actions/ApplyAccountAction.php` | `ApplyAccountAction` | `BaseAction` |
| `Actions/ApproveAccountApplicationAction.php` | `ApproveAccountApplicationAction` | `BaseAction` |
| `Actions/RejectAccountApplicationAction.php` | `RejectAccountApplicationAction` | `BaseAction` |
| `Actions/UploadRegistrationDocumentAction.php` | `UploadRegistrationDocumentAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Models/Registration.php` | `Registration` | `BaseModel` |
| `Models/RegistrationDocument.php` | `RegistrationDocument` | `BaseModel` |
| `Models/Placement.php` | `Placement` | `BaseModel` |
| `Models/PlacementChangeRequest.php` | `PlacementChangeRequest` | `BaseModel` |
| `Models/AccountApplication.php` | `AccountApplication` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Enums/AccountApplicationStatus.php` | `AccountApplicationStatus` | `LabelEnum`, `StatusEnum` | pending, approved, rejected |
| `Enums/PlacementChangeStatus.php` | `PlacementChangeStatus` | `LabelEnum`, `StatusEnum` | pending, approved, rejected |
| `Enums/RegistrationDocumentStatus.php` | `RegistrationDocumentStatus` | `LabelEnum`, `StatusEnum` | pending, verified, rejected |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Entities/PlacementCapacity.php` | `PlacementCapacity` | `BaseEntity` |
| `Entities/PlacementState.php` | `PlacementState` | `BaseEntity` |
| `Entities/RegistrationState.php` | `RegistrationState` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Policies/RegistrationPolicy.php` | `RegistrationPolicy` | `BasePolicy` |
| `Policies/RegistrationDocumentPolicy.php` | `RegistrationDocumentPolicy` | `BasePolicy` |
| `Policies/PlacementPolicy.php` | `PlacementPolicy` | `BasePolicy` |
| `Policies/PlacementChangeRequestPolicy.php` | `PlacementChangeRequestPolicy` | `BasePolicy` |
| `Policies/AccountApplicationPolicy.php` | `AccountApplicationPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Livewire/RegistrationCenter.php` | `RegistrationCenter` | `Component` |
| `Livewire/RegistrationWizard.php` | `RegistrationWizard` | `Component` |
| `Livewire/RegistrationVerification.php` | `RegistrationVerification` | `Component` |
| `Livewire/RegistrationDocumentUpload.php` | `RegistrationDocumentUpload` | `Component` |
| `Livewire/PlacementIndex.php` | `PlacementIndex` | `BaseRecordManager` |
| `Livewire/PlacementChangeManager.php` | `PlacementChangeManager` | `Component` |
| `Livewire/DirectPlacementManager.php` | `DirectPlacementManager` | `Component` |
| `Livewire/ApplyPage.php` | `ApplyPage` | `Component` |
| `Livewire/StudentPlacementChangeRequest.php` | `StudentPlacementChangeRequest` | `Component` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Livewire/Forms/RegistrationWizardForm.php` | `RegistrationWizardForm` |
| `Livewire/Forms/PlacementForm.php` | `PlacementForm` |
| `Livewire/Forms/DirectPlacementForm.php` | `DirectPlacementForm` |
| `Livewire/Forms/PlacementChangeForm.php` | `PlacementChangeForm` |
| `Livewire/Forms/AccountApplicationForm.php` | `AccountApplicationForm` |

---

## Routes

File: `routes/web/enrollment.php`
Naming pattern: `enrollment.{resource}.{action}`

---

## File Organization

```
app/Enrollment/
├── Actions/
│   ├── ApplyAccountAction.php
│   ├── ApproveAccountApplicationAction.php
│   ├── ApprovePlacementChangeAction.php
│   ├── CreatePlacementAction.php
│   ├── DeletePlacementAction.php
│   ├── DirectPlacementAction.php
│   ├── RegisterInternshipAction.php
│   ├── RejectAccountApplicationAction.php
│   ├── RejectPlacementChangeAction.php
│   ├── RequestPlacementChangeAction.php
│   ├── UpdatePlacementAction.php
│   ├── UploadRegistrationDocumentAction.php
│   └── VerifyRegistrationAction.php
├── Entities/
│   ├── PlacementCapacity.php
│   ├── PlacementState.php
│   └── RegistrationState.php
├── Enums/
│   ├── AccountApplicationStatus.php
│   ├── PlacementChangeStatus.php
│   └── RegistrationDocumentStatus.php
├── Livewire/
│   ├── Forms/
│   │   ├── AccountApplicationForm.php
│   │   ├── DirectPlacementForm.php
│   │   ├── PlacementChangeForm.php
│   │   ├── PlacementForm.php
│   │   └── RegistrationWizardForm.php
│   ├── ApplyPage.php
│   ├── DirectPlacementManager.php
│   ├── PlacementChangeManager.php
│   ├── PlacementIndex.php
│   ├── RegistrationCenter.php
│   ├── RegistrationDocumentUpload.php
│   ├── RegistrationVerification.php
│   ├── RegistrationWizard.php
│   └── StudentPlacementChangeRequest.php
├── Models/
│   ├── AccountApplication.php
│   ├── Placement.php
│   ├── PlacementChangeRequest.php
│   ├── Registration.php
│   └── RegistrationDocument.php
└── Policies/
    ├── AccountApplicationPolicy.php
    ├── PlacementChangeRequestPolicy.php
    ├── PlacementPolicy.php
    ├── RegistrationDocumentPolicy.php
    └── RegistrationPolicy.php
```

---

## Architectural Integration

- **Submodules**: None
- **Business Logic**: `app/Enrollment/`
- **Routing**: `routes/web/enrollment.php`
- **Views**: `resources/views/enrollment/`
- **Testing**: `tests/Feature/Enrollment/`, `tests/Unit/Enrollment/`
- **Dependencies**: User, Program, Academics, Core
- **Used By**: Journals, Assessment, Evaluation

*For overview and business context, see [enrollment.md](enrollment.md).*
