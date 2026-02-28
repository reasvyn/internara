# Blueprint: Digital Guidance (BP-OPR-F303)

**Blueprint ID**: `BP-OPR-F303` | **Requirement ID**: `SYRS-F-303` | **Scope**: Internship Lifecycle Management

---

## 1. Context & Strategic Intent

This blueprint defines the mandatory handbook acknowledgement tracking. It ensures that students have read and acknowledged institutional guidelines before they are permitted to record vocational activities.

---

## 2. Technical Implementation

### 2.1 The Guidance Gating Invariant (S1 - Secure)
- **Pre-Activity Gate**: The `AttendanceService` and `JournalService` MUST check the `HandbookService::hasCompletedMandatory()` status before allowing mutations.
- **Immutability**: Once an acknowledgement is recorded, it is timestamped and linked to the student's UUID.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Gating Audit**: Attempt to `checkIn()` without acknowledging mandatory handbooks (Must return `403 Forbidden`).
    - **IDOR Audit**: Verify that a student cannot acknowledge a handbook for another student.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Status Logic**: Test `hasCompletedMandatory()` logic with various handbook versions.
- **Architectural (`arch/`)**:
    - **Contract Compliance**: Verify all Guidance services extend `BaseService`.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Isolation**: Ensure `Journal` module DOES NOT import `Guidance` models directly.
- **Feature (`Feature/`)**:
    - **Performance Audit**: Verify that the gating check adds < 5ms to the request lifecycle.

---

## 4. Documentation Strategy
- **Student Guide**: Update `docs/wiki/getting-started.md` to explain the handbook acknowledgement requirement.
- **Admin Guide**: Update `docs/wiki/program-setup.md` on how to manage mandatory guidance materials.
- **Developer Reference**: Update `modules/Guidance/README.md` to document the gating logic and cross-module integration.
