# Assessment — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Aggregate-rooted layout mapping for the Assessment domain

This reference defines the structured aggregates and code layout within the **Assessment** domain.

---

## 1. Assessment Aggregate
Handles student performance scoring initialization, final score compiling, and locking grades.

- **Eloquent Models**:
  - `Assessment` (`app/Domain/Assessment/Models/Assessment.php`)
- **Policies**:
  - `AssessmentPolicy` (`app/Domain/Assessment/Policies/AssessmentPolicy.php`)
- **Command Actions**:
  - `InitializeAssessmentAction` (`app/Domain/Assessment/Actions/InitializeAssessmentAction.php`)
  - `UpdateAssessmentScoresAction` (`app/Domain/Assessment/Actions/UpdateAssessmentScoresAction.php`)
  - `ScoreIndicatorAction` (`app/Domain/Assessment/Actions/ScoreIndicatorAction.php`)
  - `FinalizeAssessmentAction` (`app/Domain/Assessment/Actions/FinalizeAssessmentAction.php`)
  - `AutoCalculateAssessmentAction` (`app/Domain/Assessment/Actions/AutoCalculateAssessmentAction.php`)
- **Livewire UI Components**:
  - `AssessmentGrading` (`app/Domain/Assessment/Livewire/AssessmentGrading.php`)
  - `AssessmentView` (`app/Domain/Assessment/Livewire/AssessmentView.php`)
- **Entities (Domain Rules)**:
  - `AssessmentResult` (`app/Domain/Assessment/Entities/AssessmentResult.php`)
- **Enums**:
  - `EvaluatorRole` (`app/Domain/Assessment/Enums/EvaluatorRole.php`)

---

## 2. Presentation Aggregate
Manages student final presentations exams scheduling and scores evaluation.

- **Eloquent Models**:
  - `Presentation` (`app/Domain/Assessment/Models/Presentation.php`)
  - `PresentationExaminer` (`app/Domain/Assessment/Models/PresentationExaminer.php`)
- **Command Actions**:
  - `SchedulePresentationAction` (`app/Domain/Assessment/Actions/SchedulePresentationAction.php`)
  - `ScorePresentationAction` (`app/Domain/Assessment/Actions/ScorePresentationAction.php`)
  - `CompletePresentationAction` (`app/Domain/Assessment/Actions/CompletePresentationAction.php`)
- **Livewire UI Components**:
  - `PresentationSchedule` (`app/Domain/Assessment/Livewire/PresentationSchedule.php`)
- **Enums**:
  - `PresentationStatus` (`app/Domain/Assessment/Enums/PresentationStatus.php`)

---

## 3. Rubric Aggregate
Manages competency definitions, scoring indicators setups, and active rubrics matching.

- **Eloquent Models**:
  - `Rubric` (`app/Domain/Assessment/Models/Rubric.php`)
  - `Competency` (`app/Domain/Assessment/Models/Competency.php`)
  - `Indicator` (`app/Domain/Assessment/Models/Indicator.php`)
- **Command Actions**:
  - `CreateRubricAction` (`app/Domain/Assessment/Actions/CreateRubricAction.php`)
  - `UpdateRubricAction` (`app/Domain/Assessment/Actions/UpdateRubricAction.php`)
  - `DeleteRubricAction` (`app/Domain/Assessment/Actions/DeleteRubricAction.php`)
  - `CreateCompetencyAction` (`app/Domain/Assessment/Actions/CreateCompetencyAction.php`)
  - `UpdateCompetencyAction` (`app/Domain/Assessment/Actions/UpdateCompetencyAction.php`)
  - `DeleteCompetencyAction` (`app/Domain/Assessment/Actions/DeleteCompetencyAction.php`)
  - `CreateIndicatorAction` (`app/Domain/Assessment/Actions/CreateIndicatorAction.php`)
  - `UpdateIndicatorAction` (`app/Domain/Assessment/Actions/UpdateIndicatorAction.php`)
  - `DeleteIndicatorAction` (`app/Domain/Assessment/Actions/DeleteIndicatorAction.php`)
- **Livewire UI Components**:
  - `RubricManager` (`app/Domain/Assessment/Livewire/RubricManager.php`)
