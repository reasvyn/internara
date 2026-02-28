# Blueprint: Academic Scoping (BP-ORG-F202)

**Blueprint ID**: `BP-ORG-F202` | **Requirement ID**: `SYRS-F-202` | **Scope**: Institutional Architecture

---

## 1. Context & Strategic Intent

This blueprint defines the automatic partitioning of domain data by Academic Year and Department. It ensures data sovereignty and prevents "Data Drift" between different cohorts.

---

## 2. Technical Implementation

### 2.1 The Academic Scope Invariant (S1 - Secure)
- **Global Scope**: All scoped models MUST use the `HasAcademicYear` trait.
- **Persistence Gate**: On creation, models MUST automatically inherit the `active_academic_year` from system settings if not provided.

### 2.2 Temporal Isolation (S3 - Scalable)
- **Query Filter**: Standard queries MUST NOT return records from inactive academic years unless explicitly requested via `withoutAcademicYear()`.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Scope Leak Audit**: Verify that setting `active_academic_year` to Year A prevents retrieval of Year B records in standard queries.
- **Unit (`Unit/`)**:
    - **Default Inheritance**: Verify that a new model instance automatically receives the current setting's year UUID.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Architectural (`arch/`)**:
    - **Trait Implementation**: Verify that all models in `Internship`, `Journal`, and `Attendance` modules implement the `HasAcademicYear` contract if they are cohort-based.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Indexed Querie**: Ensure `academic_year` columns are indexed across all participating tables.
- **Unit (`Unit/`)**:
    - **Query Efficiency**: Verify that the global scope adds only one `WHERE` clause to the generated SQL.

---

## 4. Documentation Strategy
- **Admin Guide**: Update `docs/wiki/program-setup.md` to explain how academic year scoping affects data visibility across modules.
- **Developer Guide**: Update `modules/Core/README.md` to document the `HasAcademicYear` trait and global scope implementation.
- **Governance**: Update `docs/dev/conventions.md` to mandate academic scoping for all cohort-based domain models.
