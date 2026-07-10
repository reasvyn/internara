# Evaluation — Feedback Forms, Surveys & Auto-Scoring

> **Last updated:** 2026-07-10 **Changes:** expand — add Actions reference, routes, scoring examples, file structure, and integration patterns

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

None — all components are directly under `app/Evaluation/`.

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

### Actions

| Action                                    | Type      | Description                                          |
| ----------------------------------------- | --------- | ---------------------------------------------------- |
| `CreateEvaluationFormAction`              | Command   | Create a new evaluation form with sections/questions |
| `UpdateEvaluationFormAction`              | Command   | Update form structure (sections, questions, weights) |
| `SubmitEvaluationResponseAction`          | Command   | Submit a completed evaluation response               |
| `ReadEvaluationFormAction`                | Read      | Query forms with filters and structure               |
| `ReadEvaluationResultsAction`             | Read      | Aggregated results with score bands and trends       |

### Routes

| Method | URI                                                   | Action                        |
| ------ | ----------------------------------------------------- | ----------------------------- |
| GET    | `/evaluation/forms`                                   | Form index                    |
| POST   | `/evaluation/forms`                                   | Create form                   |
| GET    | `/evaluation/forms/{evaluationForm}`                  | Show form with structure      |
| PUT    | `/evaluation/forms/{evaluationForm}`                  | Update form                   |
| POST   | `/evaluation/forms/{evaluationForm}/submit`           | Submit response               |
| GET    | `/evaluation/forms/{evaluationForm}/results`          | View aggregated results       |

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

## File Structure

```
app/Evaluation/
├── Actions/
│   ├── CreateEvaluationFormAction.php
│   ├── ReadEvaluationFormAction.php
│   ├── ReadEvaluationResultsAction.php
│   ├── SubmitEvaluationResponseAction.php
│   └── UpdateEvaluationFormAction.php
├── Enums/
│   ├── EvaluationTargetType.php
│   ├── QuestionType.php
│   └── ScoreBand.php
├── Events/
│   └── EvaluationSubmitted.php
├── Livewire/
│   ├── EvaluationFormBuilder.php
│   ├── EvaluationFormView.php
│   └── EvaluationResultsView.php
├── Models/
│   ├── EvaluationForm.php
│   ├── EvaluationSection.php
│   ├── EvaluationQuestion.php
│   ├── EvaluationResponse.php
│   └── EvaluationAnswer.php
└── Policies/
    └── EvaluationFormPolicy.php
