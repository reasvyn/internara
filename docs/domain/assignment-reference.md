# Assignment — API Reference
> Last updated: 2026-05-23
> Changes: fix: complete system initialization overhaul — security, middleware, recovery, form objects, docs


Total: 22 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Assignment/Actions/CreateAssignmentAction.php` | `CreateAssignmentAction` | `BaseAction` | Creates a new assignment |
| `Assignment/Actions/DeleteAssignmentAction.php` | `DeleteAssignmentAction` | `BaseAction` | Deletes an assignment |
| `Assignment/Actions/GradeSubmissionAction.php` | `GradeSubmissionAction` | `BaseAction` | Grades a submission with score and feedback |
| `Assignment/Actions/PublishAssignmentAction.php` | `PublishAssignmentAction` | `BaseAction` | Publishes an assignment, making it available to students |
| `Assignment/Actions/SubmitAssignmentAction.php` | `SubmitAssignmentAction` | `BaseAction` | Handles student assignment submission |
| `Assignment/Actions/UpdateAssignmentAction.php` | `UpdateAssignmentAction` | `BaseAction` | Updates an existing assignment |
| `Assignment/Actions/VerifySubmissionAction.php` | `VerifySubmissionAction` | `BaseAction` | Verifies a submission for integrity/validity |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Assignment/Entities/AssignmentRules.php` | `AssignmentRules` | `BaseEntity` | Read-only DTO for assignment validation rules |
| `Assignment/Entities/SubmissionState.php` | `SubmissionState` | `BaseEntity` | Read-only DTO for submission status state |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Assignment/Enums/AssignmentStatus.php` | `AssignmentStatus` | `LabelEnum`, `StatusEnum` | Assignment lifecycle status |
| `Assignment/Enums/SubmissionStatus.php` | `SubmissionStatus` | `LabelEnum`, `StatusEnum` | Submission lifecycle status |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Assignment/Http/Requests/CreateAssignmentRequest.php` | `CreateAssignmentRequest` | `FormRequest` | Validation for assignment creation |
| `Assignment/Http/Requests/SubmitAssignmentRequest.php` | `SubmitAssignmentRequest` | `FormRequest` | Validation for submission upload |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Assignment/Livewire/AssignmentManager.php` | `AssignmentManager` | `BaseRecordManager` | CRUD manager for assignments |
| `Assignment/Livewire/SubmissionGrading.php` | `SubmissionGrading` | `Component` | Grading interface for submissions |
| `Assignment/Livewire/SubmitAssignment.php` | `SubmitAssignment` | `Component` | Student submission form with file upload |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Assignment/Models/Assignment.php` | `Assignment` | `BaseModel` | Eloquent model for assignments |
| `Assignment/Models/AssignmentType.php` | `AssignmentType` | `BaseModel` | Eloquent model for assignment type categories |
| `Assignment/Models/Submission.php` | `Submission` | `BaseModel`, `HasMedia` | Eloquent model for student submissions (media-enabled) |

## Notifications

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `Assignment/Notifications/AssignmentNotification.php` | `AssignmentNotification` | `Notification`, `ShouldQueue` | Queued notification for new assignments |
| `Assignment/Notifications/SubmissionFeedbackNotification.php` | `SubmissionFeedbackNotification` | `Notification`, `ShouldQueue` | Queued notification for submission grading feedback |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Assignment/Policies/AssignmentPolicy.php` | `AssignmentPolicy` | `BasePolicy` | Authorization for assignment operations |
| `Assignment/Policies/SubmissionPolicy.php` | `SubmissionPolicy` | `BasePolicy` | Authorization for submission operations |
