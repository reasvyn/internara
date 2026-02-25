# Application Blueprint: Operational Program (ARC01-ORCH-01)

**Series Code**: `ARC01-ORCH` | **Scope**: `Phase 3: The WHAT` | **Compliance**: `ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint authorizes the construction of the **Core Business Logic**. It defines the 
lifecycle of an internship program, from the registration of student cohorts to their 
placement within industry partners.

- **SyRS Traceability**:
    - **[SYRS-F-201]**: Internship Program Orchestration (Batches/Years).
    - **[SYRS-F-202]**: Partner (Company) Management.
    - **[SYRS-F-301]**: Student Registration & Document Verification.
    - **[SYRS-F-302]**: Placement Allocation & Slot Tracking.

---

## 2. User Roles & Stakeholders

- **School Administrator**: Program design, batch creation, and partner onboarding.
- **Students**: Participation in programs, document submission, and placement tracking.
- **Instructors**: Oversight of registrations within their departments.

---

## 3. Modular Impact Assessment

1.  **Internship**: Owners of programs, batches, and registrations.
2.  **Mentor**: Technical bridge for on-site industrial supervisors.
3.  **Media**: Centralized storage for verification documents (MOU, Student IDs).

---

## 4. Logic & Architecture (The Orchestration Engine)

### 4.1 The Internship Lifecycle
1.  **Program Definition**: Defining high-level goals.
2.  **Batch Opening**: Temporal window for a specific cohort (e.g., "Gen 2026").
3.  **Registration**: Students applying to a batch with mandatory requirements check.
4.  **Placement**: Final assignment of a verified registration to a Company Slot.

### 4.2 Slot & Capacity Management
- **Mandate**: The system must prevent over-allocation.
- **Mechanism**: Companies define `max_slots` per program. Placements are validated 
  against remaining capacity at the Service Layer.

---

## 5. Contract & Interface Definition

### 5.1 `Modules\Internship\Services\Contracts\InternshipService`
- `openBatch(array $data): Batch`: Initializes a new cohort.
- `registerStudent(string $studentUuid, string $batchUuid): Registration`: Atomic entry logic.

### 5.2 `Modules\Internship\Services\Contracts\PlacementService`
- `allocate(string $registrationUuid, string $companyUuid): void`: Finalizes the match.
- `getRemainingSlots(string $companyUuid): int`: Real-time telemetry.

---

## 6. Data Persistence Strategy

### 6.1 State Transitions (HasStatus)
Registrations move through a strictly audited state machine:
- `draft` -> `submitted` -> `verified` -> `placed` -> `ongoing` -> `completed`.

### 6.2 The Requirement Invariant
- **Protocol**: Registrations cannot transition to `verified` until all mandatory 
  attachments (mapped in `internship_requirements`) are present in the `Media` module.

---

## 7. Verification Plan (V&V View)

### 7.1 Complex Logic Testing
- **Race Condition Audit**: Concurrent placement attempts for the last available slot.
- **Requirement Logic**: Negative tests attempting to verify a registration without 
  mandatory documents.

### 7.2 Architecture Police
- Enforce that `Internship` module does not have a physical FK to `Companies` if they reside 
  in different domains (unless merged into the same module).

---

_This blueprint constitutes the authoritative engineering record for the Operational Program. 
Integrity of the student-partner match is a systemic priority._
