# Assignment — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

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
- `Assignment`
- `Submission`

---

## Dependency Graph

This module depends on:
- **Core**
- **Guidance**
- **Program**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Assignment/Actions/CreateAssignmentAction.php` | `CreateAssignmentAction` | `BaseAction` |
| `Assignment/Actions/DeleteAssignmentAction.php` | `DeleteAssignmentAction` | `BaseAction` |
| `Submission/Actions/GradeSubmissionAction.php` | `GradeSubmissionAction` | `BaseAction` |
| `Assignment/Actions/PublishAssignmentAction.php` | `PublishAssignmentAction` | `BaseAction` |
| `Submission/Actions/SubmitAssignmentAction.php` | `SubmitAssignmentAction` | `BaseAction` |
| `Assignment/Actions/UpdateAssignmentAction.php` | `UpdateAssignmentAction` | `BaseAction` |
| `Submission/Actions/VerifySubmissionAction.php` | `VerifySubmissionAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Assignment/Models/Assignment.php` | `Assignment` |
| `Assignment/Models/AssignmentType.php` | `AssignmentType` |
| `Submission/Models/Submission.php` | `Submission` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Assignment/Livewire/AssignmentManager.php` | `AssignmentManager` | `BaseRecordManager` |
| `Submission/Livewire/SubmissionGrading.php` | `SubmissionGrading` | `Component` |
| `Submission/Livewire/SubmitAssignment.php` | `SubmitAssignment` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Assignment/Policies/AssignmentPolicy.php` | `AssignmentPolicy` |
| `Submission/Policies/SubmissionPolicy.php` | `SubmissionPolicy` |

---

## File Organization

```
app/Assignment/
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

*For overview and business context, see [assignment.md](assignment.md)*
