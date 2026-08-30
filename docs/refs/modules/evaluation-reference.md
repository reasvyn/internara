# Evaluation — Technical Reference

## Description

Detailed structural and implementation reference for the **Evaluation** module.

---

## Overview

Generic feedback collection system with a Google Forms-like architecture. Replaces the legacy
`evaluations` table with a flexible form → section → question → response → answer schema.

### Submodules

None — all components are directly under `app/Modules/Evaluation/`.

---

## Models

| File | Class | Extends |
|---|---|---|
| `Models/EvaluationAnswer.php` | `EvaluationAnswer` | `BaseModel` |
| `Models/EvaluationQuestion.php` | `EvaluationQuestion` | `BaseModel` |
| `Models/EvaluationResponse.php` | `EvaluationResponse` | `BaseModel` |
| `Models/EvaluationSection.php` | `EvaluationSection` | `BaseModel` |

## Routes

No dedicated routes — the evaluation module is consumed by other modules via model imports. Route
definitions will be added when the form builder UI and response UI are implemented.

---

## Views

No dedicated views. Views will be added with the form builder and response components.

---

## Tests

No dedicated tests. Tests will be added with feature implementation.

---

## Factories

| Factory                     | Model                |
| --------------------------- | -------------------- |
| `EvaluationFormFactory`     | `EvaluationForm`     |
| `EvaluationSectionFactory`  | `EvaluationSection`  |
| `EvaluationQuestionFactory` | `EvaluationQuestion` |
| `EvaluationResponseFactory` | `EvaluationResponse` |
| `EvaluationAnswerFactory`   | `EvaluationAnswer`   |

---

## Migrations

| Migration                                             | Table                  |
| ----------------------------------------------------- | ---------------------- |
| `2026_06_12_110000_create_evaluation_forms_table`     | `evaluation_forms`     |
| `2026_06_12_110001_create_evaluation_questions_table` | `evaluation_questions` |
| `2026_06_12_110002_create_evaluation_responses_table` | `evaluation_responses` |
| `2026_06_12_110003_create_evaluation_answers_table`   | `evaluation_answers`   |
| `2026_06_12_110004_create_evaluation_sections_table`  | `evaluation_sections`  |

---

No Actions, Entities, Policies, or Livewire components yet — these will be added with the form
builder and response collection features.

---

## Architectural Integration

- **Submodules**: None
- **Business Logic**: `app/Modules/Evaluation/`
- **Routing**: Not yet implemented
- **Views**: Not yet implemented
- **Testing**: Not yet implemented
- **Dependencies**: Core, User, Enrollment
- **Used By**: Reports, Certification

_For overview and business context, see [evaluation.md](evaluation.md)._
