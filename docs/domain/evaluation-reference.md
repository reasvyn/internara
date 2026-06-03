# Evaluation — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Aggregate-rooted layout mapping for the Evaluation domain

This reference defines the structured aggregates and code layout within the **Evaluation** domain.

---

## 1. Evaluation Aggregate
Collects multi-perspective internship quality feedback (student evaluating mentors, companies, and facilities).

- **Eloquent Models**:
  - `Evaluation` (`app/Domain/Evaluation/Models/Evaluation.php`)
- **Policies**:
  - `EvaluationPolicy` (`app/Domain/Evaluation/Policies/EvaluationPolicy.php`)
- **Command Actions**:
  - `SubmitEvaluationAction` (`app/Domain/Evaluation/Actions/SubmitEvaluationAction.php`)
  - `DeleteEvaluationAction` (`app/Domain/Evaluation/Actions/DeleteEvaluationAction.php`)
  - `EvaluateMentorAction` (`app/Domain/Evaluation/Actions/EvaluateMentorAction.php`)
- **Livewire UI Components**:
  - `MentorEvaluationManager` (`app/Domain/Evaluation/Livewire/MentorEvaluationManager.php`)
- **Entities (Domain Rules)**:
  - `EvaluationResult` (`app/Domain/Evaluation/Entities/EvaluationResult.php`)
- **Enums**:
  - `EvaluationCategory` (`app/Domain/Evaluation/Enums/EvaluationCategory.php`)
