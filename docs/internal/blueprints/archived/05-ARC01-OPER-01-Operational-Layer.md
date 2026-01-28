# Application Blueprint: Operational Layer (ARC01-OPER-01)

**Series Code**: `ARC01-OPER-01` **Status**: `Archived` (Done)

> **Spec Alignment:** This configuration baseline implements the **Progress Monitoring &
> Traceability** ([SYRS-F-201]) requirements of the authoritative
> **[System Requirements Specification](../../system-requirements-specification.md)**.

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Implement high-frequency activity tracking subsystems (Journals and
Attendance) and ensure data integrity through systemic temporal scoping.

**Objectives**:

- Provide students with a robust mechanism for daily activity recording.
- Enable automated, role-aware attendance monitoring for supervisory stakeholders.
- Enforce strict data isolation between distinct internship cohorts via Academic Year scoping.

---

## 2. Functional Specification

### 2.1 Capability Set

- **Journal Subsystem**: Daily logbook orchestration with draft persistence and multi-authority
  approval workflows.
- **Attendance Orchestration**: Check-in/out protocols with automated late-status determination
  logic.
- **Temporal Scoping Invariant**: Global `HasAcademicYear` concern to ensure data relevance to the
  active baseline.
- **Secure Media Storage**: Cryptographic signed-URL access for journal evidence attachments.

### 2.2 Stakeholder Personas

- **Student**: Utilizes the mobile-first dashboard for real-time activity and attendance logging.
- **Industry Supervisor**: Audits intern presence and provides qualitative feedback on daily logs.
- **Instructor**: Verifies weekly journal entries to satisfy competency achievement invariants.

---

## 3. Architectural Impact (Logical View)

### 3.1 Modular Decomposition

- **Journal Module**: New domain for logbook orchestration and approval lifecycles.
- **Attendance Module**: New domain for high-frequency temporal tracking.
- **Shared Module**: Enhanced with the `HasAcademicYear` scoping invariant.

### 3.2 Persistence Logic

- **Operational Entities**: `journal_entries` and `attendance_logs` utilizing **UUID v4** identity.
- **Isolation Constraint**: Inter-module references restricted to indexed UUIDs; no physical foreign
  keys.

---

## 4. Presentation Strategy (User Experience View)

### 4.1 Design Invariants

- **High-Frequency UX**: Prioritization of "Clock In/Out" actions on mobile viewports.
- **Immutable History**: Logic-enforced lockdown of approved/verified journal records to ensure
  audit integrity.
- **i11n Integrity**: Full localization of attendance statuses and journal fields in **ID** and
  **EN**.

---

## 5. Success Metrics (KPIs)

- **Tracking Consistency**: 90% of active interns satisfy the 4-entry per week submission baseline.
- **Audit Integrity**: zero instances of post-approval record modification.
- **Scoping Accuracy**: 100% of data requests satisfy the active academic year constraint.

---

## 6. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate across the operational verification suites via
  **`composer test`**.
- **Quality Gate**: zero static analysis violations via **`composer lint`**.
- **Acceptance Criteria**:
    - Functional implementation of `HasAcademicYear` isolation.
    - Verified security of journal media attachments via signed URLs.
    - Features match the "Progress Monitoring" requirements defined in the SyRS.

---

## 7. Improvement Suggestions

- **Grading Optimization**: Potential for an automated evaluation engine.
- **Role-based Segmentation**: Suggestions for specialized workspaces for different users.

