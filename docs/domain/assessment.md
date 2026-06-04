# Assessment — Documentation Overview

> Last updated: 2026-06-04
> Changes: Rewrote overview with developer-friendly content, added error handling, failure modes, and CLI commands

Rubric-based competency evaluation: rubrics with weighted criteria, competency indicators, assessment grading, and presentation scheduling.

For complete technical reference including API, models, actions, and components, see [assessment-reference.md](assessment-reference.md).

---

## Key Principles

- **Rubrics define the scoring framework** — each rubric has weighted criteria, each criterion has descriptive performance levels (typically 1-5). Scores are auto-calculated from level selections.
- **Competencies live within rubrics** — competencies and their measurable indicators are grouped under rubrics. A rubric can assess multiple competencies.
- **Assessments are finalized** — once finalized, an assessment is immutable. Corrections require a new assessment round. This ensures grade integrity.
- **Presentations are panel-based** — students defend their work before a panel. Panelists score independently. Scores are aggregated after the presentation is marked COMPLETED.

---

## Context Boundary

Owns rubric and assessment definitions. Evaluation domain consumes assessment results for final scoring. Program provides the context (which program the assessment belongs to). Guidance provides mentor/student relationships used in panel assignments.

---

## Domain Rules

- **Assessment finalization is irreversible**: once finalized, scores cannot be modified. Any corrections require a new assessment round.
- **Rubric scores follow the defined performance levels** (typically 1-5, but configurable per rubric). Weighted total is auto-calculated.
- **Presentations have a lifecycle**: SCHEDULED → COMPLETED (if successful) or CANCELLED (if not). Only one active presentation per student per program.
- **Only authorized teachers/supervisors** can submit assessment scores. Students can view their own assessments but not modify them.
- **Rubrics can be reused** across multiple assessments and programs. Changes to a rubric do not affect already-finalized assessments (which store a snapshot of the rubric at time of grading).

---

## Aggregates

- **Assessment**: The actual evaluation record linking a student to a rubric within a program. Stores scores per criterion, weighted total, finalization status, and grader identity.
- **Rubric**: Evaluation framework with weighted criteria, descriptive performance levels, and scoring anchors. Versioned — finalized assessments reference the rubric snapshot at grading time.
- **Competency**: Measurable skills and knowledge areas within a rubric. Each competency has indicators that define observable behaviors.
- **Indicator**: Specific, measurable behaviors under a competency. Used by graders to determine performance level.
- **Presentation**: Panel-based evaluation event — scheduled date/time, panelist assignments, scoring, and outcome (COMPLETED/CANCELLED). Scores are independent per panelist.

---

## CLI Commands

| Command | Purpose |
|---|---|
| `php artisan assessment:finalize-overdue` | Auto-finalize assessments past their due date |

---

## Error Handling & Failure Modes

- **Modifying a finalized assessment**: The system blocks with a `RejectedException`. The UI disables all edit controls. The audit trail shows the attempted modification.
- **Incomplete rubric criteria**: If an assessment is submitted without scoring all rubric criteria, the system shows a validation error listing the unscored criteria.
- **Presentation conflict**: Scheduling a presentation when the student already has a SCHEDULED presentation triggers a conflict warning but is not blocked (panel may need rescheduling).
- **Panelist scoring discrepancy**: If panelist scores differ by more than a configurable threshold, the system flags the assessment for review before finalization.

---

## Quick References

### Actions & Business Logic
- **17** actions across all aggregates
- Rubric/competency/indicator CRUD, assessment grading, finalization, presentation scheduling, panelist management, score aggregation

### Data & Persistence
- **6** models: `Assessment`, `Rubric`, `Competency`, `Indicator`, `Presentation`, `PresentationPanelist`
- UUID PKs. `Assessment` stores rubric snapshot at grading time as JSON. `Presentation` has scheduled/completed/cancelled lifecycle

### User Interface
- **4** Livewire components
- Rubric builder (drag-and-drop criteria), assessment grading form, presentation schedule manager, finalization dashboard

### Authorization
- **1** policy
- Teachers grade own assessments, admins manage rubrics and presentations, students view own results

---

For complete technical reference, see [assessment-reference.md](assessment-reference.md).
