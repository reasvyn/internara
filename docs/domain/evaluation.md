# Evaluation Domain

## Purpose

Evaluation collects structured feedback about the placement experience from multiple
perspectives — students rate mentors, companies, and overall satisfaction.

---

## Models

| Model | Key Fields |
|---|---|
| `Evaluation` | evaluator_id, evaluation_type, mentor_id, registration_id, overall_score, feedback |

## Actions

| Action | Type |
|---|---|
| `SubmitEvaluationAction` | Command |
| `EvaluateMentorAction` | Command |
| `DeleteEvaluationAction` | Command |

## Where to Find It

- `app/Domain/Evaluation/Models/Evaluation.php`
- `app/Domain/Evaluation/Actions/`
