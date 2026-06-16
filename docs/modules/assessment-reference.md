# Assessment — Technical Reference

> Last updated: 2026-06-16

Detailed structural and implementation reference for the **Assessment** module.

---

## Overview

Manages competency rubrics, assessment scoring frameworks, and student evaluation scorecards.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Actions/InitializeAssessmentAction.php` | `InitializeAssessmentAction` | `BaseAction` |
| `Actions/ScoreIndicatorAction.php` | `ScoreIndicatorAction` | `BaseAction` |
| `Actions/UpdateAssessmentScoresAction.php` | `UpdateAssessmentScoresAction` | `BaseAction` |
| `Actions/FinalizeAssessmentAction.php` | `FinalizeAssessmentAction` | `BaseAction` |
| `Actions/AutoCalculateAssessmentAction.php` | `AutoCalculateAssessmentAction` | `BaseAction` |
| `Rubric/Actions/CreateRubricAction.php` | `CreateRubricAction` | `BaseAction` |
| `Rubric/Actions/UpdateRubricAction.php` | `UpdateRubricAction` | `BaseAction` |
| `Rubric/Actions/DeleteRubricAction.php` | `DeleteRubricAction` | `BaseAction` |
| `Rubric/Actions/CreateCompetencyAction.php` | `CreateCompetencyAction` | `BaseAction` |
| `Rubric/Actions/UpdateCompetencyAction.php` | `UpdateCompetencyAction` | `BaseAction` |
| `Rubric/Actions/DeleteCompetencyAction.php` | `DeleteCompetencyAction` | `BaseAction` |
| `Rubric/Actions/CreateIndicatorAction.php` | `CreateIndicatorAction` | `BaseAction` |
| `Rubric/Actions/UpdateIndicatorAction.php` | `UpdateIndicatorAction` | `BaseAction` |
| `Rubric/Actions/DeleteIndicatorAction.php` | `DeleteIndicatorAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Models/Assessment.php` | `Assessment` | `BaseModel` |
| `Rubric/Models/Rubric.php` | `Rubric` | `BaseModel` |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Entities/AssessmentResult.php` | `AssessmentResult` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Policies/AssessmentPolicy.php` | `AssessmentPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Livewire/AssessmentGrading.php` | `AssessmentGrading` | `Component` |
| `Livewire/AssessmentView.php` | `AssessmentView` | `Component` |
| `Rubric/Livewire/RubricManager.php` | `RubricManager` | `Component` |

---

## Routes

File: `routes/web/assessment.php`
Naming pattern: `assessment.{resource}.{action}`

## Views

Views are located in `resources/views/assessment/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Assessment/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `AssessmentFactory` | `Assessment` |
| `RubricFactory` | `Rubric` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_assessments_table` | `assessments` |
| `create_rubrics_table` | `rubrics` |

---


---

## Architectural Integration

- **Submodules**: `Rubric`
- **Business Logic**: `app/Assessment/`
- **Routing**: `routes/web/assessment.php`
- **Views**: `resources/views/assessment/`
- **Testing**: `tests/Feature/Assessment/`, `tests/Unit/Assessment/`
- **Dependencies**: Core
- **Used By**: Evaluation

*For overview and business context, see [assessment.md](assessment.md).*
