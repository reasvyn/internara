# Internship — API Reference
> Last updated: 2026-05-25
> Changes: docs: align all documentation with actual implementation (placement, registration, internship reference, ERD, routes)


Total: 57 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Actions/AddMemberToGroupAction.php` | `AddMemberToGroupAction` | `BaseAction` | Adds a member to an internship group |
| `Internship/Actions/AddSupervisorReportNotesAction.php` | `AddSupervisorReportNotesAction` | `BaseAction` | Adds supervisor notes to a report |
| `Internship/Actions/ApproveReportAction.php` | `ApproveReportAction` | `BaseAction` | Approves a submitted internship report |
| `Internship/Actions/BatchUpdateInternshipStatusAction.php` | `BatchUpdateInternshipStatusAction` | `BaseAction` | Batch updates statuses for multiple internships |
| `Internship/Actions/CheckCloseReadinessAction.php` | `CheckCloseReadinessAction` | `BaseAction` | Checks if an internship is ready to close |
| `Internship/Actions/CreateInternshipAction.php` | `CreateInternshipAction` | `BaseAction` | Creates a new internship period |
| `Internship/Actions/CreateInternshipGroupAction.php` | `CreateInternshipGroupAction` | `BaseAction` | Creates a new internship group |
| `Internship/Actions/CreateInternshipPhaseAction.php` | `CreateInternshipPhaseAction` | `BaseAction` | Creates a new internship phase |
| `Internship/Actions/CreateReportAction.php` | `CreateReportAction` | `BaseAction` | Creates an internship report draft |
| `Internship/Actions/CreateRequirementAction.php` | `CreateRequirementAction` | `BaseAction` | Creates a document requirement for internship |
| `Internship/Actions/DeleteInternshipAction.php` | `DeleteInternshipAction` | `BaseAction` | Deletes an internship |
| `Internship/Actions/DeleteInternshipGroupAction.php` | `DeleteInternshipGroupAction` | `BaseAction` | Deletes an internship group |
| `Internship/Actions/DeleteInternshipPhaseAction.php` | `DeleteInternshipPhaseAction` | `BaseAction` | Deletes an internship phase |
| `Internship/Actions/DeleteRequirementAction.php` | `DeleteRequirementAction` | `BaseAction` | Deletes a document requirement |
| `Internship/Actions/RemoveMemberFromGroupAction.php` | `RemoveMemberFromGroupAction` | `BaseAction` | Removes a member from an internship group |
| `Internship/Actions/RequestReportRevisionAction.php` | `RequestReportRevisionAction` | `BaseAction` | Requests revision of a submitted report |
| `Internship/Actions/SubmitReportAction.php` | `SubmitReportAction` | `BaseAction` | Submits a report for review |
| `Internship/Actions/UpdateInternshipAction.php` | `UpdateInternshipAction` | `BaseAction` | Updates internship details |
| `Internship/Actions/UpdateInternshipGroupAction.php` | `UpdateInternshipGroupAction` | `BaseAction` | Updates an internship group |
| `Internship/Actions/UpdateInternshipPhaseAction.php` | `UpdateInternshipPhaseAction` | `BaseAction` | Updates an internship phase |
| `Internship/Actions/UpdateRequirementAction.php` | `UpdateRequirementAction` | `BaseAction` | Updates a document requirement |

## Controllers

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Http/Controllers/ReportController.php` | `ReportController` | `BaseController` | Download internship report documents |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Entities/InternshipGroupState.php` | `InternshipGroupState` | `BaseEntity` | Read-only DTO for internship group state |
| `Internship/Entities/InternshipPeriod.php` | `InternshipPeriod` | `BaseEntity` | Read-only DTO for internship period with status |
| `Internship/Entities/InternshipState.php` | `InternshipState` | `BaseEntity` | Read-only DTO for internship state |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Internship/Enums/InternshipGroupRole.php` | `InternshipGroupRole` | `LabelEnum` | Internship group member roles |
| `Internship/Enums/InternshipStatus.php` | `InternshipStatus` | `LabelEnum`, `StatusEnum` | Internship lifecycle status |
| `Internship/Enums/ReportStatus.php` | `ReportStatus` | `LabelEnum`, `StatusEnum` | Report lifecycle status |
| `Internship/Enums/RequirementType.php` | `RequirementType` | `LabelEnum` | Document requirement types |

