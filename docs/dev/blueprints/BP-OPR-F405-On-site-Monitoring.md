# Blueprint: On-site Monitoring (BP-OPR-F405)

**Blueprint ID**: `BP-OPR-F405` | **Requirement ID**: `SYRS-F-405` | **Scope**: Monitoring & Vocational Telemetry

---

## 1. Context & Strategic Intent

This blueprint defines the formal documentation mechanism for physical site visits. It ensures that Academic Supervisors (Teachers) can record their on-site monitoring activities, providing auditable proof of institutional oversight and evaluating the student's physical condition at the placement.

---

## 2. Technical Implementation

### 2.1 The Mentoring Visit Entity
- **Domain Module**: This logic resides within the `Mentor` module, specifically focusing on the `MentoringVisit` model.
- **Persistence**: The `mentoring_visits` table MUST enforce referential integrity at the service layer by linking `registration_id` and `teacher_id` (UUIDs).
- **Condition State**: The on-site condition of the student MUST be captured using a standardized `Enum` (e.g., `Excellent`, `Good`, `Fair`, `Poor`) to facilitate future data synthesis.

### 2.2 Security & Accountability (S1 - Secure)
- **Role Isolation**: Only the assigned `Teacher` for a specific registration is authorized to create or modify a visit record.
- **Traceability**: The creation and modification of visit records MUST be logged via the `InteractsWithActivityLog` concern.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Authorization Audit**: Verify that a teacher attempting to log a visit for an unassigned student receives a `403 Forbidden` response.
    - **Data Integrity**: Verify that submitting a visit without the mandatory `condition` enum throws a `ValidationException`.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Architectural (`arch/`)**:
    - **Identity Standards**: Ensure the `MentoringVisit` model implements the `HasUuid` trait.
- **Unit (`Unit/`)**:
    - **State Management**: Test the enum casting and validation logic for the student condition field.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Feature (`Feature/`)**:
    - **Academic Scoping**: Verify that visit queries are automatically filtered by the `HasAcademicYear` global scope, preventing data drift between cohorts.

---

## 4. Documentation Strategy
- **Staff Guide**: Update `docs/wiki/daily-monitoring.md` to document the physical visit recording process for Teachers.
- **Developer Guide**: Update `modules/Mentor/README.md` to include the `mentoring_visits` schema and relationship mappings.
- **Reporting Guide**: Update `docs/wiki/reporting.md` to explain how on-site monitoring data is aggregated in institutional reports.
