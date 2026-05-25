# Internship — API Reference

Total: 36 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Actions/AddSupervisorReportNotesAction.php` | `AddSupervisorReportNotesAction` | `BaseAction` | Adds supervisor notes to a report |
| `Internship/Actions/ApproveReportAction.php` | `ApproveReportAction` | `BaseAction` | Approves a submitted internship report |
| `Internship/Actions/BatchUpdateInternshipStatusAction.php` | `BatchUpdateInternshipStatusAction` | `BaseAction` | Batch updates statuses for multiple internships |
| `Internship/Actions/CheckCloseReadinessAction.php` | `CheckCloseReadinessAction` | `BaseAction` | Checks if an internship is ready to close |
| `Internship/Actions/CreateInternshipAction.php` | `CreateInternshipAction` | `BaseAction` | Creates a new internship period |
| `Internship/Actions/CreateReportAction.php` | `CreateReportAction` | `BaseAction` | Creates an internship report draft |
| `Internship/Actions/CreateRequirementAction.php` | `CreateRequirementAction` | `BaseAction` | Creates a document requirement for internship |
| `Internship/Actions/DeleteInternshipAction.php` | `DeleteInternshipAction` | `BaseAction` | Deletes an internship |
| `Internship/Actions/DeleteRequirementAction.php` | `DeleteRequirementAction` | `BaseAction` | Deletes a document requirement |
| `Internship/Actions/RequestReportRevisionAction.php` | `RequestReportRevisionAction` | `BaseAction` | Requests revision of a submitted report |
| `Internship/Actions/SubmitReportAction.php` | `SubmitReportAction` | `BaseAction` | Submits a report for review |
| `Internship/Actions/UpdateInternshipAction.php` | `UpdateInternshipAction` | `BaseAction` | Updates internship details |
| `Internship/Actions/UpdateRequirementAction.php` | `UpdateRequirementAction` | `BaseAction` | Updates a document requirement |

## Controllers

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Http/Controllers/ReportController.php` | `ReportController` | `BaseController` | Download internship report documents |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Entities/InternshipPeriod.php` | `InternshipPeriod` | `BaseEntity` | Read-only DTO for internship period with status |
| `Internship/Entities/InternshipState.php` | `InternshipState` | `BaseEntity` | Read-only DTO for internship state |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Internship/Enums/InternshipStatus.php` | `InternshipStatus` | `LabelEnum`, `StatusEnum` | Internship lifecycle status |
| `Internship/Enums/ReportStatus.php` | `ReportStatus` | `LabelEnum`, `StatusEnum` | Report lifecycle status |
| `Internship/Enums/RequirementType.php` | `RequirementType` | `LabelEnum` | Document requirement types |

## Events / Listeners

| File | Class | Description |
|---|---|---|
| `Internship/Events/InternshipCreated.php` | `InternshipCreated` | Event dispatched when internship is created |
| `Internship/Listeners/NotifyAdminsInternshipCreated.php` | `NotifyAdminsInternshipCreated` | Listener that notifies admins of new internship |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Http/Requests/CreateInternshipRequest.php` | `CreateInternshipRequest` | `FormRequest` | Validation for internship creation |
| `Internship/Http/Requests/RegisterStudentRequest.php` | `RegisterStudentRequest` | `FormRequest` | Validation for student registration |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Livewire/InternshipManager.php` | `InternshipManager` | `BaseRecordManager` | Full CRUD manager for internships |
| `Internship/Livewire/ReportWriter.php` | `ReportWriter` | `Component` | Report writing/submission interface |
| `Internship/Livewire/RequirementManager.php` | `RequirementManager` | `Component` | Manages document requirements |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Models/Internship.php` | `Internship` | `BaseModel` | Eloquent model for internships |
| `Internship/Models/InternshipDocumentRequirement.php` | `InternshipDocumentRequirement` | `BaseModel` | Eloquent model for required documents |
| `Internship/Models/Report.php` | `Report` | `BaseModel` | Eloquent model for internship reports |
| `Internship/Models/ReportRevision.php` | `ReportRevision` | `BaseModel` | Eloquent model for report revision history |

## Notifications

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `Internship/Notifications/InternshipCreatedNotification.php` | `InternshipCreatedNotification` | `Notification`, `ShouldQueue` | Queued notification for new internship |
| `Internship/Notifications/RegistrationNotification.php` | `RegistrationNotification` | `Notification`, `ShouldQueue` | Queued notification for registration |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Policies/CompanyPolicy.php` | `CompanyPolicy` | `BasePolicy` | Authorization for company operations |
| `Internship/Policies/InternshipPolicy.php` | `InternshipPolicy` | `BasePolicy` | Authorization for internship operations |
| `Internship/Policies/InternshipRegistrationPolicy.php` | `InternshipRegistrationPolicy` | `BasePolicy` | Authorization for registration operations |

## Rules

| File | Class | Implements | Description |
|---|---|---|---|
| `Internship/Rules/OpenForRegistration.php` | `OpenForRegistration` | `ValidationRule` | Validation rule checking internship is open for registration |
