# Placement — API Reference
> Last updated: 2026-05-31
> Changes: docs: audit — all items Implemented

> **Legend:** ✅ Implemented = code exists | ⏳ Planned = not yet implemented

Total: 21 files — ✅ 21 Implemented

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Placement/Actions/ApprovePlacementChangeAction.php` | `ApprovePlacementChangeAction` | `BaseAction` | Approves a pending placement change request |
| `Placement/Actions/CreatePlacementAction.php` | `CreatePlacementAction` | `BaseAction` | Creates a new placement slot |
| `Placement/Actions/DeletePlacementAction.php` | `DeletePlacementAction` | `BaseAction` | Deletes an empty placement slot |
| `Placement/Actions/DirectPlacementAction.php` | `DirectPlacementAction` | `BaseAction` | Directly assigns a student to a company |
| `Placement/Actions/RejectPlacementChangeAction.php` | `RejectPlacementChangeAction` | `BaseAction` | Rejects a placement change request |
| `Placement/Actions/RequestPlacementChangeAction.php` | `RequestPlacementChangeAction` | `BaseAction` | Submits a request to change placement |
| `Placement/Actions/UpdatePlacementAction.php` | `UpdatePlacementAction` | `BaseAction` | Updates placement details |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Placement/Entities/PlacementCapacity.php` | `PlacementCapacity` | `BaseEntity` | Read-only DTO for company placement capacity |
| `Placement/Entities/PlacementState.php` | `PlacementState` | `BaseEntity` | Read-only DTO for placement state |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Placement/Enums/PlacementChangeStatus.php` | `PlacementChangeStatus` | `StatusEnum` | Placement change request lifecycle status |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Placement/Livewire/DirectPlacementManager.php` | `DirectPlacementManager` | `Component` | Direct student-to-company assignment |
| `Placement/Livewire/PlacementChangeManager.php` | `PlacementChangeManager` | `BaseRecordManager` | Manages placement change requests |
| `Placement/Livewire/PlacementIndex.php` | `PlacementIndex` | `BaseRecordManager` | Paginated list of all placements |
| `Placement/Livewire/StudentPlacementChangeRequest.php` | `StudentPlacementChangeRequest` | `Component` | Student-facing placement change form |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Placement/Models/Placement.php` | `Placement` | `BaseModel` | Eloquent model for student placements |
| `Placement/Models/PlacementChangeRequest.php` | `PlacementChangeRequest` | `BaseModel` | Eloquent model for placement change requests |

### Livewire Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `Placement/Livewire/Forms/DirectPlacementForm.php` | `DirectPlacementForm` | `Form` | student_id, placement_id, academic_year, mentor_ids | `DirectPlacementManager` |
| `Placement/Livewire/Forms/PlacementChangeForm.php` | `PlacementChangeForm` | `Form` | to_placement_id, reason | `StudentPlacementChangeRequest` |
| `Placement/Livewire/Forms/PlacementForm.php` | `PlacementForm` | `Form` | company_id, internship_id, name, address, quota, description | `PlacementIndex` |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Placement/Policies/PlacementChangeRequestPolicy.php` | `PlacementChangeRequestPolicy` | `BasePolicy` | Authorization for placement change operations |
| `Placement/Policies/PlacementPolicy.php` | `PlacementPolicy` | `BasePolicy` | Authorization for placement CRUD operations |

## Where to Find It

- `app/Domain/Placement/Models/`
- `app/Domain/Placement/Actions/`

## Dependency Graph

```
Placement Domain
├── Core         → BaseModel, BaseAction, SmartLogger
├── Partnership  → Company/partner records (placement host)
├── Internship   → Internship records (placement target)
├── User         → User model (placed student/mentor)
├── Registration → Registration records (placement context)
├── Mentee       → Mentee records (student placement)
└── Mentor       → Mentor records (supervisor placement)
```

Consumed by:
  Registration (placement assignment), Internship (placement execution)

