# Application Blueprint: Operational Layer (ARC01-OPER-01)

**Series Code**: `ARC01-OPER-01` **Status**: `Archived` (Done)

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
  approval lifecycles (Locked upon approval).
- **Attendance Orchestration**: Check-in/out protocols with automated late-status determination based on institutional settings.
- **Temporal Scoping Invariant**: Implementation of the `HasAcademicYear` concern to ensure data relevance to the active baseline.
- **Secure Media Base**: Configuration of the `private` disk for journal evidence attachments to prevent unauthorized access.

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
- **Transactional Consistency**: Mandatory use of database transactions for complex state transitions (e.g., Attendance + Points).

---

## 4. Documentation Strategy (Knowledge View)

- **Operational Standards**: Documentation of the journal locking protocol and attendance late-logic.
- **Knowledge Base**: Authoring of the initial `README.md` files for the `Journal` and `Attendance` modules.
- **Security Protocols**: Definition of the private storage strategy for student-generated assets.

---

## 5. Audit & Evaluation Report (v0.13.0 Audit)

**Date**: 2026-02-04 | **Auditor**: Gemini

### 5.1 Realized Outcomes
- **Robust Orchestration**: `JournalService` and `AttendanceService` successfully implemented with strict gating and period invariants.
- **UUID & Scoping**: Verified 100% adoption of `HasUuid` and `HasAcademicYear` in operational models.
- **Gating System**: Successfully integrated with the `Guidance` module to enforce institutional briefing compliance.
- **Storage Privacy**: Evidence attachments successfully routed to the `private` disk.

### 5.2 Identified Anomalies & Corrections
- **Model Redundancy**: Found empty DocBlocks for ID properties already handled by traits. **Correction**: Removed redundant properties from `JournalEntry` and `AttendanceLog`.
- **Media Access Gap**: The design mentioned "Signed URL access," but implementation for serving private files was deferred to the **[Setup (ARC01-BOOT-01)](09-ARC01-BOOT-01-System-Initialization.md)** series for better centralization.

### 5.3 Improvement Plan
- [x] Standardize operational models for better cleanliness.
- [x] Link operational metrics to the subsequent Intelligence dashboards.

---

## 6. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate across the operational verification suites via
  **`composer test`**.
- **Quality Gate**: zero static analysis violations via **`composer lint`**.
- **Acceptance Criteria**:
    - Functional implementation of `HasAcademicYear` isolation.
    - Verified security of journal media attachments via private storage.
    - Features match the "Progress Monitoring" requirements defined in the SyRS.

---

## 7. Improvement Suggestions (Legacy)

- **Grading Optimization**: Realized via the **[Instructional Execution (ARC01-GAP-02)](11-ARC01-GAP-02-Instructional-Execution.md)** series.
- **Role-based Segmentation**: Realized via specialized dashboards in the **[Identity (ARC01-USER-01)](03-ARC01-USER-01-Identity.md)** and **[Workspaces (ARC01-FEAT-01)](06-ARC01-FEAT-01-Assessment-Workspaces.md)** series.