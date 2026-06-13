# Evaluation

> **Last updated:** 2026-06-12

Generic feedback collection system with a Google Forms-like architecture: admins build reusable
evaluation forms with weighted questions, sections, and answer scoring. Evaluations target
any PKL aspect (mentor, program, company, overall satisfaction) via a polymorphic type system.

## Purpose & Boundary

Evaluation provides a unified feedback pipeline across all PKL stakeholders. Unlike Assessment
(rubric-based competency grading) and Assignment (task-level grading), Evaluation collects
subjective feedback via configurable forms — mentor quality, program effectiveness, company
satisfaction, and overall experience. Forms are fully customizable by admins without code changes.

Out of scope: rubric-based competency scoring (Assessment), task-level feedback (Assignment),
daily logbook reflections (Journals).

## Submodules

None — all components are directly under `app/Evaluation/`.

## Key Concepts

### Evaluation Forms

Forms are the core entity (`evaluation_forms`). Each form targets a specific aspect
(`target_type`: mentor, program, company, overall). Admins create forms via a form builder UI:

```
EvaluationForm
├── EvaluationSections (optional groupings)
│   └── EvaluationQuestions (weighted, typed)
├── EvaluationQuestions (un-sectioned)
└── EvaluationResponses (submitted instances)
    └── EvaluationAnswers (per-question values + scores)
```

### Question Types

| Type | Storage | Scoring |
|------|---------|---------|
| `rating_1_5` | Integer 1-5 | Normalized to percentage: `(value / 5) × 100` |
| `rating_1_10` | Integer 1-10 | Normalized to percentage: `(value / 10) × 100` |
| `yes_no` | Boolean | 100 or 0 |
| `multiple_choice` | Selected option string | Configurable per-option score |
| `agreement` | Likert 1-5 | Same as `rating_1_5` |
| `text` | Free text | No score (qualitative only) |

### Score Calculation

Overall score is auto-calculated from weighted question scores:

```
overall_score = Σ(question_score × question_weight) / Σ(question_weight)
```

### Immutable Submissions

Once submitted, an evaluation response cannot be modified. The audit trail preserves the
original submission with timestamp, evaluator, and all answers.

## Dependencies

- Core (base classes)
- User (evaluator identity)
- Enrollment (registration context)

## Used By

- Reports (program quality data)
- Certification (eligibility checks)
