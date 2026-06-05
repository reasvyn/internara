# Assessment — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Assessment** module.

---

## Overview

Manages assessments, rubrics, and presentation evaluation frameworks

### Module Statistics
- **Actions**: 17 business logic operations
- **Models**: 6 data entities
- **Livewire Components**: 4 UI components
- **Policies**: 1 authorization rules
- **Submodules**: 3 module submodules

### Submodules
- `Assessment`
- `Presentation`
- `Rubric`

---

## Dependency Graph

This module depends on:
- **Assignment**
- **Core**
- **Enrollment**
- **Evaluation**
- **Guidance**
- **Reports**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Assessment/Actions/AutoCalculateAssessmentAction.php` | `AutoCalculateAssessmentAction` | `BaseAction` |
| `Presentation/Actions/CompletePresentationAction.php` | `CompletePresentationAction` | `BaseAction` |
| `Rubric/Actions/CreateCompetencyAction.php` | `CreateCompetencyAction` | `BaseAction` |
| `Rubric/Actions/CreateIndicatorAction.php` | `CreateIndicatorAction` | `BaseAction` |
| `Rubric/Actions/CreateRubricAction.php` | `CreateRubricAction` | `BaseAction` |
| `Rubric/Actions/DeleteCompetencyAction.php` | `DeleteCompetencyAction` | `BaseAction` |
| `Rubric/Actions/DeleteIndicatorAction.php` | `DeleteIndicatorAction` | `BaseAction` |
| `Rubric/Actions/DeleteRubricAction.php` | `DeleteRubricAction` | `BaseAction` |
| `Assessment/Actions/FinalizeAssessmentAction.php` | `FinalizeAssessmentAction` | `BaseAction` |
| `Assessment/Actions/InitializeAssessmentAction.php` | `InitializeAssessmentAction` | `BaseAction` |
| `Presentation/Actions/SchedulePresentationAction.php` | `SchedulePresentationAction` | `BaseAction` |
| `Assessment/Actions/ScoreIndicatorAction.php` | `ScoreIndicatorAction` | `BaseAction` |
| `Presentation/Actions/ScorePresentationAction.php` | `ScorePresentationAction` | `BaseAction` |
| `Assessment/Actions/UpdateAssessmentScoresAction.php` | `UpdateAssessmentScoresAction` | `BaseAction` |
| `Rubric/Actions/UpdateCompetencyAction.php` | `UpdateCompetencyAction` | `BaseAction` |
| `Rubric/Actions/UpdateIndicatorAction.php` | `UpdateIndicatorAction` | `BaseAction` |
| `Rubric/Actions/UpdateRubricAction.php` | `UpdateRubricAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Assessment/Models/Assessment.php` | `Assessment` |
| `Rubric/Models/Competency.php` | `Competency` |
| `Rubric/Models/Indicator.php` | `Indicator` |
| `Presentation/Models/Presentation.php` | `Presentation` |
| `Presentation/Models/PresentationExaminer.php` | `PresentationExaminer` |
| `Rubric/Models/Rubric.php` | `Rubric` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Assessment/Livewire/AssessmentGrading.php` | `AssessmentGrading` | `Component` |
| `Assessment/Livewire/AssessmentView.php` | `AssessmentView` | `Component` |
| `Presentation/Livewire/PresentationSchedule.php` | `PresentationSchedule` | `BaseRecordManager` |
| `Rubric/Livewire/RubricManager.php` | `RubricManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Assessment/Policies/AssessmentPolicy.php` | `AssessmentPolicy` |

---

## File Organization

```
app/Assessment/
├──            ← Submodule roots
│   └── {SubModule}/
│       ├── Actions/
│       ├── Models/
│       ├── Policies/
│       └── Livewire/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

*For overview and business context, see [assessment.md](assessment.md)*
