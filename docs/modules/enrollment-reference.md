# Enrollment — Technical Reference

> Last updated: 2026-06-05
> Changes: Fixed submodule count mismatch (0 → 5) and file tree to match overview

Detailed structural and implementation reference for the **Enrollment** module.

---

## Overview

Manages student registration and placement phase progression

### Module Statistics
- **Actions**: 13 business logic operations
- **Models**: 5 data entities
- **Livewire Components**: 9 UI components
- **Policies**: 5 authorization rules
- **Submodules**: None (flat)

### Submodules
- None — codebase uses flat organization (no submodule subdirectories)

---

## Dependency Graph

This module depends on:
- **Academics**
- **Assessment**
- **Certification**
- **Core**
- **Guidance**
- **Journals**
- **Partners**
- **Program**
- **Reports**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/ApplyAccountAction.php` | `ApplyAccountAction` | `BaseAction` |
| `Actions/ApproveAccountApplicationAction.php` | `ApproveAccountApplicationAction` | `BaseAction` |
| `Actions/ApprovePlacementChangeAction.php` | `ApprovePlacementChangeAction` | `BaseAction` |
| `Actions/CreatePlacementAction.php` | `CreatePlacementAction` | `BaseAction` |
| `Actions/DeletePlacementAction.php` | `DeletePlacementAction` | `BaseAction` |
| `Actions/DirectPlacementAction.php` | `DirectPlacementAction` | `BaseAction` |
| `Actions/RegisterInternshipAction.php` | `RegisterInternshipAction` | `BaseAction` |
| `Actions/RejectAccountApplicationAction.php` | `RejectAccountApplicationAction` | `BaseAction` |
| `Actions/RejectPlacementChangeAction.php` | `RejectPlacementChangeAction` | `BaseAction` |
| `Actions/RequestPlacementChangeAction.php` | `RequestPlacementChangeAction` | `BaseAction` |
| `Actions/UpdatePlacementAction.php` | `UpdatePlacementAction` | `BaseAction` |
| `Actions/UploadRegistrationDocumentAction.php` | `UploadRegistrationDocumentAction` | `BaseAction` |
| `Actions/VerifyRegistrationAction.php` | `VerifyRegistrationAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Models/AccountApplication.php` | `AccountApplication` |
| `Models/Placement.php` | `Placement` |
| `Models/PlacementChangeRequest.php` | `PlacementChangeRequest` |
| `Models/Registration.php` | `Registration` |
| `Models/RegistrationDocument.php` | `RegistrationDocument` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Livewire/ApplyPage.php` | `ApplyPage` | `Component` |
| `Livewire/DirectPlacementManager.php` | `DirectPlacementManager` | `Component` |
| `Livewire/PlacementChangeManager.php` | `PlacementChangeManager` | `BaseRecordManager` |
| `Livewire/PlacementIndex.php` | `PlacementIndex` | `BaseRecordManager` |
| `Livewire/RegistrationCenter.php` | `RegistrationCenter` | `Component` |
| `Livewire/RegistrationDocumentUpload.php` | `RegistrationDocumentUpload` | `Component` |
| `Livewire/RegistrationVerification.php` | `RegistrationVerification` | `Component` |
| `Livewire/RegistrationWizard.php` | `RegistrationWizard` | `Component` |
| `Livewire/StudentPlacementChangeRequest.php` | `StudentPlacementChangeRequest` | `Component` |

---

## Livewire Forms

| File | Form Class | Extends | Description |
|---|---|---|---|
| `Livewire/Forms/RegistrationWizardForm.php` | `RegistrationWizardForm` | `Form` | Form state and validation rules for the multi-step registration wizard |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Policies/AccountApplicationPolicy.php` | `AccountApplicationPolicy` |
| `Policies/PlacementChangeRequestPolicy.php` | `PlacementChangeRequestPolicy` |
| `Policies/PlacementPolicy.php` | `PlacementPolicy` |
| `Policies/RegistrationDocumentPolicy.php` | `RegistrationDocumentPolicy` |
| `Policies/RegistrationPolicy.php` | `RegistrationPolicy` |

---

## File Organization

```
app/Enrollment/
├── Actions/              ← Flat organization (no submodule subdirectories)
├── Entities/
├── Enums/
├── Http/
├── Livewire/
├── Models/
├── Notifications/
├── Policies/
├── Types/
├── Services/
└── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Registration`, `AccountApplication`, `RegistrationDocument`, `Placement`, `PlacementChangeRequest`
- **Business Logic (`app/`)**: Located in [app/Enrollment/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Enrollment/)
- **Routing (`routes/`)**: [routes/web/enrollment.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/enrollment.php)
- **Views (`views/`)**: Blade templates and layouts are in [resources/views/enrollment/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/enrollment/)
- **Testing (`tests/`)**: Feature `tests/Feature/Enrollment/`, Unit `tests/Unit/Enrollment/`


*For overview and business context, see [enrollment.md](enrollment.md)*
