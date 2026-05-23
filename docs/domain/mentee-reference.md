# Mentee — API Reference

Total: 7 files

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

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentee/Livewire/StudentDashboard.php` | `StudentDashboard` | `Component` | Student dashboard showing current internship status — *moved to `User/Livewire/Dashboards/StudentDashboard.php`* |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentee/Models/Mentee.php` | `Mentee` | `BaseModel` | Eloquent model for mentee records |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Mentee/Policies/MenteePolicy.php` | `MenteePolicy` | `BasePolicy` | Authorization for mentee operations |
