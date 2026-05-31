# Mentee — API Reference
> Last updated: 2026-05-23
> Changes: cleanup: remove orphaned dashboard views, update domain docs, mark User issues resolved


Total: 6 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentee/Actions/CreateMenteeAction.php` | `CreateMenteeAction` | `BaseAction` | Creates a mentee record with associated user account |
| `Mentee/Actions/DeleteMenteeAction.php` | `DeleteMenteeAction` | `BaseAction` | Deletes a mentee record |
| `Mentee/Actions/UpdateMenteeAction.php` | `UpdateMenteeAction` | `BaseAction` | Updates a mentee's details |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentee/Entities/MenteeState.php` | `MenteeState` | `BaseEntity` | Read-only DTO for mentee's current state (registration, internship) |

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
