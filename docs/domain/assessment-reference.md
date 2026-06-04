# Assessment — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Assessment** domain.

---

## Overview

Manages assessments, rubrics, and presentation evaluation frameworks

### Domain Statistics
- **Actions**: 17 business logic operations
- **Models**: 6 data entities
- **Livewire Components**: 4 UI components
- **Policies**: 1 authorization rules
- **Aggregates**: 3 domain aggregates

### Aggregates
- `Assessment`
- `Presentation`
- `Rubric`

---

## Dependency Graph

This domain depends on:
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
| `Aggregates/Assessment/Actions/AutoCalculateAssessmentAction.php` | `AutoCalculateAssessmentAction` | `BaseAction` |
| `Aggregates/Presentation/Actions/CompletePresentationAction.php` | `CompletePresentationAction` | `BaseAction` |
| `Aggregates/Rubric/Actions/CreateCompetencyAction.php` | `CreateCompetencyAction` | `BaseAction` |
| `Aggregates/Rubric/Actions/CreateIndicatorAction.php` | `CreateIndicatorAction` | `BaseAction` |
| `Aggregates/Rubric/Actions/CreateRubricAction.php` | `CreateRubricAction` | `BaseAction` |
| `Aggregates/Rubric/Actions/DeleteCompetencyAction.php` | `DeleteCompetencyAction` | `BaseAction` |
| `Aggregates/Rubric/Actions/DeleteIndicatorAction.php` | `DeleteIndicatorAction` | `BaseAction` |
| `Aggregates/Rubric/Actions/DeleteRubricAction.php` | `DeleteRubricAction` | `BaseAction` |
| `Aggregates/Assessment/Actions/FinalizeAssessmentAction.php` | `FinalizeAssessmentAction` | `BaseAction` |
| `Aggregates/Assessment/Actions/InitializeAssessmentAction.php` | `InitializeAssessmentAction` | `BaseAction` |
| `Aggregates/Presentation/Actions/SchedulePresentationAction.php` | `SchedulePresentationAction` | `BaseAction` |
| `Aggregates/Assessment/Actions/ScoreIndicatorAction.php` | `ScoreIndicatorAction` | `BaseAction` |
| `Aggregates/Presentation/Actions/ScorePresentationAction.php` | `ScorePresentationAction` | `BaseAction` |
| `Aggregates/Assessment/Actions/UpdateAssessmentScoresAction.php` | `UpdateAssessmentScoresAction` | `BaseAction` |
| `Aggregates/Rubric/Actions/UpdateCompetencyAction.php` | `UpdateCompetencyAction` | `BaseAction` |
| `Aggregates/Rubric/Actions/UpdateIndicatorAction.php` | `UpdateIndicatorAction` | `BaseAction` |
| `Aggregates/Rubric/Actions/UpdateRubricAction.php` | `UpdateRubricAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Assessment/Models/Assessment.php` | `Assessment` |
| `Aggregates/Rubric/Models/Competency.php` | `Competency` |
| `Aggregates/Rubric/Models/Indicator.php` | `Indicator` |
| `Aggregates/Presentation/Models/Presentation.php` | `Presentation` |
| `Aggregates/Presentation/Models/PresentationExaminer.php` | `PresentationExaminer` |
| `Aggregates/Rubric/Models/Rubric.php` | `Rubric` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Assessment/Livewire/AssessmentGrading.php` | `AssessmentGrading` | `Component` |
| `Aggregates/Assessment/Livewire/AssessmentView.php` | `AssessmentView` | `Component` |
| `Aggregates/Presentation/Livewire/PresentationSchedule.php` | `PresentationSchedule` | `BaseRecordManager` |
| `Aggregates/Rubric/Livewire/RubricManager.php` | `RubricManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Assessment/Policies/AssessmentPolicy.php` | `AssessmentPolicy` |

---

## File Organization

```
app/Domain/Assessment/
├── Aggregates/           ← Aggregate roots
│   └── {Aggregate}/
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
