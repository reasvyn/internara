# Evaluation — Technical Reference

> **Last updated:** 2026-06-16
> **Changes:** sync — initial metadata sync with new format

## Description
Detailed structural and implementation reference for the **Evaluation** module.

---


## Overview

Generic feedback collection system with a Google Forms-like architecture. Replaces the legacy
`evaluations` table with a flexible form → section → question → response → answer schema.

### Submodules

None — all components are directly under `app/Evaluation/`.

---

## Models

| File | Class | Extends | Table |
| ---- | ----- | ------- | ----- |
| `Models/EvaluationForm.php` | `EvaluationForm` | `BaseModel` | `evaluation_forms` |
| `Models/EvaluationSection.php` | `EvaluationSection` | `BaseModel` | `evaluation_sections` |
| `Models/EvaluationQuestion.php` | `EvaluationQuestion` | `BaseModel` | `evaluation_questions` |
| `Models/EvaluationResponse.php` | `EvaluationResponse` | `BaseModel` | `evaluation_responses` |
| `Models/EvaluationAnswer.php` | `EvaluationAnswer` | `BaseModel` | `evaluation_answers` |

### EvaluationForm

| Column | Type | Description |
|--------|------|-------------|
| `name` | string | Form display name |
| `description` | text (nullable) | Optional form description |
| `target_type` | string | `mentor`, `program`, `company`, `overall` |
| `is_active` | boolean | Whether form is accepting responses |

### EvaluationSection

| Column | Type | Description |
|--------|------|-------------|
| `form_id` | FK → evaluation_forms | Parent form |
| `title` | string | Section heading |
| `description` | text (nullable) | Optional section description |
| `order` | unsigned integer | Display ordering |

### EvaluationQuestion

| Column | Type | Description |
|--------|------|-------------|
| `form_id` | FK → evaluation_forms | Parent form |
| `section_id` | uuid (nullable) | Optional parent section |
| `question_text` | text | The question prompt |
| `question_type` | string | `rating_1_5`, `rating_1_10`, `yes_no`, `multiple_choice`, `text`, `agreement` |
| `options` | JSON (nullable) | Choices for `multiple_choice` |
| `weight` | unsigned integer | Scoring weight (default: 1) |
| `order` | unsigned integer | Display ordering |
| `is_required` | boolean | Whether answer is mandatory |

### EvaluationResponse

| Column | Type | Description |
|--------|------|-------------|
| `form_id` | FK → evaluation_forms | The form submitted |
| `evaluator_id` | FK → users | Who submitted |
| `target_type` | string | Polymorphic target type |
| `target_id` | uuid | Polymorphic target ID |
| `registration_id` | FK (nullable) | Registration context |
| `overall_score` | float (nullable) | Auto-calculated weighted score |
| `notes` | text (nullable) | Free-text notes |
| `submitted_at` | timestamp | When submitted |

### EvaluationAnswer

| Column | Type | Description |
|--------|------|-------------|
| `response_id` | FK → evaluation_responses | Parent response |
| `question_id` | FK → evaluation_questions | Which question |
| `value` | text (nullable) | Raw answer |
| `score` | float (nullable) | Numeric score derived from value |

---

## Enums

None. The `EvaluatorRole` enum previously located here has been moved to `app/Assessment/Enums/`
(see [Assessment reference](../modules/assessment-reference.md)).

---

## Routes

No dedicated routes — the evaluation module is consumed by other modules via model imports.
Route definitions will be added when the form builder UI and response UI are implemented.

---

## Views

No dedicated views. Views will be added with the form builder and response components.

---

## Tests

No dedicated tests. Tests will be added with feature implementation.

---

## Factories

| Factory | Model |
| ------- | ----- |
| `EvaluationFormFactory` | `EvaluationForm` |
| `EvaluationSectionFactory` | `EvaluationSection` |
| `EvaluationQuestionFactory` | `EvaluationQuestion` |
| `EvaluationResponseFactory` | `EvaluationResponse` |
| `EvaluationAnswerFactory` | `EvaluationAnswer` |

---

## Migrations

| Migration | Table |
| --------- | ----- |
| `2026_06_12_110000_create_evaluation_forms_table` | `evaluation_forms` |
| `2026_06_12_110001_create_evaluation_questions_table` | `evaluation_questions` |
| `2026_06_12_110002_create_evaluation_responses_table` | `evaluation_responses` |
| `2026_06_12_110003_create_evaluation_answers_table` | `evaluation_answers` |
| `2026_06_12_110004_create_evaluation_sections_table` | `evaluation_sections` |

---

No Actions, Entities, Policies, or Livewire components yet — these will be added with
the form builder and response collection features.

---

## Architectural Integration

- **Submodules**: None
- **Business Logic**: `app/Evaluation/`
- **Routing**: Not yet implemented
- **Views**: Not yet implemented
- **Testing**: Not yet implemented
- **Dependencies**: Core, User, Enrollment
- **Used By**: Reports, Certification

*For overview and business context, see [evaluation.md](evaluation.md).*
