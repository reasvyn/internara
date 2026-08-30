# Program — Technical Reference

## Description

Detailed structural and implementation reference for the **Program** module.

---

## Overview

Manages internship programs (lowongan PKL), program timelines, and student cohort groupings.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/Internship/Actions/BatchUpdateInternshipStatusAction.php` | `BatchUpdateInternshipStatusAction` | `BaseCommandAction` |
| `Domain/Internship/Actions/CreateInternshipAction.php` | `CreateInternshipAction` | `BaseCommandAction` |
| `Domain/Internship/Actions/DeleteInternshipAction.php` | `DeleteInternshipAction` | `BaseCommandAction` |
| `Domain/Internship/Actions/ReadCloseReadinessAction.php` | `ReadCloseReadinessAction` | `BaseReadAction` |
| `Domain/Internship/Actions/UpdateInternshipAction.php` | `UpdateInternshipAction` | `BaseCommandAction` |
| `Domain/InternshipGroup/Actions/AddMemberToGroupAction.php` | `AddMemberToGroupAction` | `BaseCommandAction` |
| `Domain/InternshipGroup/Actions/AddMembersToGroupAction.php` | `AddMembersToGroupAction` | `BaseCommandAction` |
| `Domain/InternshipGroup/Actions/CreateInternshipGroupAction.php` | `CreateInternshipGroupAction` | `BaseCommandAction` |
| `Domain/InternshipGroup/Actions/DeleteInternshipGroupAction.php` | `DeleteInternshipGroupAction` | `BaseCommandAction` |
| `Domain/InternshipGroup/Actions/RemoveMemberFromGroupAction.php` | `RemoveMemberFromGroupAction` | `BaseCommandAction` |
| `Domain/InternshipGroup/Actions/UpdateInternshipGroupAction.php` | `UpdateInternshipGroupAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/Internship/Models/Internship.php` | `Internship` | `BaseModel` |
| `Domain/InternshipGroup/Models/InternshipGroup.php` | `InternshipGroup` | `BaseModel` |
| `Domain/InternshipGroup/Models/InternshipGroupMember.php` | `InternshipGroupMember` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/Internship/Enums/InternshipStatus.php` | `InternshipStatus` | `LabelEnum` | — |
| `Domain/InternshipGroup/Enums/InternshipGroupRole.php` | `InternshipGroupRole` | `LabelEnum` | — |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/Internship/Policies/InternshipPolicy.php` | `InternshipPolicy` | `BasePolicy` |
| `Domain/InternshipGroup/Policies/InternshipGroupPolicy.php` | `InternshipGroupPolicy` | `BasePolicy` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/Internship/Data/InternshipData.php` | `InternshipData` | `BaseData` |
| `Domain/InternshipGroup/Data/InternshipGroupData.php` | `InternshipGroupData` | `BaseData` |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/Internship/Events/InternshipCreated.php` | `InternshipCreated` | `BaseEvent` |
| `Domain/Internship/Events/InternshipStatusBatchUpdated.php` | `InternshipStatusBatchUpdated` | `BaseEvent` |

## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Domain/Internship/Listeners/NotifyAdminsInternshipCreated.php` | `NotifyAdminsInternshipCreated` | — |

## Notifications

| File                                                         | Notification                    |
| ------------------------------------------------------------ | ------------------------------- |
| `Internship/Notifications/InternshipCreatedNotification.php` | `InternshipCreatedNotification` |
| `Notifications/RegistrationNotification.php`                 | `RegistrationNotification`      |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Internship/Livewire/Forms/InternshipForm.php` | `InternshipForm` | `BaseFormView` |
| `Domain/Internship/Livewire/InternshipManager.php` | `InternshipManager` | `BaseRecordManager` |
| `Domain/InternshipGroup/Livewire/Forms/InternshipGroupForm.php` | `InternshipGroupForm` | `BaseFormView` |
| `Domain/InternshipGroup/Livewire/InternshipGroupManager.php` | `InternshipGroupManager` | `BaseRecordManager` |

## Rules

| File | Rule | Purpose |
|---|---|---|
| `Domain/Internship/Rules/OpenForRegistration.php` | `OpenForRegistration` | — |

## Form Requests

| File | Request | Purpose |
|---|---|---|
| `Http/Requests/CreateInternshipRequest.php` | `CreateInternshipRequest` | — |
| `Http/Requests/RegisterStudentRequest.php` | `RegisterStudentRequest` | — |

## Routes

File: `routes/web/program.php` Naming pattern: `sysadmin.internships` (admin prefix)

## Views

Views are located in `resources/views/program/`. See [UI/UX](../../guides/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/Program/`. See [Testing](../../guides/infra/testing.md)
for the testing conventions.

## Factories

| Factory                        | Model                   |
| ------------------------------ | ----------------------- |
| `InternshipFactory`            | `Internship`            |
| `InternshipGroupFactory`       | `InternshipGroup`       |
| `InternshipGroupMemberFactory` | `InternshipGroupMember` |

## Migrations

| Migration                                | Table                         |
| ---------------------------------------- | ----------------------------- |
| `create_internships_table`               | `internships`                 |
| `add_registration_dates_to_internships_table` | `internships`             |
| `create_internship_groups_table`         | `internship_groups` + `internship_group_members` |

---

## Architectural Integration

- **Submodules**: `Internship`, `InternshipGroup`
- **Business Logic**: `app/Modules/Program/`
- **Routing**: `routes/web/program.php`
- **Views**: `resources/views/program/`
- **Testing**: `tests/Program/`
- **Dependencies**: Academics, Partners, Core
- **Used By**: Enrollment, Journals, Evaluation

_For overview and business context, see [program.md](program.md)._
