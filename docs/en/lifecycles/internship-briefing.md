# Internship Briefing / Pre-Departure Orientation

**Event:** Conducting pre-departure briefing sessions for students before they begin their internship.

**Phase:** 3 — Registration & Placement (pre-operations preparation)

**Previous Event:** [Student Registration](student-registration.md)

**Next Event:** [Logbook Workflow](logbook-workflow.md), [Attendance Tracking](attendance-tracking.md)

---

## Overview

Before students begin their internship at host companies, schools typically conduct a briefing session (Pembekalan PKL). This covers workplace ethics, occupational safety (K3), reporting procedures, disciplinary rules, and administrative instructions.

The system tracks briefing sessions and optionally enforces attendance as a prerequisite for starting operational activities — configurable per internship.

## Trigger

- Start of a new internship period (batch briefing)
- School policy requires pre-departure orientation

## Pre-conditions

- At least one student has an active registration
- User is logged in as Super Admin, Admin, or Teacher

## Actors

| Actor | Role | Can create briefing | Can record attendance |
|---|---|---|---|
| Super Admin | System administrator | Yes | Yes |
| Admin | School administrator | Yes | Yes |
| Teacher | Academic supervisor | Yes (own students) | Yes (own students) |

---

## Event A: Creating a Briefing Session

### Flow

```
Admin → Internships → Briefings → Create → Fill Details → Save
```

Navigate to **Admin → Internships → Briefings**.

| Field | Validation | Description |
|---|---|---|
| **Title** | Required, max 255 | e.g., "Pembekalan PKL 2025/2026" |
| **Internship** | Required, exists | Which internship this briefing is for |
| **Date & Time** | Required, datetime | When the briefing is held |
| **Location** | Optional, max 255 | Venue or meeting link |
| **Is Mandatory** | Boolean, default true | Whether attendance gates operations |
| **Description** | Optional, text | Agenda, topics, speaker info |

The `CreateBriefingAction` (`app/Actions/Briefing/CreateBriefingAction.php`) creates the session. Multiple briefings can exist per internship.

---

## Event B: Recording Attendance

### Flow

```
Teacher → Briefings → Select Session → Mark Students → Save
```

1. Teacher opens the briefing session via `BriefingManager::manageAttendance()`
2. Views list of enrolled students
3. Marks each student as **Attended** or **Not Attended**
4. Submits — `RecordBriefingAttendanceAction` saves all records via `updateOrCreate`

Attendance can also be recorded **after** the session (retroactively).

### Admin Override

Admin can manually mark any student as attended via `OverrideBriefingAttendanceAction` (`app/Actions/Briefing/OverrideBriefingAttendanceAction.php`). This is logged in the audit trail.

---

## Optional Gate: Mandatory Briefing

When `is_mandatory = true` for a briefing session, the affected students must be marked `attended` before:

- Their first logbook entry can be created (checked in `CreateLogbookAction`)
- Their first attendance record can be created (checked in `ClockInAction`)

The check uses `Briefing::hasStudentCompletedMandatoryBriefing($userId, $internshipId)` (`app/Models/Briefing.php`):

```php
Briefing::where('internship_id', $internshipId)
    ->where('is_mandatory', true)
    ->exists() && BriefingAttendance::where(...)->where('attended', true)->exists()
```

If no mandatory briefing exists for the internship, the check returns `true` — no gate applies.

**If the briefing is mandatory but the student did not attend**, the teacher/admin can:
1. Mark the student as attended anyway via the attendance modal
2. Create a make-up briefing session
3. Wait for the next batch briefing

The gate is **configurable per briefing** — only briefings with `is_mandatory = true` trigger the check.

---

## Models

| Model | Table | Key Fields |
|---|---|---|
| `App\Models\Briefing` | `briefings` | `title`, `date`, `location`, `is_mandatory`, `internship_id`, `created_by` |
| `App\Models\BriefingAttendance` | `briefing_attendances` | `briefing_id`, `user_id`, `attended`, `notes` (unique: `briefing_id` + `user_id`) |

## Actions

| Action | Purpose |
|---|---|
| `App\Actions\Briefing\CreateBriefingAction` | Creates a briefing session |
| `App\Actions\Briefing\RecordBriefingAttendanceAction` | Records student attendance in bulk |
| `App\Actions\Briefing\OverrideBriefingAttendanceAction` | Admin overrides a single student's attendance |

## Livewire Component

| Component | Route | View |
|---|---|---|
| `App\Livewire\Internship\BriefingManager` | `admin/internships/briefings` (name: `admin.internships.briefings`) | `livewire.internship.briefing-manager` |

## Key Rules

| Rule | Enforcement |
|---|---|
| **Briefing is per internship** | Linked via `internship_id` |
| **Attendance records per student** | `BriefingAttendance` with `unique(['briefing_id', 'user_id'])` |
| **Mandatory flag is configurable** | Per briefing session |
| **Gate only applies if mandatory** | `hasStudentCompletedMandatoryBriefing` skips if no mandatory briefing exists |
| **Gate enforced at Action layer** | `CreateLogbookAction` and `ClockInAction` call the check |
| **Admin override permitted** | `OverrideBriefingAttendanceAction` logged in audit trail |
| **No supervisor dependency** | Teacher/admin records all attendance |

## Error Handling

| Failure | Behavior |
|---|---|
| Student already marked | `updateOrCreate` replaces previous record |
| Briefing linked to non-existent internship | Foreign key constraint |

## Seamless Connection

After briefing completion, students proceed to:

- **[Logbook Workflow](logbook-workflow.md)** — record daily activities (gated if mandatory)
- **[Attendance Tracking](attendance-tracking.md)** — daily presence records (gated if mandatory)
