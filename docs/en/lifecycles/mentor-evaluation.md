# Mentor Evaluation

**Event:** Evaluating mentor performance during the internship.

**Phase:** 5 — Assessment & Evaluation

**Previous Event:** [Assessment & Scoring](assessment-scoring.md)

**Next Event:** [Period Closing](period-closing.md)

---

## Overview

Mentor evaluation allows students and administrators to provide feedback on mentor performance. Evaluation criteria cover communication, responsiveness, and guidance quality. This is separate from student assessment — it evaluates the mentor's effectiveness.

## Trigger

- End of internship period
- Periodic evaluation cycle
- Student feedback request

## Pre-conditions

- Mentor is attached to at least one registration
- For student evaluators: student has an active or completed registration under the mentor
- User is logged in with appropriate role

## Actors

| Actor | Role | Can evaluate mentors |
|---|---|---|
| Student | STUDENT | Yes (own mentors) |
| Teacher | TEACHER | Yes (other teachers/supervisors) |
| Admin | ADMIN, SUPER_ADMIN | Yes (all) |
| Supervisor | SUPERVISOR | Yes (other mentors) |

---

## Evaluation Criteria

Each evaluation scores the mentor on these criteria (0-100 each):

| Criterion | Description |
|---|---|
| **Communication** | Responsiveness to messages, clarity of instructions |
| **Responsiveness** | Speed of feedback and support |
| **Guidance Quality** | Usefulness and depth of mentoring |

## Overall Score

An optional overall score (0-100) summarizes the mentor's performance.

## Event Flow

### Creating an Evaluation

```
Evaluator → Evaluation → Select Mentor → Score Criteria → Submit
```

Navigate to the evaluation section. For students: portal may have a "Evaluate Mentor" option.

| Field | Validation | Description |
|---|---|---|
| **Mentor** | Required, exists | Select the mentor being evaluated |
| **Communication** | Numeric, 0-100 | Communication score |
| **Responsiveness** | Numeric, 0-100 | Responsiveness score |
| **Guidance Quality** | Numeric, 0-100 | Quality of guidance |
| **Overall Score** | Optional, numeric, 0-100 | Summary score |
| **Feedback** | Optional, max 2000 | Written feedback |

The `EvaluateMentorAction` saves the evaluation with:
- Evaluator ID (who submitted)
- Mentor ID (who is being evaluated)
- Criteria scores
- Optional overall score and feedback

### Editing an Evaluation

Evaluators can edit their own evaluations. The `MentorEvaluationManager` component supports edit:

1. Opens existing evaluation
2. Changes scores or feedback
3. Updates via `EvaluateMentorAction`
4. System records the update

### Deleting an Evaluation

Evaluators or admins can delete evaluations. `DeleteEvaluationAction` removes the record.

## Access Control

| User | Can evaluate | Can edit own | Can delete |
|---|---|---|---|
| Student (under mentor) | Yes | Yes | No |
| Any teacher | Yes | Yes | No |
| Admin / Super Admin | Yes | Yes | Yes |

## Key Rules

| Rule | Enforcement |
|---|---|
| **Multiple evaluations allowed** | A mentor can be evaluated by multiple students |
| **Score bounds** | 0-100 for all numeric scores |
| **Feedback optional** | No validation on feedback field |
| **Own evaluation editable** | Only the creator can edit their evaluation |

## Seamless Connection

Mentor evaluations feed into:

- **[Period Closing](period-closing.md)** — evaluations should be collected before the period ends
- **[Report Generation](report-generation.md)** — evaluation summaries appear in mentor performance reports
