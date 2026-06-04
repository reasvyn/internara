# Assignment — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Assignment** domain.

---

## Overview

Manages coursework assignments and submission tracking

### Domain Statistics
- **Actions**: 7 business logic operations
- **Models**: 3 data entities
- **Livewire Components**: 3 UI components
- **Policies**: 2 authorization rules
- **Aggregates**: 2 domain aggregates

### Aggregates
- `Assignment`
- `Submission`

---

## Dependency Graph

This domain depends on:
- **Core**
- **Guidance**
- **Program**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/Assignment/Actions/CreateAssignmentAction.php` | `CreateAssignmentAction` | `BaseAction` |
| `Aggregates/Assignment/Actions/DeleteAssignmentAction.php` | `DeleteAssignmentAction` | `BaseAction` |
| `Aggregates/Submission/Actions/GradeSubmissionAction.php` | `GradeSubmissionAction` | `BaseAction` |
| `Aggregates/Assignment/Actions/PublishAssignmentAction.php` | `PublishAssignmentAction` | `BaseAction` |
| `Aggregates/Submission/Actions/SubmitAssignmentAction.php` | `SubmitAssignmentAction` | `BaseAction` |
| `Aggregates/Assignment/Actions/UpdateAssignmentAction.php` | `UpdateAssignmentAction` | `BaseAction` |
| `Aggregates/Submission/Actions/VerifySubmissionAction.php` | `VerifySubmissionAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Assignment/Models/Assignment.php` | `Assignment` |
| `Aggregates/Assignment/Models/AssignmentType.php` | `AssignmentType` |
| `Aggregates/Submission/Models/Submission.php` | `Submission` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Assignment/Livewire/AssignmentManager.php` | `AssignmentManager` | `BaseRecordManager` |
| `Aggregates/Submission/Livewire/SubmissionGrading.php` | `SubmissionGrading` | `Component` |
| `Aggregates/Submission/Livewire/SubmitAssignment.php` | `SubmitAssignment` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Assignment/Policies/AssignmentPolicy.php` | `AssignmentPolicy` |
| `Aggregates/Submission/Policies/SubmissionPolicy.php` | `SubmissionPolicy` |

---

## File Organization

```
app/Domain/Assignment/
├── Aggregates/           ← Aggregate roots
│   └── {Aggregate}/
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
