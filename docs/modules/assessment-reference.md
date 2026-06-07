# Assessment — Technical Reference

> Last updated: 2026-06-06  
> Changes: Removed competencies, indicators, and presentations tables and references.

Detailed structural and implementation reference for the **Assessment** module.

---

## Overview

Manages assessments and JSON-based rubric evaluation templates.

### Module Statistics

- **Actions**: 9 business logic operations
- **Models**: 2 data entities (`Assessment`, `Rubric`)
- **Livewire Components**: 3 UI components
- **Policies**: 1 authorization rules
- **Submodules**: 2 module submodules

### Submodules

- **Rubric**: Evaluation templates with competency criteria schemas stored as JSON.
- **Assessment**: Evaluator grading records scoring students against rubrics.

---

## Dependency Graph

This module depends on:

- **Core** (base classes)
- **Enrollment** (registration records)
- **User** (evaluators and students)

---

## Actions

| File                                        | Class                           | Extends      |
| ------------------------------------------- | ------------------------------- | ------------ |
| `Actions/InitializeAssessmentAction.php`    | `InitializeAssessmentAction`    | `BaseAction` |
| `Actions/AutoCalculateAssessmentAction.php` | `AutoCalculateAssessmentAction` | `BaseAction` |
| `Actions/UpdateAssessmentScoresAction.php`  | `UpdateAssessmentScoresAction`  | `BaseAction` |
| `Actions/FinalizeAssessmentAction.php`      | `FinalizeAssessmentAction`      | `BaseAction` |
| `Rubric/Actions/CreateRubricAction.php`     | `CreateRubricAction`            | `BaseAction` |
| `Rubric/Actions/UpdateRubricAction.php`     | `UpdateRubricAction`            | `BaseAction` |
| `Rubric/Actions/DeleteRubricAction.php`     | `DeleteRubricAction`            | `BaseAction` |

---

## Models

| File                       | Class        |
| -------------------------- | ------------ |
| `Models/Assessment.php`    | `Assessment` |
| `Rubric/Models/Rubric.php` | `Rubric`     |

---

## Livewire Components

| File                                | Component           | Extends     |
| ----------------------------------- | ------------------- | ----------- |
| `Livewire/AssessmentGrading.php`    | `AssessmentGrading` | `Component` |
| `Livewire/AssessmentView.php`       | `AssessmentView`    | `Component` |
| `Rubric/Livewire/RubricManager.php` | `RubricManager`     | `Component` |

---

## Authorization Policies

| File                            | Policy             |
| ------------------------------- | ------------------ | ------------ |
| `Policies/AssessmentPolicy.php` | `AssessmentPolicy` | `BasePolicy` |

---

## File Organization

```
app/Assessment/
├── Actions/              ← Cross-submodule / flat actions
├── Entities/
├── Livewire/
├── Models/
├── Policies/
├── Rubric/               ← Rubric submodule
│   ├── Actions/
│   ├── Livewire/
│   └── Models/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Assessment`, `Rubric`
- **Business Logic (`app/`)**: Located in
  [app/Assessment/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Assessment/)
- **Routing (`routes/`)**:
  [routes/web/assessment.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/assessment.php)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/assessment/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/assessment/)
- **Testing (`tests/`)**: Feature `tests/Feature/Assessment/`, Unit `tests/Unit/Assessment/`

_For overview and business context, see [assessment.md](assessment.md)_
