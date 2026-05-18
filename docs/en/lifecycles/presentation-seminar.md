# Presentation & Seminar («Sidang PKL»)

**Event:** Conducting oral presentations where students defend their internship results before a panel of examiners.

**Phase:** 5 — Assessment & Evaluation

**Previous Event:** [Final Report](final-report.md)

**Next Event:** [Assessment & Scoring](assessment-scoring.md)

---

## Overview

The presentation/seminar (Sidang PKL) is an oral examination where students present their internship outcomes to a panel of school examiners. It is an **optional** component — configurable per internship.

The presentation is assessed separately from the written report. If both exist, the final composite score can be calculated from weighted components.

## Trigger

- Student's report has been approved (recommended but not required)
- Admin schedules presentation sessions
- Internship is approaching its end date

## Pre-conditions

- Student has an active or recently completed registration
- At least one examiner assigned per session
- User is logged in as Super Admin, Admin, or Teacher

## Actors

| Actor | Role | Can schedule | Can examine | Can score |
|---|---|---|---|---|
| Super Admin | System administrator | Yes | Yes | Yes |
| Admin | School administrator | Yes | Yes | Yes |
| Teacher | Academic supervisor | No | Yes (appointed) | Yes |

> Supervisor is not involved.

---

## Event A: Scheduling Presentations

### Flow

```
Admin → Presentations → Schedule → Set Details → Save
```

Navigate to **Admin → Presentations**.

| Field | Description |
|---|---|
| **Registration** | Which student is presenting |
| **Scheduled At** | Date and time |
| **Location** | Room or meeting link |
| **Examiners** | Panel of teachers/admins (min 1, max 5) |
| **Notes** | Internal instructions |

`SchedulePresentationAction` (`app/Actions/Presentation/SchedulePresentationAction.php`) creates the Presentation record with status `SCHEDULED` and creates `PresentationExaminer` records for each examiner.

---

## Event B: Scoring the Presentation

Each examiner opens the presentation scoring form and enters their assessment.

| Field | Description |
|---|---|
| **Score** | Numeric, 0-100 |
| **Feedback** | Optional |

Examiners can edit their scores until the presentation is marked as `COMPLETED`.

### Score Aggregation

```
final_presentation_score = average of all examiner scores
```

---

## Event C: Completing the Presentation

`CompletePresentationAction` (`app/Actions/Presentation/CompletePresentationAction.php`):
1. Sets status to `COMPLETED`
2. Computes `presentation_score` as average of examiner scores
3. If report score exists: computes `final_score = (report_weight × report_score) + (presentation_weight × presentation_score)`

Weights default to 50/50. If no report score exists, presentation score is used as the final score.

## Presentation Status Lifecycle

```
SCHEDULED ──► COMPLETED (terminal)
SCHEDULED ──► CANCELLED (terminal)
```

Defined in `App\Enums\Presentation\PresentationStatus`.

---

## Models

| Model | Table |
|---|---|
| `App\Models\Presentation` | `presentations` |
| `App\Models\PresentationExaminer` | `presentation_examiners` |

## Actions

| Action | Purpose |
|---|---|
| `SchedulePresentationAction` | Creates SCHEDULED presentation with examiner assignments |
| `ScorePresentationAction` | Records examiner score |
| `CompletePresentationAction` | Finalizes, computes average, optionally composites with report score |

## Livewire Component

| Component | Route | View |
|---|---|---|
| `App\Livewire\Presentation\PresentationSchedule` | `admin/presentations` (name: `admin.presentations`) | `livewire.presentation.presentation-schedule` |

## Key Rules

| Rule | Enforcement |
|---|---|
| **Presentation is optional** | Configurable flag on internship (not yet implemented) |
| **Examiners are teachers/admins** | Supervisor cannot be an examiner |
| **One presentation per registration** | Single record per registration |
| **All examiners score independently** | Scores stored per examiner in `presentation_examiners` |
| **No supervisor dependency** | Entirely managed by teachers/admins |

## Seamless Connection

The presentation score can feed into the assessment rubric as a system-role competency indicator.
