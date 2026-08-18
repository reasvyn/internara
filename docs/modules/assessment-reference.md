# Assessment — Technical Reference

> **Last updated:** 2026-08-18 **Changes:** add Data/DTOs section (ScoreIndicator,
> UpdateAssessmentScores, Rubric Data classes)

## Description

Detailed structural and implementation reference for the **Assessment** module.

---

## Overview

Manages competency rubrics, assessment scoring frameworks, and student evaluation scorecards.

## Actions

| File                                        | Class                           | Extends             |
| ------------------------------------------- | ------------------------------- | ------------------- |
| `Actions/InitializeAssessmentAction.php`    | `InitializeAssessmentAction`    | `BaseCommandAction` |
| `Actions/ScoreIndicatorAction.php`          | `ScoreIndicatorAction`          | `BaseCommandAction` |
| `Actions/UpdateAssessmentScoresAction.php`  | `UpdateAssessmentScoresAction`  | `BaseCommandAction` |
| `Actions/FinalizeAssessmentAction.php`      | `FinalizeAssessmentAction`      | `BaseCommandAction` |
| `Actions/AutoCalculateAssessmentAction.php` | `AutoCalculateAssessmentAction` | `BaseCommandAction` |
| `Rubric/Actions/CreateRubricAction.php`     | `CreateRubricAction`            | `BaseCommandAction` |
| `Rubric/Actions/UpdateRubricAction.php`     | `UpdateRubricAction`            | `BaseCommandAction` |
| `Rubric/Actions/DeleteRubricAction.php`     | `DeleteRubricAction`            | `BaseCommandAction` |
| `Rubric/Actions/CreateCompetencyAction.php` | `CreateCompetencyAction`        | `BaseCommandAction` |
| `Rubric/Actions/UpdateCompetencyAction.php` | `UpdateCompetencyAction`        | `BaseCommandAction` |
| `Rubric/Actions/DeleteCompetencyAction.php` | `DeleteCompetencyAction`        | `BaseCommandAction` |
| `Rubric/Actions/CreateIndicatorAction.php`  | `CreateIndicatorAction`         | `BaseCommandAction` |
| `Rubric/Actions/UpdateIndicatorAction.php`  | `UpdateIndicatorAction`         | `BaseCommandAction` |
| `Rubric/Actions/DeleteIndicatorAction.php`  | `DeleteIndicatorAction`         | `BaseCommandAction` |

---

## Models

| File                       | Class        | Extends     |
| -------------------------- | ------------ | ----------- |
| `Models/Assessment.php`    | `Assessment` | `BaseModel` |
| `Rubric/Models/Rubric.php` | `Rubric`     | `BaseModel` |

---

## Entities

| File                            | Class              | Extends      |
| ------------------------------- | ------------------ | ------------ |
| `Entities/AssessmentResult.php` | `AssessmentResult` | `BaseEntity` |

---

## Policies

| File                            | Policy             | Extends      |
| ------------------------------- | ------------------ | ------------ |
| `Policies/AssessmentPolicy.php` | `AssessmentPolicy` | `BasePolicy` |

## Enums

| File                      | Enum            | Implements  | Values                             |
| ------------------------- | --------------- | ----------- | ---------------------------------- |
| `Enums/EvaluatorRole.php` | `EvaluatorRole` | `LabelEnum` | admin, teacher, supervisor, system |

---

## Data / DTOs

| File                                   | Class                        | Extends    |
| -------------------------------------- | ---------------------------- | ---------- |
| `Data/ScoreIndicatorData.php`          | `ScoreIndicatorData`         | `BaseData` |
| `Data/UpdateAssessmentScoresData.php`  | `UpdateAssessmentScoresData` | `BaseData` |
| `Rubric/Data/CreateRubricData.php`     | `CreateRubricData`           | `BaseData` |
| `Rubric/Data/UpdateRubricData.php`     | `UpdateRubricData`           | `BaseData` |
| `Rubric/Data/CreateCompetencyData.php` | `CreateCompetencyData`       | `BaseData` |
| `Rubric/Data/UpdateCompetencyData.php` | `UpdateCompetencyData`       | `BaseData` |
| `Rubric/Data/CreateIndicatorData.php`  | `CreateIndicatorData`        | `BaseData` |
| `Rubric/Data/UpdateIndicatorData.php`  | `UpdateIndicatorData`        | `BaseData` |
| `Rubric/Data/DeleteIndicatorData.php`  | `DeleteIndicatorData`        | `BaseData` |

---

## Livewire Components

| File                                | Component           | Extends        |
| ----------------------------------- | ------------------- | -------------- |
| `Livewire/AssessmentGrading.php`    | `AssessmentGrading` | `BaseFormView` |
| `Livewire/AssessmentView.php`       | `AssessmentView`    | `Component`    |
| `Rubric/Livewire/RubricManager.php` | `RubricManager`     | `Component`    |

## Events

| File                             | Event                 | Dispatched By              |
| -------------------------------- | --------------------- | -------------------------- |
| `Events/AssessmentFinalized.php` | `AssessmentFinalized` | `FinalizeAssessmentAction` |

## Listeners

| File                                   | Listener                 | Listens To            |
| -------------------------------------- | ------------------------ | --------------------- |
| `Listeners/LogAssessmentFinalized.php` | `LogAssessmentFinalized` | `AssessmentFinalized` |

---

## Routes

File: `routes/web/assessment.php` Named routes: `assessments`, `sysadmin.assessments.rubrics`,
`sysadmin.assessments.grade`

## Views

Views are located in `resources/views/assessment/`. See [UI/UX](../foundation/ui-ux.md) for the
design system.

## Tests

Tests are located in `tests/Assessment/`. See [Testing](../infrastructure/testing.md) for the
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
- **Business Logic**: `app/Assessment/`
- **Routing**: `routes/web/assessment.php`
- **Views**: `resources/views/assessment/`
- **Testing**: `tests/Assessment/`
- **Dependencies**: Core
- **Used By**: Evaluation

_For overview and business context, see [assessment.md](assessment.md)._
