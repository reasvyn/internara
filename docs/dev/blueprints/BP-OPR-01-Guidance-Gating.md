# Blueprint: The Guidance Gating Invariant (BP-OPR-01)

**Blueprint ID**: `BP-OPR-01` | **Scope**: Operational Telemetry | **Compliance**: `ISO/IEC 25010`

---

## 1. Context & Strategic Intent

This blueprint defines the critical **Gating Logic** that ensures students possess institutional knowledge before commencing active internship duties. It establishes a mandatory sequence where the acknowledgment of guidelines acts as a technical prerequisite for recording daily telemetry.

---

## 2. The Gating Mechanism (S3 - Scalable)

### 2.1 Pre-condition Invariant
A student is prohibited from creating `Journal` entries or recording `Attendance` until all mandatory institutional handbooks have been acknowledged.

### 2.2 Functional Dependencies
Interaction occurs via **Service Contracts** to maintain isolation:
1. **`HandbookService` (Provider)**: Manages handbooks and tracks acknowledgment state.
2. **`JournalService` (Consumer)**: Checks the gate before log submission.
3. **`AttendanceService` (Consumer)**: Checks the gate before clock-in.

---

## 3. Implementation Logic (PEP & S1 - Secure)

The gate is enforced at the **Service Layer** entry points.

### 3.1 Verification Logic (The PEP Check)
Before persistence, services MUST invoke the provider contract. 
- **Security (S1)**: Methods MUST also verify subject identity via Policies to prevent a student from acknowledging another student's handbook (IDOR mitigation).

### 3.2 Resilience (S2 - Sustain)
- **Bootstrapping**: Handbook discovery logic MUST handle scenarios where no handbooks are defined, defaulting to an "Open" state to prevent system deadlock.
- **i18n**: All gating error messages MUST be localized (`__('guidance::messages.must_complete_guidance')`).

---

## 4. Administrative Governance

### 4.1 Feature Toggle
Governed by `setting('feature_guidance_enabled')`.
- **Audit Trail**: Every change to this toggle MUST be recorded in the `ActivityLog` with the administrator's UUID.

### 4.2 Mandatory Protocol
- **Rule**: Only handbooks flagged as **"Mandatory"** contribute to the gating logic.
- **Traceability**: Acknowledgment records MUST store the specific version/timestamp of the handbook.

---

## 5. Construction & UI (A11y Aware)

- **Thin Component**: The `GuidanceHub` Livewire component delegates all acknowledgment persistence to the `HandbookService`.
- **A11y (WCAG 2.1 AA)**: The handbook download and "Agree" buttons MUST be keyboard-navigable and provide clear ARIA labels.

---

## 6. Verification & Validation (V&V) - TDD Strategy

### 6.1 Gating Integrity Tests (S1 - Secure)
- **Blocked Access Audit (Negative)**: 
    - **Scenario**: Create a Student account without handbook acknowledgments. Attempt to call `JournalService::create()`.
    - **Assertion**: Must return `403 Forbidden` with the specific translation key for guidance.
- **Bypass Audit**: 
    - **Scenario**: Set `feature_guidance_enabled` to `false`.
    - **Assertion**: The same student must now be able to call `JournalService::create()` successfully.
- **IDOR Audit**: Verify that a student cannot acknowledge a handbook on behalf of another student's UUID.

### 6.2 Architectural Testing (`tests/Arch/GuidanceTest.php`)
- **Isolation**: Verify `Journal` and `Attendance` modules DO NOT import any models from the `Guidance` module (use `HandbookService` interface only).
- **Dualism**: Ensure `HandbookService` implements `EloquentQuery` for reads and `GuidanceOrchestrator` implements `BaseService` for acknowledgment logic.

### 6.3 Functional & A11y
- **Acknowledgement Loop**: Verify that acknowledging a handbook correctly updates the `ActivityLog` and the student's status.
- **A11y (WCAG 2.1 AA)**: Use **Dusk** to verify that the "Agree" button is reachable via `TAB` and has a visible focus state.

### 6.4 Quantitative
- **Cognitive Load**: Maintain Cognitive Complexity < 15 for the `hasCompletedMandatory()` logic.
- **Coverage**: 100% coverage for the gating PEP checks in all consumer modules.

_This blueprint records the current state of operational gating. Evolution of the student readiness loop must be reflected here._
