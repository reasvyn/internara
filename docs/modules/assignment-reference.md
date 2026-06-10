# Assignment — Technical Reference

> Last updated: 2026-06-10

Detailed structural and implementation reference for the **Assignment** module.

---

## Overview

Manages course assignments and submission tracking with grading workflows.

### Submodules

- `Submission` — Assignment submission, grading, and feedback

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Actions/CreateAssignmentAction.php` | `CreateAssignmentAction` | `BaseAction` |
| `Actions/UpdateAssignmentAction.php` | `UpdateAssignmentAction` | `BaseAction` |
| `Actions/DeleteAssignmentAction.php` | `DeleteAssignmentAction` | `BaseAction` |
| `Actions/PublishAssignmentAction.php` | `PublishAssignmentAction` | `BaseAction` |
| `Submission/Actions/SubmitAssignmentAction.php` | `SubmitAssignmentAction` | `BaseAction` |
| `Submission/Actions/GradeSubmissionAction.php` | `GradeSubmissionAction` | `BaseAction` |
| `Submission/Actions/VerifySubmissionAction.php` | `VerifySubmissionAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Models/Assignment.php` | `Assignment` | `BaseModel` |
| `Submission/Models/Submission.php` | `Submission` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Enums/AssignmentStatus.php` | `AssignmentStatus` | `LabelEnum`, `StatusEnum` | draft, published, closed, archived |
| `Submission/Enums/SubmissionStatus.php` | `SubmissionStatus` | `LabelEnum`, `StatusEnum` | draft, submitted, graded, returned |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Entities/AssignmentRules.php` | `AssignmentRules` | `BaseEntity` |
| `Submission/Entities/SubmissionState.php` | `SubmissionState` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Policies/AssignmentPolicy.php` | `AssignmentPolicy` | `BasePolicy` |
| `Submission/Policies/SubmissionPolicy.php` | `SubmissionPolicy` | `BasePolicy` |

---

## Notifications

| File | Notification |
| ---- | ------------ |
| `Notifications/AssignmentNotification.php` | `AssignmentNotification` |
| `Submission/Notifications/SubmissionFeedbackNotification.php` | `SubmissionFeedbackNotification` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Livewire/AssignmentManager.php` | `AssignmentManager` | `BaseRecordManager` |
| `Submission/Livewire/SubmitAssignment.php` | `SubmitAssignment` | `Component` |
| `Submission/Livewire/SubmissionGrading.php` | `SubmissionGrading` | `Component` |

## Form Requests

| File | Request | Purpose |
| ---- | ------- | ------- |
| `Http/Requests/CreateAssignmentRequest.php` | `CreateAssignmentRequest` | Assignment creation validation |
| `Submission/Http/Requests/SubmitAssignmentRequest.php` | `SubmitAssignmentRequest` | Submission validation |

---

## Routes

File: `routes/web/assignment.php`
Naming pattern: `assignment.{resource}.{action}`

## Views

Views are located in `resources/views/assignment/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Assignment/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `AssignmentFactory` | `Assignment` |
| `SubmissionFactory` | `Submission` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_assignments_table` | `assignments` |
| `create_submissions_table` | `submissions` |

---

## File Organization

```
app/Assignment/
├── Actions/
│   ├── CreateAssignmentAction.php
│   ├── DeleteAssignmentAction.php
│   ├── PublishAssignmentAction.php
│   └── UpdateAssignmentAction.php
├── Entities/AssignmentRules.php
├── Enums/AssignmentStatus.php
├── Http/Requests/CreateAssignmentRequest.php
├── Livewire/AssignmentManager.php
├── Models/Assignment.php
├── Notifications/AssignmentNotification.php
├── Policies/AssignmentPolicy.php
└── Submission/
    ├── Actions/
    │   ├── GradeSubmissionAction.php
    │   ├── SubmitAssignmentAction.php
    │   └── VerifySubmissionAction.php
    ├── Entities/SubmissionState.php
    ├── Enums/SubmissionStatus.php
    ├── Http/Requests/SubmitAssignmentRequest.php
    ├── Livewire/
    │   ├── SubmissionGrading.php
    │   └── SubmitAssignment.php
    ├── Models/Submission.php
    ├── Notifications/SubmissionFeedbackNotification.php
    └── Policies/SubmissionPolicy.php
```

---

## Architectural Integration

- **Submodules**: `Submission`
- **Business Logic**: `app/Assignment/`
- **Routing**: `routes/web/assignment.php`
- **Views**: `resources/views/assignment/`
- **Testing**: `tests/Feature/Assignment/`, `tests/Unit/Assignment/`
- **Dependencies**: User, Program, Core

*For overview and business context, see [assignment.md](assignment.md).*
