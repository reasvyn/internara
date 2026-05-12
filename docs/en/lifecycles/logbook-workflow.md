# Logbook Workflow

**Event:** Recording and verifying daily internship journal entries.

**Phase:** 4 — Operations

**Previous Event:** [Student Registration](student-registration.md)

**Next Events:** [Attendance Tracking](attendance-tracking.md), [Assignment Workflow](assignment-workflow.md), [Supervision Process](supervision-process.md)

---

## Overview

The logbook (daily journal) is the primary mechanism for students to document their daily activities during the internship. Each day produces one entry that is reviewed and verified by the school teacher.

## Trigger

- Student's daily routine during active internship
- Teacher's periodic review cycle

## Pre-conditions

- Student has an **active** registration (see [Student Registration](student-registration.md))
- Student is within the internship period (current date between registration start and end)
- Internship status is **Active**
- User is logged in with role STUDENT (to create) or TEACHER (to verify)

## Actors

| Actor | Role | Can create | Can edit | Can verify | Can request revision |
|---|---|---|---|---|---|
| Student | STUDENT | Yes (own) | Yes (own, if draft/revision) | No | No |
| School Teacher | TEACHER | No | No | Yes | Yes |
| Admin | ADMIN, SUPER_ADMIN | No | No | Yes | Yes |

---

## Logbook Status Lifecycle

The logbook follows a 4-state lifecycle (DRAFT → SUBMITTED → VERIFIED, with REVISION_REQUIRED loop) defined in the [System Lifecycle](system-lifecycle.md#logbook-state-machine).

---

## Event A: Creating a Logbook Entry

### Flow

```
Student → Logbook → New Entry → Fill Details → Save as Draft / Submit
```

Navigate to **Student → Logbook**.

| Field | Validation | Description |
|---|---|---|
| **Date** | Required, date, defaults to today | Entry date |
| **Content** | Required, min 10 chars | Description of today's activities |
| **Learning Outcomes** | Optional | What the student learned |

### Save as Draft

The entry is saved with status `DRAFT`. The student can edit it later. Only one draft per day is allowed — editing the draft overwrites it.

### Submit

The `SubmitLogbookAction` transitions the entry:

1. Finds the student's active registration
2. Checks no submitted entry exists for today (prevents duplicates)
3. Uses `updateOrCreate` — if a draft exists, upgrades it to SUBMITTED
4. If no draft exists, creates and submits in one step
5. Status becomes **SUBMITTED**

Once submitted, the student cannot edit the entry (unless the teacher requests revision).

---

## Event B: Teacher Reviews Entry

### Flow

```
Teacher → Logbook Manager → Review Entry → Verify / Request Revision
```

The teacher sees all logbook entries for students under their supervision. Filters by student, date range, and status.

### Option 1: Verify

Teacher clicks **Verify**:

- Status transitions to **VERIFIED**
- Entry is finalized — cannot be edited or changed
- Student is notified

### Option 2: Request Revision

Teacher clicks **Request Revision**:

- Status transitions to **REVISION_REQUIRED**
- Teacher adds feedback notes
- Student receives notification with feedback
- Student can edit and resubmit

---

## Event C: Student Revises and Resubmits

1. Student sees the feedback
2. Edits the content (entry is in DRAFT-like state)
3. Resubmits — status returns to **SUBMITTED**
4. Teacher reviews again

---

## Key Rules

| Rule | Enforcement |
|---|---|
| **One entry per day** | Only one SUBMITTED entry allowed per day per student |
| **Draft overwrites** | Editing a draft replaces the previous content |
| **Verified is terminal** | Once VERIFIED, no further changes |
| **Active registration required** | Cannot create logbook without active registration |
| **Within internship period** | `MenteeState::canSubmitLogbook()` checks period bounds |

## Admin/Teacher Logbook Manager

The **Logbook Manager** (accessible to admins and teachers) provides:

- **List view** — all logbook entries for supervised students with search, filters, sorting
- **Create for student** — teacher can create an entry on behalf of a student
- **Edit** — update entry content (for draft/revision entries)
- **Verify** — approve submitted entries
- **Delete** — remove entries
- **Bulk delete** — mass removal of selected entries

## Seamless Connection

While the logbook captures daily activities, other operational events run in parallel:

- **[Attendance Tracking](attendance-tracking.md)** — records the student's physical presence
- **[Assignment Workflow](assignment-workflow.md)** — measures task completion
- **[Supervision Process](supervision-process.md)** — documents mentor interactions

All of these feed into the **[Assessment & Scoring](assessment-scoring.md)** phase, where logbook completion rates may contribute to the final grade.
