# Assignment — Technical Reference

## Description

Detailed structural and implementation reference for the **Assignment** module.

---

## Overview

Manages course assignments and submission tracking with grading and revision workflows.

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/CreateAssignmentAction.php` | `CreateAssignmentAction` | `BaseCommandAction` |
| `Actions/DeleteAssignmentAction.php` | `DeleteAssignmentAction` | `BaseCommandAction` |
| `Actions/PublishAssignmentAction.php` | `PublishAssignmentAction` | `BaseCommandAction` |
| `Actions/UpdateAssignmentAction.php` | `UpdateAssignmentAction` | `BaseCommandAction` |
| `Domain/Submission/Actions/GradeSubmissionAction.php` | `GradeSubmissionAction` | `BaseCommandAction` |
| `Domain/Submission/Actions/RequestSubmissionRevisionAction.php` | `RequestSubmissionRevisionAction` | `BaseCommandAction` |
| `Domain/Submission/Actions/SubmitAssignmentAction.php` | `SubmitAssignmentAction` | `BaseCommandAction` |
| `Domain/Submission/Actions/VerifySubmissionAction.php` | `VerifySubmissionAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/Submission/Models/Submission.php` | `Submission` | `BaseModel` |
| `Models/Assignment.php` | `Assignment` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/Submission/Enums/SubmissionStatus.php` | `SubmissionStatus` | `LabelEnum` | — |
| `Enums/AssignmentStatus.php` | `AssignmentStatus` | `LabelEnum` | — |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/Submission/Policies/SubmissionPolicy.php` | `SubmissionPolicy` | `BasePolicy` |
| `Policies/AssignmentPolicy.php` | `AssignmentPolicy` | `BasePolicy` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Data/CreateAssignmentData.php` | `CreateAssignmentData` | `BaseData` |
| `Data/UpdateAssignmentData.php` | `UpdateAssignmentData` | `BaseData` |
| `Domain/Submission/Data/GradeSubmissionData.php` | `GradeSubmissionData` | `BaseData` |
| `Domain/Submission/Data/SubmitAssignmentData.php` | `SubmitAssignmentData` | `BaseData` |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/Submission/Events/SubmissionRevisionRequested.php` | `SubmissionRevisionRequested` | `BaseEvent` |
| `Events/AssignmentPublished.php` | `AssignmentPublished` | `BaseEvent` |

## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Listeners/NotifyOnAssignmentPublished.php` | `NotifyOnAssignmentPublished` | — |

## Notifications

| File                                                          | Notification                     |
| ------------------------------------------------------------- | -------------------------------- |
| `Notifications/AssignmentNotification.php`                    | `AssignmentNotification`         |
| `Submission/Notifications/SubmissionFeedbackNotification.php` | `SubmissionFeedbackNotification` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Submission/Livewire/SubmissionGrading.php` | `SubmissionGrading` | `Component` |
| `Domain/Submission/Livewire/SubmitAssignment.php` | `SubmitAssignment` | `Component` |
| `Livewire/AssignmentManager.php` | `AssignmentManager` | `BaseRecordManager` |

## Form Requests

| File | Request | Purpose |
|---|---|---|
| `Domain/Submission/Http/Requests/SubmitAssignmentRequest.php` | `SubmitAssignmentRequest` | — |
| `Http/Requests/CreateAssignmentRequest.php` | `CreateAssignmentRequest` | — |

## Routes

File: `routes/web/assignment.php` Named routes: `student.assignments`, `sysadmin.assignments`,
`sysadmin.submissions.grading`, `teacher.submissions.grading`, `supervision.submissions.grading`,
`assignment.show`

## Views

Views are located in `resources/views/assignment/`. See [UI/UX](../../guides/ui-ux/design-system.md) for the
design system.

## Tests

Tests are located in `tests/Assignment/`. See [Testing](../../guides/infra/testing.md) for the
testing conventions.

## Factories

| Factory             | Model        |
| ------------------- | ------------ |
| `AssignmentFactory` | `Assignment` |
| `SubmissionFactory` | `Submission` |

## Migrations

| Migration                  | Table         |
| -------------------------- | ------------- |
| `create_assignments_table` | `assignments` |
| `create_submissions_table` | `submissions` |

---

## Architectural Integration

- **Submodules**: `Submission`
- **Business Logic**: `app/Modules/Assignment/`
- **Routing**: `routes/web/assignment.php`
- **Views**: `resources/views/assignment/`
- **Testing**: `tests/Assignment/`
- **Dependencies**: User, Program, Core

_For overview and business context, see [assignment.md](assignment.md)._