## Events / Listeners

| File | Class | Description |
|---|---|---|
| `Internship/Events/InternshipCreated.php` | `InternshipCreated` | Event dispatched when internship is created |
| `Internship/Listeners/NotifyAdminsInternshipCreated.php` | `NotifyAdminsInternshipCreated` | Listener that notifies admins of new internship |

## Form Objects

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Livewire/Forms/InternshipForm.php` | `InternshipForm` | `Form` | Form state for internship CRUD |
| `Internship/Livewire/Forms/InternshipGroupForm.php` | `InternshipGroupForm` | `Form` | Form state for internship group CRUD |
| `Internship/Livewire/Forms/InternshipPhaseForm.php` | `InternshipPhaseForm` | `Form` | Form state for internship phase CRUD |
| `Internship/Livewire/Forms/InternshipRequirementForm.php` | `InternshipRequirementForm` | `Form` | Form state for requirement CRUD |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Http/Requests/CreateInternshipRequest.php` | `CreateInternshipRequest` | `FormRequest` | Validation for internship creation |
| `Internship/Http/Requests/RegisterStudentRequest.php` | `RegisterStudentRequest` | `FormRequest` | Validation for student registration |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Livewire/InternshipGroupManager.php` | `InternshipGroupManager` | `BaseRecordManager` | CRUD for internship groups + member management |
| `Internship/Livewire/InternshipManager.php` | `InternshipManager` | `BaseRecordManager` | Full CRUD manager for internships |
| `Internship/Livewire/InternshipPhaseManager.php` | `InternshipPhaseManager` | `BaseRecordManager` | CRUD for internship phases |
| `Internship/Livewire/ReportWriter.php` | `ReportWriter` | `Component` | Report writing/submission interface |
| `Internship/Livewire/RequirementManager.php` | `RequirementManager` | `Component` | Manages document requirements |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Internship/Models/Internship.php` | `Internship` | `BaseModel` | Eloquent model for internships |
| `Internship/Models/InternshipDocumentRequirement.php` | `InternshipDocumentRequirement` | `BaseModel` | Eloquent model for required documents |
| `Internship/Models/InternshipGroup.php` | `InternshipGroup` | `BaseModel` | Eloquent model for internship groups |
| `Internship/Models/InternshipGroupMember.php` | `InternshipGroupMember` | `BaseModel` | Eloquent model for group membership |
| `Internship/Models/InternshipPhase.php` | `InternshipPhase` | `BaseModel` | Eloquent model for internship phases |
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
| `Internship/Policies/InternshipGroupPolicy.php` | `InternshipGroupPolicy` | `BasePolicy` | Authorization for group operations |
| `Internship/Policies/InternshipPhasePolicy.php` | `InternshipPhasePolicy` | `BasePolicy` | Authorization for phase operations |
| `Internship/Policies/InternshipPolicy.php` | `InternshipPolicy` | `BasePolicy` | Authorization for internship operations |
| `Internship/Policies/InternshipRegistrationPolicy.php` | `InternshipRegistrationPolicy` | `BasePolicy` | Authorization for registration operations |

## Rules

| File | Class | Implements | Description |
|---|---|---|---|
| `Internship/Rules/OpenForRegistration.php` | `OpenForRegistration` | `ValidationRule` | Validation rule checking internship is open for registration |

## Where to Find It

- `app/Domain/Internship/Models/`
- `app/Domain/Internship/Actions/` — 21 Actions
- `app/Domain/Internship/Enums/` — InternshipStatus, ReportStatus, GroupRole
- `app/Domain/Internship/Events/InternshipCreated.php`
- `app/Domain/Internship/Policies/` — 3 Policies: InternshipPolicy, InternshipGroupPolicy, InternshipPhasePolicy (CompanyPolicy and InternshipRegistrationPolicy are cross-domain — see Partnership and Registration)
