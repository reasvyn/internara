# Assignment — Technical Reference

> **Last updated:** 2026-08-18 **Changes:** add CreateAssignmentData, UpdateAssignmentData,
> GradeSubmissionData to Data/DTOs

## Description

Detailed structural and implementation reference for the **Assignment** module.

---

## Overview

Manages course assignments and submission tracking with grading and revision workflows.

## Actions

| File                                                     | Class                             | Extends             |
| -------------------------------------------------------- | --------------------------------- | ------------------- |
| `Actions/CreateAssignmentAction.php`                     | `CreateAssignmentAction`          | `BaseCommandAction` |
| `Actions/UpdateAssignmentAction.php`                     | `UpdateAssignmentAction`          | `BaseCommandAction` |
| `Actions/DeleteAssignmentAction.php`                     | `DeleteAssignmentAction`          | `BaseCommandAction` |
| `Actions/PublishAssignmentAction.php`                    | `PublishAssignmentAction`         | `BaseCommandAction` |
| `Submission/Actions/SubmitAssignmentAction.php`          | `SubmitAssignmentAction`          | `BaseCommandAction` |
| `Submission/Actions/GradeSubmissionAction.php`           | `GradeSubmissionAction`           | `BaseCommandAction` |
| `Submission/Actions/VerifySubmissionAction.php`          | `VerifySubmissionAction`          | `BaseCommandAction` |
| `Submission/Actions/RequestSubmissionRevisionAction.php` | `RequestSubmissionRevisionAction` | `BaseCommandAction` |

---

## Models

| File                               | Class        | Extends     |
| ---------------------------------- | ------------ | ----------- |
| `Models/Assignment.php`            | `Assignment` | `BaseModel` |
| `Submission/Models/Submission.php` | `Submission` | `BaseModel` |

---

## Enums

| File                                    | Enum               | Implements                | Values                                                |
| --------------------------------------- | ------------------ | ------------------------- | ----------------------------------------------------- |
| `Enums/AssignmentStatus.php`            | `AssignmentStatus` | `LabelEnum`, `StatusEnum` | draft, published, closed                              |
| `Submission/Enums/SubmissionStatus.php` | `SubmissionStatus` | `LabelEnum`, `StatusEnum` | draft, submitted, verified, graded, revision_required |

---

## Entities

| File                                      | Class             | Extends      |
| ----------------------------------------- | ----------------- | ------------ |
| `Entities/AssignmentRules.php`            | `AssignmentRules` | `BaseEntity` |
| `Submission/Entities/SubmissionState.php` | `SubmissionState` | `BaseEntity` |

---

## Policies

| File                                       | Policy             | Extends      |
| ------------------------------------------ | ------------------ | ------------ |
| `Policies/AssignmentPolicy.php`            | `AssignmentPolicy` | `BasePolicy` |
| `Submission/Policies/SubmissionPolicy.php` | `SubmissionPolicy` | `BasePolicy` |

---

## Data / DTOs

| File                                       | Class                  | Extends    |
| ------------------------------------------ | ---------------------- | ---------- |
| `Data/CreateAssignmentData.php`            | `CreateAssignmentData` | `BaseData` |
| `Data/UpdateAssignmentData.php`            | `UpdateAssignmentData` | `BaseData` |
| `Submission/Data/GradeSubmissionData.php`  | `GradeSubmissionData`  | `BaseData` |
| `Submission/Data/SubmitAssignmentData.php` | `SubmitAssignmentData` | `BaseData` |

## Events

| File                                                | Event                         | Dispatched By                     |
| --------------------------------------------------- | ----------------------------- | --------------------------------- |
| `Events/AssignmentPublished.php`                    | `AssignmentPublished`         | `PublishAssignmentAction`         |
| `Submission/Events/SubmissionRevisionRequested.php` | `SubmissionRevisionRequested` | `RequestSubmissionRevisionAction` |

## Listeners

| File                                        | Listener                      | Listens To            |
| ------------------------------------------- | ----------------------------- | --------------------- |
| `Listeners/NotifyOnAssignmentPublished.php` | `NotifyOnAssignmentPublished` | `AssignmentPublished` |

## Notifications

| File                                                          | Notification                     |
| ------------------------------------------------------------- | -------------------------------- |
| `Notifications/AssignmentNotification.php`                    | `AssignmentNotification`         |
| `Submission/Notifications/SubmissionFeedbackNotification.php` | `SubmissionFeedbackNotification` |

## Livewire Components

| File                                        | Component           | Extends             |
| ------------------------------------------- | ------------------- | ------------------- |
| `Livewire/AssignmentManager.php`            | `AssignmentManager` | `BaseRecordManager` |
| `Submission/Livewire/SubmitAssignment.php`  | `SubmitAssignment`  | `BaseFormView`      |
| `Submission/Livewire/SubmissionGrading.php` | `SubmissionGrading` | `Component`         |

## Form Requests

| File                                                   | Request                   | Purpose                        |
| ------------------------------------------------------ | ------------------------- | ------------------------------ |
| `Http/Requests/CreateAssignmentRequest.php`            | `CreateAssignmentRequest` | Assignment creation validation |
| `Submission/Http/Requests/SubmitAssignmentRequest.php` | `SubmitAssignmentRequest` | Submission validation          |

---

## Routes

File: `routes/web/assignment.php` Named routes: `student.assignments`, `sysadmin.assignments`,
`sysadmin.submissions.grading`, `teacher.submissions.grading`, `supervision.submissions.grading`,
`assignment.show`

## Views

Views are located in `resources/views/assignment/`. See [UI/UX](../../foundation/ui-ux.md) for the
design system.

## Tests

Tests are located in `tests/Assignment/`. See [Testing](../../infrastructure/testing.md) for the
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
- **Business Logic**: `app/Assignment/`
- **Routing**: `routes/web/assignment.php`
- **Views**: `resources/views/assignment/`
- **Testing**: `tests/Assignment/`
- **Dependencies**: User, Program, Core

_For overview and business context, see [assignment.md](assignment.md)._
