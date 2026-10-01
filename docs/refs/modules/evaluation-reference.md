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

## Actions

None. The module ships no Action classes — form authoring and response submission
(AXKZW-FR-EVAL-006, -008, -014) have no Layer 3 implementation yet. Consumers read the
models directly.

## Policies & Permissions

None. NFR-EVAL-001 requires administrative authorship gates; with no Actions or Livewire
components there is nothing to gate, so authorization is currently unenforced at the
application layer.

## Events

None. No domain events are dispatched on form or response changes.

---

## Models

| File | Class | Extends |
|---|---|---|
| `Models/EvaluationAnswer.php` | `EvaluationAnswer` | `BaseModel` |
| `Models/EvaluationForm.php` | `EvaluationForm` | `BaseModel` |
| `Models/EvaluationQuestion.php` | `EvaluationQuestion` | `BaseModel` |
| `Models/EvaluationResponse.php` | `EvaluationResponse` | `BaseModel` |
| `Models/EvaluationSection.php` | `EvaluationSection` | `BaseModel` |

## Routes

No `routes/web/evaluation.php` file exists. The evaluation module is consumed by other modules
via model imports. Route definitions will be added when the form builder UI and response UI are
implemented.

---

## Views

No `resources/views/evaluation/` directory exists. Views will be added with the form builder and
response components.

---

## Tests

| Test                         | Layer   | Requirements covered                                            |
| ---------------------------- | ------- | --------------------------------------------------------------- |
| `EvaluationFormTest`         | Feature | FR-EVAL-001, -002, -018 — form CRUD, five target types, lookup   |
| `EvaluationStructureTest`    | Feature | FR-EVAL-003, -004, -005 — sections, cascade, six question types  |
| `EvaluationResponseTest`     | Feature | FR-EVAL-010, -011, -012, -013 — response targets, freeze         |
| `EvaluationTraceabilityTest` | Feature | FR-EVAL-008 and further schema-contract requirements             |
| `EvaluationLocalizationTest` | Feature | FR-EVAL-021 — EN/ID string and criteria-label parity              |
| `EvaluationHygieneTest`              | Arch    | NFR-EVAL-008 — `strict_types` on every module file               |

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
| `2026_01_06_000001_create_evaluation_forms_table.php`     | `evaluation_forms`     |
| `2026_01_06_000002_create_evaluation_sections_table.php`  | `evaluation_sections`  |
| `2026_01_06_000003_create_evaluation_questions_table.php` | `evaluation_questions` |
| `2026_01_06_000004_create_evaluation_responses_table.php` | `evaluation_responses` |
| `2026_01_06_000005_create_evaluation_answers_table.php`   | `evaluation_answers`   |

---

*Entities, DTOs, Actions, and Livewire components remain to be added with the form builder and response collection features.*

---

## Architectural Integration

- **Submodules**: None
- **Business Logic**: `app/Modules/Evaluation/` — models only; no Actions yet
- **Routing**: `routes/web/` — no `evaluation.php` route file
- **Views**: `resources/views/` — no `evaluation/` directory
- **Testing**: `tests/{Arch,Feature}/Evaluation/`
- **Dependencies**: Core, User, Enrollment
- **Used By**: Reports, Certification
- **Spec status**: `Partial` — see [AXKZW](../../specs/AXKZW-evaluation.md)

_For overview and business context, see [evaluation.md](evaluation.md)._
