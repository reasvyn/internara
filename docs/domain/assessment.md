# Assessment Domain

## Purpose

Assessment provides rubric-based competency evaluation — defining criteria, scoring students
against rubrics, and managing presentation exams.

---

## Models

| Model | Key Fields |
|---|---|
| `Rubric` | name, description, internship_id, is_active |
| `Competency` | rubric_id, name, weight, evaluator_role |
| `Indicator` | competency_id, name, max_score, weight |
| `Assessment` | registration_id, rubric_id, score, type, finalized_at |
| `Presentation` | registration_id, scheduled_at, status, scores |
| `PresentationExaminer` | presentation_id, examiner_id, score |

## Actions (15 total)

| Action | Type |
|---|---|
| `CreateRubricAction` | Command |
| `UpdateRubricAction` | Command |
| `DeleteRubricAction` | Command |
| `CreateCompetencyAction` | Command |
| `UpdateCompetencyAction` | Command |
| `DeleteCompetencyAction` | Command |
| `CreateIndicatorAction` | Command |
| `UpdateIndicatorAction` | Command |
| `DeleteIndicatorAction` | Command |
| `InitializeAssessmentAction` | Command |
| `UpdateAssessmentScoresAction` | Command |
| `FinalizeAssessmentAction` | Command |
| `AutoCalculateAssessmentAction` | Command |
| `SchedulePresentationAction` | Command |
| `ScorePresentationAction` | Command |
| `CompletePresentationAction` | Command |

## Where to Find It

- `app/Domain/Assessment/Models/`
- `app/Domain/Assessment/Actions/` — 16 Actions
