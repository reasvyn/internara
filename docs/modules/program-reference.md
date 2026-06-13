# Program — Technical Reference

> Last updated: 2026-06-10

Detailed structural and implementation reference for the **Program** module.

---

## Overview

Manages internship programs (lowongan PKL), program timelines, and student cohort groupings.

### Submodules

- `Internship` — Internship program definitions
- `InternshipGroup` — Student cohort groupings

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Internship/Actions/CreateInternshipAction.php` | `CreateInternshipAction` | `BaseAction` |
| `Internship/Actions/UpdateInternshipAction.php` | `UpdateInternshipAction` | `BaseAction` |
| `Internship/Actions/DeleteInternshipAction.php` | `DeleteInternshipAction` | `BaseAction` |
| `Internship/Actions/BatchUpdateInternshipStatusAction.php` | `BatchUpdateInternshipStatusAction` | `BaseAction` |
| `Internship/Actions/ReadCloseReadinessAction.php` | `ReadCloseReadinessAction` | Read |
| `InternshipGroup/Actions/CreateInternshipGroupAction.php` | `CreateInternshipGroupAction` | `BaseAction` |
| `InternshipGroup/Actions/UpdateInternshipGroupAction.php` | `UpdateInternshipGroupAction` | `BaseAction` |
| `InternshipGroup/Actions/DeleteInternshipGroupAction.php` | `DeleteInternshipGroupAction` | `BaseAction` |
| `InternshipGroup/Actions/AddMemberToGroupAction.php` | `AddMemberToGroupAction` | `BaseAction` |
| `InternshipGroup/Actions/RemoveMemberFromGroupAction.php` | `RemoveMemberFromGroupAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Internship/Models/Internship.php` | `Internship` | `BaseModel` |
| `InternshipGroup/Models/InternshipGroup.php` | `InternshipGroup` | `BaseModel` |
| `InternshipGroup/Models/InternshipGroupMember.php` | `InternshipGroupMember` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Internship/Enums/InternshipStatus.php` | `InternshipStatus` | `LabelEnum`, `StatusEnum` | draft, published, active, completed, cancelled |
| `InternshipGroup/Enums/InternshipGroupRole.php` | `InternshipGroupRole` | `LabelEnum` | leader, member |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Internship/Entities/InternshipPeriod.php` | `InternshipPeriod` | `BaseEntity` |
| `Internship/Entities/InternshipState.php` | `InternshipState` | `BaseEntity` |
| `InternshipGroup/Entities/InternshipGroupState.php` | `InternshipGroupState` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Internship/Policies/InternshipPolicy.php` | `InternshipPolicy` | `BasePolicy` |
| `InternshipGroup/Policies/InternshipGroupPolicy.php` | `InternshipGroupPolicy` | `BasePolicy` |

---

## Data / DTOs

| File | Class | Extends |
| ---- | ----- | ------- |
| `Internship/Data/InternshipData.php` | `InternshipData` | `BaseData` |
| `InternshipGroup/Data/InternshipGroupData.php` | `InternshipGroupData` | `BaseData` |

## Events

| File | Event | Extends |
| ---- | ----- | ------- |
| `Internship/Events/InternshipCreated.php` | `InternshipCreated` | `BaseEvent` |

## Listeners

| File | Listener |
| ---- | -------- |
| `Internship/Listeners/NotifyAdminsInternshipCreated.php` | `NotifyAdminsInternshipCreated` |

## Notifications

| File | Notification |
| ---- | ------------ |
| `Internship/Notifications/InternshipCreatedNotification.php` | `InternshipCreatedNotification` |
| `Notifications/RegistrationNotification.php` | `RegistrationNotification` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Internship/Livewire/InternshipManager.php` | `InternshipManager` | `BaseRecordManager` |
| `InternshipGroup/Livewire/InternshipGroupManager.php` | `InternshipGroupManager` | `BaseRecordManager` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Internship/Livewire/Forms/InternshipForm.php` | `InternshipForm` |
| `InternshipGroup/Livewire/Forms/InternshipGroupForm.php` | `InternshipGroupForm` |

## Rules

| File | Rule | Purpose |
| ---- | ---- | ------- |
| `Internship/Rules/OpenForRegistration.php` | `OpenForRegistration` | Validates internship is open for registration |

## Form Requests

| File | Request | Purpose |
| ---- | ------- | ------- |
| `Http/Requests/CreateInternshipRequest.php` | `CreateInternshipRequest` | Create internship validation |
| `Http/Requests/RegisterStudentRequest.php` | `RegisterStudentRequest` | Student registration validation |

---

## Routes

File: `routes/web/program.php`
Naming pattern: `program.{resource}.{action}`

## Views

Views are located in `resources/views/program/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Program/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `InternshipFactory` | `Internship` |
| `InternshipGroupFactory` | `InternshipGroup` |
| `InternshipGroupMemberFactory` | `InternshipGroupMember` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_internships_table` | `internships` |
| `create_internship_groups_table` | `internship_groups` |
| `create_internship_group_members_table` | `internship_group_members` |

---

## File Organization

```
app/Program/
├── Http/Requests/
│   ├── CreateInternshipRequest.php
│   └── RegisterStudentRequest.php
├── Internship/
│   ├── Actions/
│   │   ├── BatchUpdateInternshipStatusAction.php
│   │   ├── ReadCloseReadinessAction.php
│   │   ├── CreateInternshipAction.php
│   │   ├── DeleteInternshipAction.php
│   │   └── UpdateInternshipAction.php
│   ├── Entities/
│   │   ├── InternshipPeriod.php
│   │   └── InternshipState.php
│   ├── Enums/InternshipStatus.php
│   ├── Events/InternshipCreated.php
│   ├── Listeners/NotifyAdminsInternshipCreated.php
│   ├── Livewire/
│   │   ├── Forms/InternshipForm.php
│   │   └── InternshipManager.php
│   ├── Models/Internship.php
│   ├── Notifications/InternshipCreatedNotification.php
│   ├── Policies/InternshipPolicy.php
│   └── Rules/OpenForRegistration.php
├── InternshipGroup/
│   ├── Actions/
│   │   ├── AddMemberToGroupAction.php
│   │   ├── CreateInternshipGroupAction.php
│   │   ├── DeleteInternshipGroupAction.php
│   │   ├── RemoveMemberFromGroupAction.php
│   │   └── UpdateInternshipGroupAction.php
│   ├── Entities/InternshipGroupState.php
│   ├── Enums/InternshipGroupRole.php
│   ├── Livewire/
│   │   ├── Forms/InternshipGroupForm.php
│   │   └── InternshipGroupManager.php
│   ├── Models/
│   │   ├── InternshipGroup.php
│   │   └── InternshipGroupMember.php
│   └── Policies/InternshipGroupPolicy.php
└── Notifications/RegistrationNotification.php
```

---

## Architectural Integration

- **Submodules**: `Internship`, `InternshipGroup`
- **Business Logic**: `app/Program/`
- **Routing**: `routes/web/program.php`
- **Views**: `resources/views/program/`
- **Testing**: `tests/Feature/Program/`, `tests/Unit/Program/`
- **Dependencies**: Academics, Partners, Core
- **Used By**: Enrollment, Journals, Evaluation

*For overview and business context, see [program.md](program.md).*
