# Mentor — API Reference
> Last updated: 2026-05-26
> Changes: fix: enforce super admin integrity with SuperAdminIntegrityRules across all code paths


Total: 24 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentor/Actions/CreateMentorAction.php` | `CreateMentorAction` | `BaseAction` | Creates a mentor with user account and role assignment |
| `Mentor/Actions/CreateMentorProfileAction.php` | `CreateMentorProfileAction` | `BaseAction` | Creates mentor profile (industry/teacher) |
| `Mentor/Actions/CreateSupervisionLogAction.php` | `CreateSupervisionLogAction` | `BaseAction` | Creates a supervision visit log |
| `Mentor/Actions/DeleteMentorAction.php` | `DeleteMentorAction` | `BaseAction` | Deletes a mentor record |
| `Mentor/Actions/ToggleMentorActiveAction.php` | `ToggleMentorActiveAction` | `BaseAction` | Toggles mentor active status |
| `Mentor/Actions/UpdateMentorAction.php` | `UpdateMentorAction` | `BaseAction` | Updates mentor info |
| `Mentor/Actions/UpdateMentorProfileAction.php` | `UpdateMentorProfileAction` | `BaseAction` | Updates mentor profile details |
| `Mentor/Actions/VerifySupervisionLogAction.php` | `VerifySupervisionLogAction` | `BaseAction` | Verifies/approves a supervision log |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentor/Entities/MentorRole.php` | `MentorRole` | `BaseEntity` | Read-only DTO for mentor role info |
| `Mentor/Entities/SupervisionStatus.php` | `SupervisionStatus` | `BaseEntity` | Read-only DTO for supervision log status |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Mentor/Enums/SupervisionLogStatus.php` | `SupervisionLogStatus` | `StatusEnum` | Supervision log verification status |
| `Mentor/Enums/SupervisionType.php` | `SupervisionType` | `LabelEnum` | Supervision visit types |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentor/Http/Requests/CreateHandbookRequest.php` | `CreateHandbookRequest` | `FormRequest` | Validation for handbook creation |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentor/Livewire/AssessInternship.php` | `AssessInternship` | `Component` | Mentor assesses internship |
| `Mentor/Livewire/EvaluateMentor.php` | `EvaluateMentor` | `Component` | Evaluate a mentor directly |
| `Mentor/Livewire/MentorProfileManager.php` | `MentorProfileManager` | `Component` | View/manage mentor profiles |
| `Mentor/Livewire/ReportNotes.php` | `ReportNotes` | `Component` | Add supervisor notes to reports |
| `Mentor/Livewire/ReportReview.php` | `ReportReview` | `BaseRecordManager` | Review and approve/reject reports |
| `Mentor/Livewire/Supervision/SupervisionManager.php` | `SupervisionManager` | `Component` | Manages supervision visit records |
| `Mentor/Livewire/Supervision/SupervisorLogManager.php` | `SupervisorLogManager` | `Component` | Manages supervisor log entries |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentor/Models/Mentor.php` | `Mentor` | `BaseModel` | Eloquent model for mentors |
| `Mentor/Models/SupervisionLog.php` | `SupervisionLog` | `BaseModel` | Eloquent model for supervision visit logs |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentor/Policies/MentorPolicy.php` | `MentorPolicy` | `BasePolicy` | Authorization for mentor operations |
| `Mentor/Policies/SupervisionLogPolicy.php` | `SupervisionLogPolicy` | `BasePolicy` | Authorization for supervision log operations |

## Where to Find It

- `app/Domain/Mentor/Models/`
- `app/Domain/Mentor/Actions/`

## Dependency Graph

```
Mentor Domain
├── Core         → BaseModel, BaseAction, SmartLogger
├── User         → User model (mentor identity)
├── Registration → Registration records (mentor assignment)
└── Auth         → Role, permissions (mentor authorization)
```

Consumed by:
  Mentee (dashboard display), Admin (mentor management)

