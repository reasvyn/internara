# Internship Creation

**Event:** Creating, publishing, and managing internship programs.

**Phase:** 2 — Internship Planning

**Previous Event:** [School Configuration](school-configuration.md)

**Next Event:** [Company & Placement Management](company-placement.md)

---

## Overview

An internship program (internship) is a time-bounded period during which students work at partner companies under academic and industry supervision. Multiple internships can exist across different departments, academic years, or industry sectors.

## Trigger

- Start of a new academic period (annual cycle)
- New industry partnership requires a dedicated program
- Special program outside the standard cycle (ad-hoc)

## Pre-conditions

- [School Configuration](school-configuration.md) is complete (at least one active academic year)
- User is logged in as Super Admin, Admin, or Teacher
- Partner companies have been registered (optional for draft, required before publishing)

## Actors

| Actor | Role | Can create | Can publish | Can complete | Can delete |
|---|---|---|---|---|---|
| Super Admin | System administrator | Yes | Yes | Yes | Yes |
| Admin | School administrator | Yes | Yes | Yes | Yes |
| Teacher | Academic supervisor | Yes | Yes | Yes | No (if dependencies exist) |

---

## Event A: Creating an Internship

### Flow

```
Admin → Internships → Create → Fill Details → Save (Draft)
```

Navigate to **Admin → Internships** and click **Create**.

| Field | Validation | Description |
|---|---|---|
| **Name** | Required, max 255, unique | e.g., "PKL Teknik Komputer 2025/2026" |
| **Start Date** | Required, date | When the internship period begins |
| **End Date** | Required, date, after start date | When the internship period ends |
| **Registration Start Date** | Optional, date | When student registration opens |
| **Registration End Date** | Optional, date, ≥ registration start | When student registration closes |
| **Description** | Optional | Program description, objectives, notes |
| **Status** | Required, defaults to DRAFT | Initial status |

The `CreateInternshipAction` executes:

1. Validates input data
2. If no `academic_year_id` provided, auto-assigns the currently active academic year
3. Creates the Internship record with status `DRAFT`
4. Logs audit: `internship_created`
5. Dispatches `InternshipCreated` domain event

### Document Requirements

After creation, admin can attach document requirements:

```
Admin → Internships → Requirements → Add Document
```

Each requirement specifies:
- **Document** — from the available document templates
- **Is Mandatory** — whether submission is required for registration

Students must upload and have these documents verified during registration.

---

## Event B: Publishing an Internship

### Flow

```
Admin → Internships → Edit → Set Status to Published → Save
```

Transition: **DRAFT → PUBLISHED**

Pre-conditions:
- At least one placement slot exists (otherwise students cannot register)
- Start and end dates are in the future (or at least valid)

Post-conditions:
- Internship is visible to students for registration
- Registration is accepted if current date falls within the registration window

---

## Event C: Activating an Internship

### Flow

```
Admin → Internships → Edit → Set Status to Active → Save
```

Transition: **PUBLISHED → ACTIVE**

This is set manually or automatically when the start date arrives. During ACTIVE:

- Students with approved registrations can begin operational activities
- Logbook entries, attendance, assignments, and supervision become available
- Registration may still be open (if within the registration window)

---

## Event D: Completing an Internship

### Flow

```
Admin → Internships → Edit → Set Status to Completed → Save
```

Transition: **ACTIVE → COMPLETED**

Pre-conditions (recommended):
- All assessments are finalized
- All submissions are graded
- All supervision logs are verified
- All attendance records are reconciled

Post-conditions:
- No further operational activities (logbook, attendance, etc.) can be created
- Existing records are read-only
- The internship is terminal — no further status changes

---

## Event E: Cancelling an Internship

### Flow

Transition: **DRAFT/PUBLISHED/ACTIVE → CANCELLED**

Pre-conditions:
- No active registrations with ongoing activities (recommended)

Post-conditions:
- Same as COMPLETED — terminal state
- Students with active registrations must be handled separately

---

## Internship Status Lifecycle

The internship follows a 5-state lifecycle (DRAFT → PUBLISHED → ACTIVE → COMPLETED/CANCELLED) defined in the [System Lifecycle](system-lifecycle.md#5-phase-2-internship-planning). See the Complete State Transition Map for all valid transitions.

## Key Rules

| Rule | Enforcement |
|---|---|
| **Unique name per internship** | Database unique constraint |
| **Dates must be valid** | End date after start date, registration window within period |
| **Default academic year** | Auto-assigned from active academic year |
| **Status transition validation** | `InternshipStatus::canTransitionTo()` checks validity |
| **Terminal states** | COMPLETED and CANCELLED cannot transition further |
| **Delete guard** | Cannot delete if placements or registrations exist |
| **Registration acceptance** | `InternshipPeriod::isAcceptingRegistrations()` checks status AND dates |

## Batch Operations

### Close All Filtered

Super Admin or Admin can mass-transition filtered internships to COMPLETED:

1. Apply filters (by academic year, department, date range, etc.)
2. Click **Close All Filtered**
3. `BatchUpdateInternshipStatusAction` updates all matching records

### Delete Selected

Selected internships can be bulk-deleted (if no dependencies exist).

## State Changes

| Component | Before | After |
|---|---|---|
| Internship | Not created / Draft | Created with specified status |
| Academic year link | — | Internship linked to active academic year |
| Document requirements | — | Attached to internship (optional) |
| Activity log | — | Audit entry for creation/update |

## Error Handling

| Failure | Behavior |
|---|---|
| Invalid status transition | Error message with valid targets |
| Name already exists | Unique validation error |
| End date before start date | Validation error |
| Delete with dependencies | Blocked with explanation |
| No active academic year | Error — cannot create without active year |

## Seamless Connection

After creating and publishing an internship:

- **[Company & Placement Management](company-placement.md)** — define where students will work and how many slots are available
- **[Student Registration](student-registration.md)** — students can start registering once the internship is published
