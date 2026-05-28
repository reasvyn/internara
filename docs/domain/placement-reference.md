# Placement — API Reference
> Last updated: 2026-05-26
> Changes: fix: enforce super admin integrity with SuperAdminIntegrityRules across all code paths


Total: 18 files

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

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Placement/Policies/PlacementChangeRequestPolicy.php` | `PlacementChangeRequestPolicy` | `BasePolicy` | Authorization for placement change operations |
| `Placement/Policies/PlacementPolicy.php` | `PlacementPolicy` | `BasePolicy` | Authorization for placement CRUD operations |
