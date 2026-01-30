# Application Blueprint: Operational Readiness & Governance (ARC01-GAP-01)

**Series Code**: `ARC01-GAP-01` **Status**: `In Progress`

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
- **Placement Governance**: Formalize partner capacity management and explicit **Advisor
  Allocation** to ensure every student placement has an assigned monitoring teacher.
- **Temporal Integrity**: Enforce strict lifecycle-aware activity windows via `start_date` and
  `end_date` invariants on internship registrations.
- **Flexible Assignment Governance**: Replace hardcoded deliverables with a dynamic assignment
  engine. While "Laporan PKL (PDF)" and "Presentasi PKL (PPT)" remain the default requirements,
  administrators must have the autonomy to add, modify, or remove assignments based on institutional
  policy.
- **Operational Accountability**: Implement a dual-tier logging strategy:
    - **System Log (AuditLog)**: Immutable logs for critical backend data modifications.
    - **User Log (ActivityLog)**: Behavioral logs for stakeholder interactions using
      `Spatie/ActivityLog`.

---

## 2. Functional Specification (MVP Focus)

### 2.1 Capability Set

- **Batch Onboarding Utility**: CSV-based import engine for Students, Teachers, and Mentors with
  automated credential generation.
- **Placement & Advisor Orchestrator**: Tools for Staff to manage partner quotas and explicitly link
  Monitoring Teachers to student placements.
- **Temporal Guard**: Logic-level enforcement of internship period boundaries for all student
  activities.
- **Dynamic Assignment Engine**: A polymorphic submission system where admins define required tasks.
  The system initializes with default tasks (Report & PPT) but supports custom task definitions
  (e.g., "Industry Certificate", "Weekly Log Summary") with specific validation rules (file types,
  deadlines).
- **Audit Logs & State Control**: Immutable logs for critical data modifications and global
  operational toggles to manage system-wide phases (e.g., Registration vs. Operation).
- **User Activity Tracking**: Capture user-level events such as successful logins, profile
  modifications, and document submissions to provide a behavioral audit trail for stakeholders.

### 2.2 Stakeholder Personas

- **Staff**: Orchestrates batch imports, manages quotas, and assigns advisors.
- **System Administrator**: Manages global system phases and audits administrative logs.
- **Student/User**: Can view their own activity history for transparency.

---

## 3. Architectural Impact (Logical View)

### 3.1 Domain Integration & Data Architecture

- **Internship Module**:
    - Enhancement of `internship_registrations` table with `start_date`, `end_date`, and
      `teacher_id` (Advisor).
    - Enhancement of `internship_placements` table with `capacity_quota` management.
    - Implementation of student assignments (managed via Assignment module).
- **Core Module**:
    - Global state management for `system_phase`.
- **Log Module (Centralized Observability)**:
    - **System Log (AuditLog)**: Managed within this module to provide an immutable trail of
      critical data modifications. Existing `AuditLog` infrastructure will be consolidated here.
    - **User Log (ActivityLog)**: Integration of `Spatie/ActivityLog` for behavioral tracking.
        1.  Configuration: Use **UUID** for `subject_id` and `causer_id`.
        2.  Standardization: Define `log_name` identifiers: `auth`, `profile`, `submission`.
        3.  Implementation: Provide centralized Traits/Contracts for other modules to record user
            activities.
- **Assignment Module (Evolutionary Target)**:
    - **Purpose**: Decouple student assignment logic (e.g., Reports, Presentations) from the core
      Internship lifecycle.
    - **Entities**:
        - `AssignmentType`: Template for assignment formats (e.g., "Laporan PKL", "Presentasi PKL").
        - `Assignment`: Specific tasks linked to an `internship_id` or `academic_year`.
        - `Submission`: Student uploads for a specific `Assignment`.
- **Requirement Management (Internship Module)**:
    - **Context**: Registration prerequisites (e.g., "Surat Izin Orang Tua", "Pakta Integritas")
      remain in the **Internship** module as they are part of the onboarding/registration flow.
    - **Migration Path**: Transition legacy report/presentation records into the new Assignment
      structure, while maintaining requirement records in the Internship domain.

### 3.2 Logic Invariants

- **The Advisor Invariant**: Every student placement must be mapped to exactly one Monitoring
  Teacher.
- **The Period Invariant**: Activities are strictly rejected if `today()` is outside the assigned
  internship date range.
- **The Assignment Fulfillment Invariant**: A student's program completion status cannot be
  certified until **all mandatory assignments** (as defined by the current policy/assignment types)
  are submitted and verified.
- **The Phase Invariant**: Certain operations (like registration) are restricted based on the global
  `system_phase`.

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
    - Admin can successfully add a custom mandatory assignment (e.g., "Industry Certificate").
    - Verified that student cannot complete the program if the new custom mandatory assignment is
      missing.
    - Audit logs correctly record placement modifications.

---

## 6. Improvement Suggestions

- **Assignment Modularization**: All assignment-related mechanisms (including submissions) have been decoupled from the Internship module and migrated to a dedicated **Assignment** module to ensure domain purity and better maintainability.

---

_This blueprint establishes the administrative baseline, ensuring Internara is ready for large-scale
school operations._
