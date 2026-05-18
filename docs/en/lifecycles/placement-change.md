# Placement Change / Mutation («Mutasi PKL»)

**Event:** Handling mid-internship changes to a student's company placement.

**Phase:** 4 — Operations

**Previous Event:** [Student Registration](student-registration.md)

**Next Event:** (continues operations in new placement)

---

## Overview

During the internship period, a student may need to change their placement due to company closure, internal company issues, student-family relocation, disciplinary action, or mutual agreement.

The student's registration stays **ACTIVE** throughout the process.

## Trigger

- Company terminates the student's placement
- Student requests a transfer (personal reasons)
- Company can no longer accommodate the student
- School-initiated move (disciplinary or logistical)

## Pre-conditions

- Student has an **active** registration with a placement assigned
- Target placement exists with available capacity
- User is logged in with appropriate role

## Actors

| Actor | Role | Can request | Can approve | Can reject |
|---|---|---|---|---|
| Student | STUDENT | Yes (own) | No | No |
| Teacher | TEACHER | Yes (own students) | No | No |
| Admin | ADMIN, SUPER_ADMIN | Yes | Yes | Yes |

> Only admin can approve a placement change.

---

## Event A: Requesting a Placement Change

### Student Request Flow

```
Student → Request Placement Change → Select New Placement → Submit Request
```

Navigate to **Student → Request Placement Change**.

| Field | Description |
|---|---|
| **Target Placement** | Select from available placements (same internship, with capacity) |
| **Reason** | Min 20 characters |

`RequestPlacementChangeAction` (`app/Actions/Internship/RequestPlacementChangeAction.php`):
1. Validates no pending request already exists for this registration
2. Creates a `PlacementChangeRequest` with status `PENDING`
3. Notifies admin

### Request from Admin/Teacher

Admin can also submit requests on behalf of students via the admin panel.

---

## Event B: Approving or Rejecting

### Flow

```
Admin → Placements → Changes → Review → Approve / Reject
```

Navigate to **Admin → Internships → Placements → Changes**.

### Approve

`ApprovePlacementChangeAction` (`app/Actions/Internship/ApprovePlacementChangeAction.php`):
1. Re-checks target placement capacity
2. Decrements `filled_quota` on old placement
3. Increments `filled_quota` on new placement
4. Updates registration's `placement_id` and dates
5. Sets request status to `APPROVED`

### Reject

`RejectPlacementChangeAction` (`app/Actions/Internship/RejectPlacementChangeAction.php`):
1. Sets status to `REJECTED`
2. Records rejection reason
3. Registration stays at original placement

---

## Placement Change Status Lifecycle

```
PENDING ──► APPROVED (terminal)
PENDING ──► REJECTED (terminal)
```

Defined in `App\Enums\Internship\PlacementChangeStatus`.

---

## Models

| Model | Table |
|---|---|
| `App\Models\PlacementChangeRequest` | `placement_change_requests` |

## Actions

| Action | Purpose |
|---|---|
| `RequestPlacementChangeAction` | Creates PENDING change request |
| `ApprovePlacementChangeAction` | Approves, adjusts quotas, updates registration |
| `RejectPlacementChangeAction` | Rejects with reason |

## Livewire Components

| Component | Route | View |
|---|---|---|
| `App\Livewire\Internship\PlacementChangeManager` | `admin/internships/placements/changes` | `livewire.internship.placement-change-manager` |
| `App\Livewire\Internship\StudentPlacementChangeRequest` | `student/internships/placement-change` | `livewire.internship.student-placement-change-request` |

## Key Rules

| Rule | Enforcement |
|---|---|
| **No pending duplicate requests** | One PENDING change request per registration |
| **Capacity re-checked on approval** | `PlacementCapacity::fromModel($newPlacement)->hasAvailableSlots()` |
| **Registration stays ACTIVE** | No status change during mutation |
| **Quota updated atomically** | `filled_quota` decremented/incremented in DB transaction |
| **Only admin can approve/reject** | `ApprovePlacementChangeAction` and `RejectPlacementChangeAction` |
| **Historical placement preserved** | Audit trail records old and new placement IDs |
