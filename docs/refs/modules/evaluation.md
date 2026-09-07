# Evaluation — Feedback Forms, Surveys & Auto-Scoring

## Description

Generic feedback collection system with a Google Forms-like architecture: admins build reusable
evaluation forms with weighted questions, sections, and answer scoring. Evaluations target any PKL
aspect (mentor, program, company, overall satisfaction) via a polymorphic type system.

## Purpose & Boundary

Evaluation provides a unified feedback pipeline across all PKL stakeholders. Unlike Assessment
(rubric-based competency grading) and Assignment (task-level grading), Evaluation collects
subjective feedback via configurable forms — mentor quality, program effectiveness, company
satisfaction, and overall experience. Forms are fully customizable by admins without code changes.

Out of scope: rubric-based competency scoring (Assessment), task-level feedback (Assignment), daily
logbook reflections (Journals).

## Submodules

None — all components live directly under the Evaluation module namespace.

## Key Concepts

### Evaluation Forms

Forms are the core entity (`evaluation_forms`). Each form targets a specific aspect (`target_type`:
mentor, program, company, overall). Admins create forms via a form builder UI:

```
EvaluationForm
├── EvaluationSections (optional groupings)
│   └── EvaluationQuestions (weighted, typed)
├── EvaluationQuestions (un-sectioned)
└── EvaluationResponses (submitted instances)
    └── EvaluationAnswers (per-question values + scores)

```

### Question Types

| Type              | Storage                | Scoring                                        |
| ----------------- | ---------------------- | ---------------------------------------------- |
| `rating_1_5`      | Integer 1-5            | Normalized to percentage: `(value / 5) × 100`  |
| `rating_1_10`     | Integer 1-10           | Normalized to percentage: `(value / 10) × 100` |
| `yes_no`          | Boolean                | 100 or 0                                       |
| `multiple_choice` | Selected option string | Configurable per-option score                  |
| `agreement`       | Likert 1-5             | Same as `rating_1_5`                           |
| `text`            | Free text              | No score (qualitative only)                    |

### Score Calculation

Overall score is auto-calculated from weighted question scores:

```
overall_score = Σ(question_score × question_weight) / Σ(question_weight)

```

**Score Band Mapping:**

| Band                  | Range  | Label                     |
| --------------------- | ------ | ------------------------- |
| EXCELLENT             | 85-100 | Excellent                 |
| GOOD                  | 70-84  | Good                      |
| SATISFACTORY          | 55-69  | Satisfactory              |
| NEEDS_IMPROVEMENT     | 40-54  | Needs Improvement         |
| POOR                  | 0-39   | Poor                      |

### Immutable Submissions

Once submitted, an evaluation response cannot be modified. The audit trail preserves the original submission with timestamp, evaluator, and all answers. This immutability is enforced at the database level and the Action layer.

### Integration Patterns

- **Polymorphic Targeting**: Forms target any entity via `target_type`/`target_id` (mentor, program, company, overall)
- **Reports Integration**: Aggregated scores per program feed into program quality metrics in the Reports module
- **Certification Gate**: Minimum evaluation scores can be required before certificate issuance
- **Cache Strategy**: Form structure is cached with key `evaluation.form.{id}`; invalidated on form update

## Dependencies

- Core (base classes)
- User (evaluator identity)
- Enrollment (registration context)

## Used By

- Reports (program quality data)
- Certification (eligibility checks)

## Design Principles

- **Forms are polymorphic targets, not fixed entities** — the same form structure targets different subjects (`mentor`, `program`, `company`, `overall`) via `target_type`/`target_id`. This avoids building a separate evaluation workflow per target; the form builder is universal.
- **Score auto-calculation is formulaic and transparent** — overall score is `Σ(score × weight) / Σ(weight)`, normalized to percentage. Score band mapping (EXCELLENT → POOR) is deterministic. No hidden rounding, no undocumented weights — the calculation is auditable by anyone with the form definition.
- **Submissions are immutable after submit** — once an evaluation response is submitted, answers cannot be modified. Enforce this at the database level and the Action layer. Corrections require a new submission with an audit trail linking it to the original.
- **Form structure is cached, not the submission** — form definitions are cached by key `evaluation.form.{id}` and invalidated on update. Submissions are live data — never cache them.

## How It Works

*Content to be added — verify against actual implementation.*

