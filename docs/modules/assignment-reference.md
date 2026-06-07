# Assignment — Technical Reference

> Last updated: 2026-06-03 Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Assignment** module.

---

## Overview

Manages coursework assignments and submission tracking

### Module Statistics

- **Actions**: 7 business logic operations
- **Models**: 3 data entities
- **Livewire Components**: 3 UI components
- **Policies**: 2 authorization rules
- **Submodules**: 2 module submodules

### Submodules

- `Submission` (flat: Actions, Entities, Enums, Livewire, Models, Policies, Notifications directly
  in `app/Assignment/`)

---

## Dependency Graph

This module depends on:

- **Core**
- **Guidance**
- **Program**
- **User**

---

## Actions

| File                                            | Class                     | Extends      |
| ----------------------------------------------- | ------------------------- | ------------ |
| `Actions/CreateAssignmentAction.php`            | `CreateAssignmentAction`  | `BaseAction` |
| `Actions/DeleteAssignmentAction.php`            | `DeleteAssignmentAction`  | `BaseAction` |
| `Submission/Actions/GradeSubmissionAction.php`  | `GradeSubmissionAction`   | `BaseAction` |
| `Actions/PublishAssignmentAction.php`           | `PublishAssignmentAction` | `BaseAction` |
| `Submission/Actions/SubmitAssignmentAction.php` | `SubmitAssignmentAction`  | `BaseAction` |
| `Actions/UpdateAssignmentAction.php`            | `UpdateAssignmentAction`  | `BaseAction` |
| `Submission/Actions/VerifySubmissionAction.php` | `VerifySubmissionAction`  | `BaseAction` |

---

## Models

| File                               | Class            |
| ---------------------------------- | ---------------- |
| `Models/Assignment.php`            | `Assignment`     |
| `Models/AssignmentType.php`        | `AssignmentType` |
| `Submission/Models/Submission.php` | `Submission`     |

---

## Livewire Components

| File                                        | Component           | Extends             |
| ------------------------------------------- | ------------------- | ------------------- |
| `Livewire/AssignmentManager.php`            | `AssignmentManager` | `BaseRecordManager` |
| `Submission/Livewire/SubmissionGrading.php` | `SubmissionGrading` | `Component`         |
| `Submission/Livewire/SubmitAssignment.php`  | `SubmitAssignment`  | `Component`         |

---

## Authorization Policies

| File                                       | Policy             |
| ------------------------------------------ | ------------------ |
| `Policies/AssignmentPolicy.php`            | `AssignmentPolicy` |
| `Submission/Policies/SubmissionPolicy.php` | `SubmissionPolicy` |

---

## File Organization

```
app/Assignment/
├── Actions/              ← Cross-submodule / flat actions
├── Entities/
├── Enums/
├── Http/
│   └── Requests/
├── Livewire/
├── Models/
├── Notifications/
├── Policies/
├── Submission/           ← Submission submodule
│   ├── Actions/
│   ├── Entities/
│   ├── Enums/
│   ├── Http/
│   │   └── Requests/
│   ├── Livewire/
│   ├── Models/
│   ├── Notifications/
│   └── Policies/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Assignment`, `Submission`
- **Business Logic (`app/`)**: Located in
  [app/Assignment/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Assignment/)
- **Routing (`routes/`)**:
  [routes/web/assignment.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/assignment.php)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/assignment/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/assignment/)
- **Testing (`tests/`)**: Feature `tests/Feature/Assignment/`, Unit `tests/Unit/Assignment/`

_For overview and business context, see [assignment.md](assignment.md)_
