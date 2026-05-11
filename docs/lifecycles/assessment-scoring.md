# Assessment & Scoring

**Event:** Evaluating student performance through rubric-based assessment.

**Phase:** 5 — Assessment & Evaluation

**Previous Events:** [Logbook Workflow](logbook-workflow.md), [Assignment Workflow](assignment-workflow.md)

**Next Events:** [Mentor Evaluation](mentor-evaluation.md), [Period Closing](period-closing.md)

---

## Overview

Assessments measure student performance against predefined rubrics. Each rubric contains weighted competencies with indicators, and multiple evaluators (teachers, supervisors) can score different sections. The final score is calculated using weighted normalization.

## Trigger

- Approaching end of the internship period
- Teacher initiates final assessment
- Continuous scoring throughout the internship (optional)

## Pre-conditions

- Student has an **active** or recently completed registration
- A **rubric** is defined and active for the internship
- Evaluators are assigned (teachers, supervisors with appropriate roles)
- User is logged in with appropriate role to score or finalize

## Actors

| Actor | Role | Can score indicators | Can auto-import | Can finalize |
|---|---|---|---|---|
| School Teacher | TEACHER | Yes (own competencies) | Yes | Yes |
| Admin | ADMIN, SUPER_ADMIN | Yes (all) | Yes | No |
| Supervisor | SUPERVISOR | Yes (own competencies) | No | No |
| System | SYSTEM (auto) | Auto-calculated | N/A | N/A |

---

## Event A: Rubric Management

Before assessment can begin, a rubric must exist.

### Creating a Rubric

```
Admin → Assessments → Rubrics → Create
```

| Field | Validation |
|---|---|
| **Name** | Required, max 255 |
| **Description** | Optional, max 5000 |
| **Is Active** | Boolean, defaults to true |
| **Created By** | Auto-set to current user |

### Adding Competencies

Each rubric has one or more competencies.

| Field | Validation | Description |
|---|---|---|
| **Name** | Required, max 255 | e.g., "Technical Skills" |
| **Description** | Optional | Detail about this competency |
| **Weight** | Integer 0-100 | Relative importance |
| **Evaluator Role** | Required | Who scores this: admin, teacher, supervisor, system |
| **Order** | Integer | Display order |

A competency with `evaluator_role = system` is auto-calculated (e.g., from attendance rate or logbook completion).

### Adding Indicators

Each competency has one or more indicators.

| Field | Validation | Description |
|---|---|---|
| **Name** | Required, max 255 | e.g., "Code Quality" |
| **Description** | Optional | Scoring criteria |
| **Max Score** | Numeric 1-999 | Maximum score for this indicator |
| **Weight** | Integer 0-100 | Weight within the competency |
| **Order** | Integer | Display order |

---

## Event B: Assessment Initialization

When a teacher or supervisor opens the assessment page for a student registration:

1. The system finds the rubric linked to the internship
2. Finds or creates an Assessment record via `InitializeAssessmentAction`
3. Loads all competencies and indicators from the rubric
4. Filters competencies by the current user's role:
   - **Admin/Super Admin** — sees all competencies
   - **Teacher** — sees competencies assigned to `teacher` role
   - **Supervisor** — sees competencies assigned to `supervisor` role
5. Loads any existing scores into the UI

---

## Event C: Scoring Indicators

Evaluators enter scores for each indicator they are authorized to assess.

### Manual Scoring

1. Teacher/supervisor opens the assessment grading interface
2. Enters scores for each visible indicator
3. Each score is saved in real-time via `UpdateAssessmentScoresAction`:
   - Records the evaluator ID and evaluation timestamp per competency
   - Scores are stored in the assessment's content JSON (`competencies.{id}.indicators.{id}`)
   - Scores persist immediately (no "save" button needed)

### Auto-Import Scores

Teachers can auto-import scores from assignment submissions and logbooks:

1. Clicks **Auto Import**
2. `AutoCalculateAssessmentAction` scans:
   - Assignment submission scores (for graded assignments)
   - Logbook completion rate (percentage of days with submitted entries)
3. Populates applicable indicators with calculated scores

---

## Event D: Assessment Finalization

**Who:** School Teacher only (per `MentorRole::canFinalizeAssessment()`)

### Flow

```
Teacher → Open Assessment → Review Scores → Finalize
```

`FinalizeAssessmentAction` executes:

1. Validates assessment is not already finalized
2. Validates a rubric is attached
3. Validates at least one competency has been scored
4. For each competency:
   - For each indicator: `normalized = (score / max_score) × 100`
   - `competencyScore += normalized × (indicator.weight / 100)`
5. Total score:
   - `totalWeightedScore += competencyScore × (competency.weight / 100)`
6. Saves: final score, `finalized_at` timestamp, `evaluator_id`
7. Assessment becomes **finalized** — no further changes allowed

### Score Calculation Example

```
Competency: Technical Skills (weight: 60)

  Indicator: Code Quality (max: 100, weight: 50)
    Score: 80 → normalized = 80/100 × 100 = 80
    Contribution: 80 × 50/100 = 40

  Indicator: Documentation (max: 50, weight: 50)
    Score: 40 → normalized = 40/50 × 100 = 80
    Contribution: 80 × 50/100 = 40

  Competency Score: 40 + 40 = 80
  Weighted: 80 × 60/100 = 48

Competency: Soft Skills (weight: 40)

  Indicator: Communication (max: 100, weight: 100)
    Score: 90 → normalized = 90/100 × 100 = 90
    Contribution: 90 × 100/100 = 90

  Competency Score: 90
  Weighted: 90 × 40/100 = 36

─────────────────────────────────────────
  TOTAL SCORE: 48 + 36 = 84 / 100
```

---

## Event E: Role-Based Visibility

The system filters competencies based on evaluator roles:

| User Role | Sees competencies with | Can finalize? |
|---|---|---|
| Admin / Super Admin | All roles | No |
| Teacher | `teacher` role | Yes |
| Supervisor | `supervisor` role | No |
| Student (view only) | All (read-only) | No |

Teachers also verify they are assigned as a mentor to the student before they can score `teacher`-role competencies.

---

## Key Rules

| Rule | Enforcement |
|---|---|
| **One assessment per registration** | `firstOrCreate` on registration_id |
| **Finalization is one-time** | `finalized_at` must be null |
| **Rubric required** | Cannot finalize without a rubric |
| **At least one competency scored** | Finalization validates this |
| **Score bounds** | Each indicator score ≤ max_score |
| **No changes after finalization** | `isFinalized` guard on all mutations |

## Assessment Result Entity

The `AssessmentResult` entity provides:
- `isFinalized()` — whether the assessment is final
- `calculateTotalScore()` — recomputes the total from content

## Seamless Connection

Assessment scores represent the student's final performance measurement:

- **[Mentor Evaluation](mentor-evaluation.md)** — evaluates the mentor's performance
- **[Period Closing](period-closing.md)** — all assessments should be finalized before closure
- **[Report Generation](report-generation.md)** — scores feed into completion reports
