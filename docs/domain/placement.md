# Placement Domain

## Purpose

Placement manages slots at partner companies and assigns students to them. It bridges the gap between supply (slots defined per company per program) and demand (students who have registered and need a host organization). This domain ensures that every registered student has a confirmed slot and that no company's capacity is exceeded.

## Boundary

**In scope:** Placement slot definition (per company per program with total capacity and filled count), slot capacity management (track available vs assigned), student-to-slot assignment (manual/placement wizard, verification, and direct placement by admin), placement change requests (student-initiated, admin-approved with atomic slot swap).

**Out of scope:** Company partnership agreement management (Partnership domain owns agreement terms), internship program definition (Internship domain), student registration and application processing (Registration domain), mentor assignment (Mentor domain).

## Key Concepts

**Placement Slots.** A slot represents positions at a specific company for a specific internship program. Each slot tracks: the company, the internship program, total capacity (`quota`), currently filled positions (`filled_quota`), and a description. Capacity is enforced at write time — `filled_quota` is atomically incremented during placement assignment and decremented during changes or removals.

**Student Assignment Flow** has two entry points:

1. **Via Registration Verification (standard flow):** An admin reviews a pending registration in `RegistrationVerification`, selects a placement and optional mentors, and confirms. This calls `VerifyRegistrationAction` which sets the registration to `active` and increments the slot's `filled_quota`.

2. **Via Direct Placement (manual flow):** An admin uses `DirectPlacementManager` to assign a student who may not have gone through the standard registration wizard. This calls `DirectPlacementAction` which auto-creates a Mentee + Registration in `active` status and increments the slot's `filled_quota`.

**Placement Capacity** is tracked via `PlacementCapacity` entity which provides `isFull()`, `availableSlots()`, and `hasAvailableSlots()` methods based on `quota` vs `filled_quota`.

**Change Requests.** Students with an active placement can request a change via `StudentPlacementChangeRequest`. Each request captures: current placement (`from_placement_id`), desired placement (`to_placement_id`), reason, and requester identity. An admin reviews via `PlacementChangeManager` and can approve (atomic swap: decrement old slot, increment new slot, update registration) or reject with a reason.

## Requirements

### User Stories & Rules

**Slot Management**
- **Admin:** As an admin, I want to create placement slots per company and program so that available positions are tracked
- **Admin:** As an admin, I want to update slot capacity so that it matches partnership agreements
- **Admin:** As an admin, I want to delete empty slots so that the system stays clean
- A slot cannot be deleted if it has active registrations — must be empty first
- `filled_quota` must never exceed `quota` — enforced at the application level

**Student Assignment**
- **Admin:** As an admin, I want to assign students to slots during registration verification so that placement is part of the standard workflow
- **Admin:** As an admin, I want to directly place a student in a slot so that exceptional cases are handled
- A slot cannot receive more students than its remaining capacity

**Change Requests**
- **Student:** As a student, I want to request a placement change if my circumstances require it
- **Admin:** As an admin, I want to approve or reject change requests so that transfers are managed fairly
- Change requests execute atomically: the old slot is released and the new slot assigned in a single transaction, eliminating gap states
- A pending change request blocks new requests for the same registration (one at a time)

### Process Flow

```
Standard Assignment:
  Registration [pending] → Admin verifies (selects placement + mentors)
                         → Registration [active], slot.filled_quota++

Direct Placement:
  Student selected → Admin picks placement + mentors
                   → Mentee created → Registration [active], slot.filled_quota++

Change Request:
  Student submits [pending] → Admin approves
                            → old slot.filled_quota--, new slot.filled_quota++
                            → Registration updated to new placement
  Student submits [pending] → Admin rejects (with reason)
                            → Request marked rejected
```

### Key Operations

| Action | Description |
|--------|-------------|
| `CreatePlacementAction` | Creates a new placement slot with quota |
| `UpdatePlacementAction` | Updates slot details (name, address, quota, description) |
| `DeletePlacementAction` | Deletes an empty placement slot |
| `DirectPlacementAction` | Directly assigns a student to a slot (creates Mentee + Registration) |
| `RequestPlacementChangeAction` | Student initiates a placement change request |
| `ApprovePlacementChangeAction` | Admin approves (atomic slot swap: decrement old, increment new) |
| `RejectPlacementChangeAction` | Admin rejects a change request with reason |

### Livewire Components

| Component | Access | Description |
|-----------|--------|-------------|
| `PlacementIndex` | Admin | CRUD management for placement slots (table, create/edit modal, delete) |
| `DirectPlacementManager` | Admin | Form to directly assign a student to a slot |
| `PlacementChangeManager` | Admin | Reviews pending change requests (approve/reject with reason) |
| `StudentPlacementChangeRequest` | Student | Form to submit a placement change request |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Placement` (`placements`), `PlacementChangeRequest` |
| **Entities** | `PlacementState` (deletion gating — canBeDeleted), `PlacementCapacity` (capacity calculation — isFull, availableSlots) |
| **Enums** | `PlacementChangeStatus` — `PENDING`, `APPROVED`, `REJECTED` (implements `LabelEnum`, `StatusEnum`) |
| **Policies** | `PlacementPolicy` (placement CRUD), `PlacementChangeRequestPolicy` (change request CRUD) |

## Dependencies

| Dependency | Reason |
|---|---|
| Internship | Programs define which placements are needed and their date ranges |
| Partnership | Companies and their active agreements provide capacity ceilings |
| Registration | Student registrations create placement demand |
| Mentee | Direct placement auto-creates Mentee records |
| Core | BaseAction, BaseModel, SmartLogger, BaseRecordManager |

## Routes

| URL | Name | Component | Middleware |
|-----|------|-----------|------------|
| `/admin/internships/placements` | `admin.internships.placements` | PlacementIndex | auth, role:super_admin\|admin |
| `/admin/internships/placements/direct` | `admin.internships.placements.direct` | DirectPlacementManager | auth, role:super_admin\|admin |
| `/admin/internships/placements/changes` | `admin.internships.placements.changes` | PlacementChangeManager | auth, role:super_admin\|admin |
| `/student/internships/placement-change` | `student.internships.placement-change` | StudentPlacementChangeRequest | auth, role:student |
