# Blueprint: Pre-Placement Checklist (BP-REG-F301)

**Blueprint ID**: `BP-REG-F301` | **Requirement ID**: `SYRS-F-301` | **Scope**: Internship Lifecycle Management

---

## 1. Context & Strategic Intent

This blueprint defines the mandatory requirement verification loop. It ensures that students satisfy all institutional prerequisites (e.g., medical checks, parent permission) before being eligible for industrial placement.

---

## 2. Technical Implementation

### 2.1 The Requirement Guard (S1 - Secure)
- **State Machine**: Registration status MUST NOT transition to `active` until the `InternshipRequirementService::hasClearedMandatory()` returns `true`.
- **Verification Loop**: Only assigned `Teachers` or `Admins` can verify individual requirement submissions.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Gating Audit**: Attempt to `approve()` a registration with incomplete requirements (Must throw `AppException` 422).
    - **IDOR Audit**: Verify that a student cannot verify their own requirement submission.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Logic Verification**: Test `hasClearedMandatory()` with edge cases (e.g., mix of optional and mandatory requirements).
- **Architectural (`arch/`)**:
    - **Service Standards**: Ensure `InternshipRequirementService` extends `EloquentQuery`.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Isolation**: Ensure the verification logic is decoupled from the `Registration` model via a Service Contract.
- **Feature (`Feature/`)**:
    - **Event Consistency**: Verify that clearing requirements dispatches a `RequirementsCleared` event.

---

## 4. Documentation Strategy
- **Staff Guide**: Update `docs/wiki/enrollment-matching.md` to document the requirement verification process.
- **Student Guide**: Update `docs/wiki/getting-started.md` to explain the pre-placement checklist and submission process.
- **Developer Guide**: Update `modules/Internship/README.md` to document the requirement state machine and `hasClearedMandatory()` logic.
