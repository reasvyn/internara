# Assessment — API Reference
> Last updated: 2026-05-23
> Changes: fix: complete system initialization overhaul — security, middleware, recovery, form objects, docs


Total: 31 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Assessment/Actions/AutoCalculateAssessmentAction.php` | `AutoCalculateAssessmentAction` | `BaseAction` | Auto-calculates final assessment from submissions and reports |
| `Assessment/Actions/CompletePresentationAction.php` | `CompletePresentationAction` | `BaseAction` | Marks a presentation as complete with final scores |
| `Assessment/Actions/CreateCompetencyAction.php` | `CreateCompetencyAction` | `BaseAction` | Creates a new competency within a rubric |
| `Assessment/Actions/CreateIndicatorAction.php` | `CreateIndicatorAction` | `BaseAction` | Creates a new indicator within a competency |
| `Assessment/Actions/CreateRubricAction.php` | `CreateRubricAction` | `BaseAction` | Creates a new rubric for an assessment |
| `Assessment/Actions/DeleteCompetencyAction.php` | `DeleteCompetencyAction` | `BaseAction` | Deletes a competency |
| `Assessment/Actions/DeleteIndicatorAction.php` | `DeleteIndicatorAction` | `BaseAction` | Deletes an indicator |
| `Assessment/Actions/DeleteRubricAction.php` | `DeleteRubricAction` | `BaseAction` | Deletes a rubric |
| `Assessment/Actions/FinalizeAssessmentAction.php` | `FinalizeAssessmentAction` | `BaseAction` | Finalizes an assessment, locking scores |
| `Assessment/Actions/InitializeAssessmentAction.php` | `InitializeAssessmentAction` | `BaseAction` | Sets up a new assessment from a rubric |
| `Assessment/Actions/SchedulePresentationAction.php` | `SchedulePresentationAction` | `BaseAction` | Schedules a presentation with date and examiners |
| `Assessment/Actions/ScoreIndicatorAction.php` | `ScoreIndicatorAction` | `BaseAction` | Scores a specific indicator for an assessment |
| `Assessment/Actions/ScorePresentationAction.php` | `ScorePresentationAction` | `BaseAction` | Scores presentation by an examiner |
| `Assessment/Actions/UpdateAssessmentScoresAction.php` | `UpdateAssessmentScoresAction` | `BaseAction` | Updates all scores on an assessment |
| `Assessment/Actions/UpdateCompetencyAction.php` | `UpdateCompetencyAction` | `BaseAction` | Updates a competency's details |
| `Assessment/Actions/UpdateIndicatorAction.php` | `UpdateIndicatorAction` | `BaseAction` | Updates an indicator's details |
| `Assessment/Actions/UpdateRubricAction.php` | `UpdateRubricAction` | `BaseAction` | Updates a rubric's details |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Assessment/Entities/AssessmentResult.php` | `AssessmentResult` | `BaseEntity` | Read-only DTO for computed assessment results |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Assessment/Enums/EvaluatorRole.php` | `EvaluatorRole` | `LabelEnum` | Evaluator role (teacher/industry) |
| `Assessment/Enums/PresentationStatus.php` | `PresentationStatus` | `LabelEnum`, `StatusEnum` | Presentation lifecycle status |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Assessment/Livewire/AssessmentGrading.php` | `AssessmentGrading` | `Component` | Grading interface for an assessment |
| `Assessment/Livewire/AssessmentView.php` | `AssessmentView` | `Component` | Read-only view of an assessment |
| `Assessment/Livewire/PresentationSchedule.php` | `PresentationSchedule` | `BaseRecordManager` | Manages presentation scheduling and scoring |
| `Assessment/Livewire/RubricManager.php` | `RubricManager` | `Component` | CRUD management of rubrics, competencies, indicators |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Assessment/Models/Assessment.php` | `Assessment` | `BaseModel` | Eloquent model for student assessments (soft-deletes) |
| `Assessment/Models/Competency.php` | `Competency` | `BaseModel` | Eloquent model for rubric competencies |
| `Assessment/Models/Indicator.php` | `Indicator` | `BaseModel` | Eloquent model for rubric indicators (scoring items) |
| `Assessment/Models/Presentation.php` | `Presentation` | `BaseModel` | Eloquent model for student presentations |
| `Assessment/Models/PresentationExaminer.php` | `PresentationExaminer` | `BaseModel` | Pivot model linking examiners to presentations |
| `Assessment/Models/Rubric.php` | `Rubric` | `BaseModel` | Eloquent model for rubrics |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Assessment/Policies/AssessmentPolicy.php` | `AssessmentPolicy` | `BasePolicy` | Authorization for assessment operations |
