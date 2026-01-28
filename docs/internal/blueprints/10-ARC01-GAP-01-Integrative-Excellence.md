# Application Blueprint: Operational Readiness & Governance (ARC01-GAP-01)

**Series Code**: `ARC01-GAP-01` **Status**: `Planned`

> **System Requirements Specification Alignment:** This blueprint satisfies the **Administrative
> Orchestration** ([SYRS-F-101]) and **Security & Integrity** ([SYRS-NF-502]) requirements by
> ensuring the system is administratively prepared for the internship cycle.

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Establish the administrative foundation for the internship lifecycle,
ensuring high-fidelity stakeholder onboarding and strict temporal governance.

**Objectives**:

- **Batch Onboarding**: Eliminate manual friction by providing utilities for mass subject
  registration via CSV.
- **Placement Governance**: Formalize partner capacity management and explicit **Advisor Allocation**
  to ensure every student placement has an assigned monitoring teacher.
- **Temporal Integrity**: Enforce strict lifecycle-aware activity windows via `start_date` and
  `end_date` invariants on internship registrations.
- **Student Deliverables Management**: Prioritize the formalization of post-internship artifacts
  (PKL Reports and PPT Presentations) as mandatory completion requirements.
- **Operational Accountability**: Implement audit logs for critical data changes and global
  **Operational State Control** for system phases.

---

## 2. Functional Specification (MVP Focus)

### 2.1 Capability Set

- **Batch Onboarding Utility**: CSV-based import engine for Students, Teachers, and Mentors with
  automated credential generation.
- **Placement & Advisor Orchestrator**: Tools for Staff to manage partner quotas and explicitly
  link Monitoring Teachers to student placements.
- **Temporal Guard**: Logic-level enforcement of internship period boundaries for all student
  activities.
- **Deliverable Submission Subsystem**: A dedicated engine for students to upload mandatory
  post-internship artifacts, specifically PKL Reports (PDF) and Presentation Materials (PPT).
- **Audit Logs & State Control**: Immutable logs for critical data modifications and global
  operational toggles to manage system-wide phases (e.g., Registration vs. Operation).

### 2.2 Stakeholder Personas

- **Staff**: Orchestrates batch imports, manages quotas, and assigns advisors.
- **System Administrator**: Manages global system phases and audits administrative logs.

---

## 3. Architectural Impact (Logical View)

### 3.1 Domain Integration & Data Architecture

- **Internship Module**:
    - Enhancement of `internship_registrations` table with `start_date`, `end_date`, and
      `teacher_id` (Advisor).
    - Enhancement of `internship_placements` table with `capacity_quota` management.
    - Implementation of `internship_deliverables` (UUID, registration_id, type [Report/PPT],
      file_path, status).
- **Core Module**:
    - Implementation of `AuditLog` (UUID, subject_id, action, payload, timestamp).
    - Global state management for `system_phase`.

### 3.2 Logic Invariants

- **The Advisor Invariant**: Every student placement must be mapped to exactly one Monitoring
  Teacher.
- **The Period Invariant**: Activities are strictly rejected if `today()` is outside the assigned
  internship date range.
- **The Deliverable Invariant**: A student's program completion status cannot be certified until
  both the PKL Report and PPT Presentation are submitted and verified.
- **The Phase Invariant**: Certain operations (like registration) are restricted based on the
  global `system_phase`.

---

## 4. Presentation Strategy (MVP UI)

### 4.1 UI Design Invariants

- **Standardized Progres Views**: Utilizing MaryUI Progress Bars and Tables to visualize quota
  utilization.
- **Temporal Feedback**: Clear UI indicators for students when they are outside their active
  internship period.

---

## 5. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate on administrative and temporal validation tests via
  `composer test`.
- **Quality Gate**: zero static analysis or formatting violations via `composer lint`.
- **Acceptance Criteria**:
    - Successful batch import of 100+ students via CSV.
    - Verified rejection of out-of-period attendance/journal attempts.
    - Functional submission and administrative verification of PKL Reports and PPT presentations.
    - Audit logs correctly record placement modifications.

---

_This blueprint establishes the administrative baseline, ensuring Internara is ready for 
large-scale school operations._