# Blueprint: Absence Orchestration (BP-OPR-F402)

**Blueprint ID**: `BP-OPR-F402` | **Requirement ID**: `SYRS-F-402` | **Scope**: Monitoring & Vocational Telemetry

---

## 1. Context & Strategic Intent

This blueprint defines the lifecycle of student leave requests. It ensures that non-presence is formally justified with digital evidence and verified by supervisors to maintain academic integrity.

---

## 2. Technical Implementation

### 2.1 The Absence Lifecycle (S1 - Secure)
- **Justification**: Every request MUST include a category and digital evidence (e.g., doctor's note).
- **Conflict Invariant**: An `approved` absence MUST block any `Check-In` attempts for that day.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Conflict Audit**: Verify that `AttendanceService::checkIn()` throws an exception if an approved absence exists.
    - **Evidence Audit**: Verify that "Sick" category requires a file upload.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Status Trail**: Verify that `AbsenceRequest` captures the `verified_by` UUID correctly.
- **Architectural (`arch/`)**:
    - **Standard Compliance**: Ensure `AbsenceRequest` model uses `HasStatus` concern.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Isolation**: Ensure `Attendance` module interacts with `Media` strictly via Service Contracts for evidence.
- **Feature (`Feature/`)**:
    - **Scoring Integration**: Verify that `ComplianceService` treats approved absences as neutral/authorized.

---

## 4. Documentation Strategy
- **Student Guide**: Update `docs/wiki/daily-monitoring.md` to document the process for submitting absence requests and required evidence.
- **Staff Guide**: Update `docs/wiki/daily-monitoring.md` on how to verify and approve absence requests.
- **Developer Guide**: Update `modules/Attendance/README.md` to document the absence lifecycle and its integration with the participation scoring engine.
