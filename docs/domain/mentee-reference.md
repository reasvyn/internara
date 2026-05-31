# Mentee — API Reference
> Last updated: 2026-05-31
> Changes: docs: audit — all items Implemented

> **Legend:** ✅ Implemented = code exists | ⏳ Planned = not yet implemented

Total: 6 files — ✅ 6 Implemented

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentee/Actions/CreateMenteeAction.php` | `CreateMenteeAction` | `BaseAction` | Creates a mentee record with associated user account |
| `Mentee/Actions/DeleteMenteeAction.php` | `DeleteMenteeAction` | `BaseAction` | Deletes a mentee record |
| `Mentee/Actions/UpdateMenteeAction.php` | `UpdateMenteeAction` | `BaseAction` | Updates a mentee's details |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentee/Entities/MenteeState.php` | `MenteeState` | `BaseEntity` | Read-only DTO for mentee state: canClockIn, canSubmitLogbook, canSubmitAssignment, hasEnded, daysRemaining |

Mentee does not own Livewire components — `StudentDashboard` moved to the [User domain](user-reference.md).

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentee/Models/Mentee.php` | `Mentee` | `BaseModel` | Eloquent model for mentee records |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentee/Policies/MenteePolicy.php` | `MenteePolicy` | `BasePolicy` | Authorization for mentee operations |

## Where to Find It

- `app/Domain/Mentee/Models/Mentee.php`
- `app/Domain/Mentee/Actions/`

## Dependency Graph

```
Mentee Domain
├── Core         → BaseModel, BaseAction, SmartLogger
├── User         → User model (student identity)
└── Registration → Registration records (mentee enrollment)
```

Consumed by:
  Admin (student management), Mentor (supervision relationship)

