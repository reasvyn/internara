# Evaluation — API Reference

Total: 8 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Evaluation/Actions/DeleteEvaluationAction.php` | `DeleteEvaluationAction` | `BaseAction` | Deletes an evaluation |
| `Evaluation/Actions/EvaluateMentorAction.php` | `EvaluateMentorAction` | `BaseAction` | Submits a mentor evaluation (upsert logic) |
| `Evaluation/Actions/SubmitEvaluationAction.php` | `SubmitEvaluationAction` | `BaseAction` | Submits a general evaluation (upsert) |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Evaluation/Entities/EvaluationResult.php` | `EvaluationResult` | `BaseEntity` | Read-only DTO for computed evaluation results |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Evaluation/Enums/EvaluationCategory.php` | `EvaluationCategory` | `LabelEnum` | Evaluation type categories |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Evaluation/Livewire/MentorEvaluationManager.php` | `MentorEvaluationManager` | `Component` | UI for managing mentor evaluations |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Evaluation/Models/Evaluation.php` | `Evaluation` | `BaseModel` | Eloquent model for evaluations |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Evaluation/Policies/EvaluationPolicy.php` | `EvaluationPolicy` | `BasePolicy` | Authorization for evaluation operations |
