# Schedule — API Reference
> Last updated: 2026-05-23
> Changes: fix: complete system initialization overhaul — security, middleware, recovery, form objects, docs


Total: 7 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Schedule/Actions/CreateScheduleAction.php` | `CreateScheduleAction` | `BaseAction` | Creates a new schedule entry |
| `Schedule/Actions/DeleteScheduleAction.php` | `DeleteScheduleAction` | `BaseAction` | Deletes a schedule entry |
| `Schedule/Actions/UpdateScheduleAction.php` | `UpdateScheduleAction` | `BaseAction` | Updates a schedule entry |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Schedule/Entities/ScheduleStatus.php` | `ScheduleStatus` | `BaseEntity` | Read-only DTO for schedule entry status |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Schedule/Livewire/ScheduleIndex.php` | `ScheduleIndex` | `Component` | Schedule management interface |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Schedule/Models/Schedule.php` | `Schedule` | `BaseModel` | Eloquent model for schedules |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Schedule/Policies/SchedulePolicy.php` | `SchedulePolicy` | `BasePolicy` | Authorization for schedule operations |

## Where to Find It

- `app/Domain/Schedule/Models/Schedule.php`
- `app/Domain/Schedule/Actions/`
