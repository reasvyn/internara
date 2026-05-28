# Placement Domain

## Purpose

Placement bridges supply (company slots) and demand (students needing host organizations).
Manages slot capacity, direct assignments, and change requests.

---

## Design Principles

### 1. Quota Enforcement

Placement quotas are atomic — never exceed capacity. Increment/decrement operations use
locking to prevent over-allocation under concurrent requests.

### 2. Change Request Workflow

Students request placement changes; admins approve or reject. Each change is logged.

---

## Models

| Model | Key Fields |
|---|---|
| `Placement` | company_id, internship_id, name, quota, filled_quota |
| `PlacementChangeRequest` | registration_id, from_placement_id, to_placement_id, reason, status |

## Actions

| Action | Type |
|---|---|
| `CreatePlacementAction` | Command |
| `UpdatePlacementAction` | Command |
| `DeletePlacementAction` | Command |
| `DirectPlacementAction` | Command |
| `RequestPlacementChangeAction` | Command |
| `ApprovePlacementChangeAction` | Command |
| `RejectPlacementChangeAction` | Command |

## Where to Find It

- `app/Domain/Placement/Models/`
- `app/Domain/Placement/Actions/`
