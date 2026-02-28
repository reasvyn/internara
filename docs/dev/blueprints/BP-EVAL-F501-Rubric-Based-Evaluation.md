# Blueprint: Rubric-Based Evaluation (BP-EVAL-F501)

**Blueprint ID**: `BP-EVAL-F501` | **Requirement ID**: `SYRS-F-501` | **Scope**: Assessment & Performance Synthesis

---

## 1. Context & Strategic Intent

This blueprint defines the formal competency scoring mechanism. It ensures that supervisors can evaluate student performance based on institutional rubrics, providing qualitative marks that contribute to the final grade.

---

## 2. Technical Implementation

### 2.1 Rubric Strategy
- **JSON Flexibility**: Rubrics are stored as JSON blobs, allowing for dynamic curriculum updates without database schema changes.
- **Role-Based Scoring**: Supports distinct rubric inputs from both the **Industry Mentor** and the **Academic Teacher**.

### 2.2 Constructing the Grade
- **PHP 8.4 Excellence**: Use **Property Hooks** for virtual score representations (e.g., `letter_grade`, `is_passing`).
- **Authorization**: Every evaluation MUST be authorized via Policy to ensure only assigned supervisors can submit marks.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Authorization Audit**: Test that an unassigned supervisor attempting to evaluate a student receives a `403 Forbidden`.
- **Unit (`Unit/`)**:
    - **JSON Schema Audit**: Verify that saving a rubric with an invalid JSON structure (e.g., missing mandatory criteria fields) triggers a validation error.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Architectural (`arch/`)**:
    - **Domain Isolation**: Ensure the `Assessment` module does not contain hard-coded rubric labels (must be dynamic).
- **Feature (`Feature/`)**:
    - **Audit Trail**: Verify that every rubric submission is recorded in the `ActivityLog` with its score payload.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Contract Compliance**: Verify that the evaluation logic is exposed via a Service Contract.
- **Unit (`Unit/`)**:
    - **Calculation Speed**: Verify that the grade conversion (marks to letter) performs in < 1ms.

---

## 4. Documentation Strategy
- **Staff Guide**: Update `docs/wiki/assessment-and-evaluation.md` to document the rubric-based evaluation process for Teachers and Mentors.
- **Developer Guide**: Update `modules/Assessment/README.md` to include the JSON schema for rubrics and the authorization policy logic.
- **Registry**: Update `docs/wiki/institutional-foundation.md` on how to configure institutional rubrics.
