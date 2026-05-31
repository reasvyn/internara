# Guidance — API Reference
> Last updated: 2026-05-23
> Changes: fix: complete system initialization overhaul — security, middleware, recovery, form objects, docs


Total: 11 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Actions/AcknowledgeHandbookAction.php` | `AcknowledgeHandbookAction` | `BaseAction` | Records student acknowledgment of a handbook |
| `Guidance/Actions/CreateHandbookAction.php` | `CreateHandbookAction` | `BaseAction` | Creates a new handbook with slug |
| `Guidance/Actions/DeleteHandbookAction.php` | `DeleteHandbookAction` | `BaseAction` | Deletes a handbook |
| `Guidance/Actions/UpdateHandbookAction.php` | `UpdateHandbookAction` | `BaseAction` | Updates an existing handbook |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Entities/HandbookPublishState.php` | `HandbookPublishState` | `BaseEntity` | Read-only DTO for handbook publish state |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Livewire/HandbookIndex.php` | `HandbookIndex` | `Component` | Admin handbook management with pagination |
| `Guidance/Livewire/StudentHandbookIndex.php` | `StudentHandbookIndex` | `Component` | Student handbook view with acknowledgment |

### Livewire Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `Guidance/Livewire/Forms/HandbookForm.php` | `HandbookForm` | `Form` | title, content, version, is_active, target_audience | `HandbookIndex` |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Models/Handbook.php` | `Handbook` | `BaseModel` | Eloquent model for handbooks |
| `Guidance/Models/HandbookAcknowledgement.php` | `HandbookAcknowledgement` | `BaseModel` | Eloquent model for handbook acknowledgment records |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Policies/HandbookPolicy.php` | `HandbookPolicy` | `BasePolicy` | Authorization for handbook operations |

## Where to Find It

- `app/Domain/Guidance/Models/`
- `app/Domain/Guidance/Actions/`

## Dependency Graph

```
Guidance Domain
├── Core  → BaseModel, BaseAction, SmartLogger
└── User  → User model (author/reader identity)
```

Consumed by:
  Mentee (reading guidance), Mentor (reading guidance materials)

