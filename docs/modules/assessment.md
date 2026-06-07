# Assessment — Documentation Overview

> Last updated: 2026-06-06  
> Changes: Removed the presentations table and examiner panels (offline school concern); simplified
> rubric competencies/indicators into a single JSON rubric structure.

Rubric-based competency evaluation: rubrics with weighted criteria structures stored as JSON, and
student assessment grading.

For complete technical reference including API, models, actions, and components, see
[assessment-reference.md](assessment-reference.md).

---

## Key Principles

- **Rubrics Define the Scoring Framework** — Each rubric represents an evaluation template. Its
  competencies and indicators are stored directly as a structured JSON column inside the rubric
  record, ensuring historical grades do not break if templates are edited later.
- **Assessments are Grade Submissions** — Assessments record the student's grades against specific
  rubric indicators.
- **Immutable Grades** — Once finalized, an assessment record is immutable. Corrections require a
  new assessment round to preserve grade integrity.

---

## Context Boundary

The **Assessment** module owns rubric definitions and student assessment records.

- Consumes **Enrollment (`registrations`)** to identify students and placements.
- Consumes **User (`users`)** to verify teacher and supervisor evaluators.
- Provides graded component scores to **Reports (`reports`)** to compile the Final Student Grade
  Card (_Rapor PKL_).

---

## Module Rules

- **Assessment Finalization is Irreversible:** Once finalized, scores cannot be modified.
  Corrections require launching a new assessment round.
- **Rubric Structure Immutability:** Rubrics can be reused. If a template is changed, existing
  finalized assessments are unaffected because they reference the indicator keys or score snapshots.
- **Dual Mentor Grading Fallbacks:** Supervisor evaluations are optional. If the corporate
  supervisor is inactive, the teacher can activate:
    - **Proxy Evaluation:** Inputs scores on behalf of the supervisor based on physical paperwork.
    - **Weight Redistribution:** Shifts the supervisor's weight directly to the teacher and exam
      component weights in the Reports module.
- **Grader Authorization:** Only assigned school teachers and industry supervisors can grade
  students. Students have read-only access to their assessments.

---

## Submodules

- **Assessment:** Records the actual grades submitted by an evaluator for a student's registration
  using a rubric. Tracks scores per indicator, overall comments, and finalization status.
- **Rubric:** The evaluation template containing a JSON `structure` that defines the competencies,
  indicators, weights, and maximum scores:
    ```json
    [
        {
            "competency": "Sikap Kerja",
            "weight": 40,
            "indicators": [{ "id": "ind_1", "name": "Disiplin", "max_score": 100, "weight": 50 }]
        }
    ]
    ```

---

## Error Handling & Failure Modes

- **Modifying a Finalized Assessment:** System blocks edits and throws a `RejectedException`.
- **Incomplete Grading Sheet:** Submitting an assessment with missing indicator marks throws a
  `ValidationFailedException`.
- **Unauthorized Evaluator:** An evaluator trying to grade a student who is not assigned to them
  (via placement or registration) is blocked with a `403 Forbidden` response.

---

## Quick References

### Actions & Business Logic

- **9** actions across submodules (reduced from 17 by moving competencies/indicators to JSON and
  deleting presentations):
    - Rubric CRUD (create, update, delete)
    - Assessment grading (initialize, calculate, update, finalize)

### Data & Persistence

- **2** models: `Assessment`, `Rubric` (stores the criteria structure JSON).
- UUID PKs, index on foreign keys.

### User Interface

- **3** Livewire components:
    - `RubricManager` — Rubric builder with JSON structure editor.
    - `AssessmentGrading` — Evaluator grading sheet.
    - `AssessmentView` — Student grade viewer.

---

For complete technical reference, see [assessment-reference.md](assessment-reference.md).
