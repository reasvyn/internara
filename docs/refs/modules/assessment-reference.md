# Assessment — Technical Reference

## Description

Detailed structural and implementation reference for the **Assessment** module.

---

## Overview

Manages competency rubrics, assessment scoring frameworks, and student evaluation scorecards.

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/AutoCalculateAssessmentAction.php` | `AutoCalculateAssessmentAction` | `BaseCommandAction` |
| `Actions/FinalizeAssessmentAction.php` | `FinalizeAssessmentAction` | `BaseCommandAction` |
| `Actions/InitializeAssessmentAction.php` | `InitializeAssessmentAction` | `BaseCommandAction` |
| `Actions/ScoreIndicatorAction.php` | `ScoreIndicatorAction` | `BaseCommandAction` |
| `Actions/UpdateAssessmentScoresAction.php` | `UpdateAssessmentScoresAction` | `BaseCommandAction` |
| `Domain/Rubric/Actions/CreateCompetencyAction.php` | `CreateCompetencyAction` | `BaseCommandAction` |
| `Domain/Rubric/Actions/CreateIndicatorAction.php` | `CreateIndicatorAction` | `BaseCommandAction` |
| `Domain/Rubric/Actions/CreateRubricAction.php` | `CreateRubricAction` | `BaseCommandAction` |
| `Domain/Rubric/Actions/DeleteCompetencyAction.php` | `DeleteCompetencyAction` | `BaseCommandAction` |
| `Domain/Rubric/Actions/DeleteIndicatorAction.php` | `DeleteIndicatorAction` | `BaseCommandAction` |
| `Domain/Rubric/Actions/DeleteRubricAction.php` | `DeleteRubricAction` | `BaseCommandAction` |
| `Domain/Rubric/Actions/UpdateCompetencyAction.php` | `UpdateCompetencyAction` | `BaseCommandAction` |
| `Domain/Rubric/Actions/UpdateIndicatorAction.php` | `UpdateIndicatorAction` | `BaseCommandAction` |
| `Domain/Rubric/Actions/UpdateRubricAction.php` | `UpdateRubricAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/Rubric/Models/Rubric.php` | `Rubric` | `BaseModel` |
| `Models/Assessment.php` | `Assessment` | `BaseModel` |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/Rubric/Policies/RubricPolicy.php` | `RubricPolicy` | `BasePolicy` |
| `Policies/AssessmentPolicy.php` | `AssessmentPolicy` | `BasePolicy` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Enums/EvaluatorRole.php` | `EvaluatorRole` | `LabelEnum` | — |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Data/ScoreIndicatorData.php` | `ScoreIndicatorData` | `BaseData` |
| `Data/UpdateAssessmentScoresData.php` | `UpdateAssessmentScoresData` | `BaseData` |
| `Domain/Rubric/Data/CreateCompetencyData.php` | `CreateCompetencyData` | `BaseData` |
| `Domain/Rubric/Data/CreateIndicatorData.php` | `CreateIndicatorData` | `BaseData` |
| `Domain/Rubric/Data/CreateRubricData.php` | `CreateRubricData` | `BaseData` |
| `Domain/Rubric/Data/DeleteIndicatorData.php` | `DeleteIndicatorData` | `BaseData` |
| `Domain/Rubric/Data/UpdateCompetencyData.php` | `UpdateCompetencyData` | `BaseData` |
| `Domain/Rubric/Data/UpdateIndicatorData.php` | `UpdateIndicatorData` | `BaseData` |
| `Domain/Rubric/Data/UpdateRubricData.php` | `UpdateRubricData` | `BaseData` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Rubric/Livewire/RubricManager.php` | `RubricManager` | `BaseRecordManager` |
| `Livewire/AssessmentGrading.php` | `AssessmentGrading` | `Component` |
| `Livewire/AssessmentView.php` | `AssessmentView` | `Component` |

## Events

| File | Event | Extends |
|---|---|---|
| `Events/AssessmentFinalized.php` | `AssessmentFinalized` | `BaseEvent` |

## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Listeners/LogAssessmentFinalized.php` | `LogAssessmentFinalized` | — |

## Routes

File: `routes/web/assessment.php` Named routes: `assessments`, `sysadmin.assessments.rubrics`,
`sysadmin.assessments.grade`

## Views

Views are located in `resources/views/assessment/`. See [UI/UX](../../guides/ui-ux.md) for the
design system.

## Tests

Tests are located in `tests/Assessment/`. See [Testing](../../guides/infra/testing.md) for the
testing conventions.

## Factories

| Factory             | Model        |
| ------------------- | ------------ |
| `AssessmentFactory` | `Assessment` |
| `RubricFactory`     | `Rubric`     |

## Migrations

| Migration                  | Table         |
| -------------------------- | ------------- |
| `create_assessments_table` | `assessments` |
| `create_rubrics_table`     | `rubrics`     |

---

## Architectural Integration

- **Submodules**: `Rubric`
- **Business Logic**: `app/Modules/Assessment/`
- **Routing**: `routes/web/assessment.php`
- **Views**: `resources/views/assessment/`
- **Testing**: `tests/Assessment/`
- **Dependencies**: Core
- **Used By**: Evaluation

_For overview and business context, see [assessment.md](assessment.md)._
