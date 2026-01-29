# Application Blueprint: Instructional Execution & Mentoring (ARC01-GAP-02)

**Series Code**: `ARC01-GAP-02` **Status**: `Planned`

> **System Requirements Specification Alignment:** This blueprint implements the **Progress
> Monitoring** ([SYRS-F-201], [SYRS-F-202]) and **Assessment** ([SYRS-F-301]) requirements of the
> authoritative **[System Requirements Specification](../system-requirements-specification.md)**.

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Formalize the daily instructional loop by implementing competency registries,
mentoring session documentation, and activity governance.

**Objectives**:

- **Competency Mapping**: Establish a linear departmental rubric for students to claim skills during
  journal entries.
- **Mentoring Dialogue**: Bridge Teacher and Mentor roles with authenticated mentoring logs to
  document student growth.
- **Submission Governance**: Implement journal submission windows and a simplified mechanism for
  student absence requests (Leave/Permit).
- **Instructional Evidence**: Ensure every journal entry serves as verifiable evidence of
  instructional progress.

---

## 2. Functional Specification (MVP Focus)

### 2.1 Capability Set

- **Competency Registry**: A simplified framework for Teachers to define department-specific skills
  that students claim during journal entry.
- **Mentoring Session Registry**: A centralized log for documenting formal interaction between
  supervisors (Teachers/Mentors) and students.
- **Submission & Absence Management**: A governance engine for journal submission deadlines and a
  workflow for student absence requests (Leave/Sick).
- **Instructional Progress Recap**: A tabular summary for Teachers to view competency claims derived
  from student journals.

### 2.2 Stakeholder Personas

- **Teacher**: Defines competencies, verifies student claims, and logs academic mentoring.
- **Industry Supervisor (Mentor)**: Authenticates to log workplace mentoring sessions.
- **Student**: Logs daily journals, claims competencies, and requests absences.

---

## 3. Architectural Impact (Logical View)

### 3.1 Domain Integration & Data Architecture

- **Assessment Module**:
    - Creation of `competencies` (UUID, code, name, department_id).
    - Creation of `competency_claims` (Polymorphic link between Journals and Competencies).
- **Mentor/Teacher Modules**:
    - Implementation of `mentoring_sessions` (UUID, registration_id, creator_id, content, date).
- **Attendance Module**:
    - Implementation of `absence_requests` (UUID, registration_id, type, reason, status).

### 3.2 Logic Invariants

- **The Claim Invariant**: Students can only claim competencies mapped to their specific department.
- **The Submission Invariant**: Journals cannot be submitted or edited outside the defined
  submission window (e.g., within 7 days of the activity date).

---

## 4. Presentation Strategy (MVP UI)

### 4.1 UI Design Invariants

- **Claim Selection**: Integrated dropdowns in the journal entry form for competency tagging.
- **Mentoring Timeline**: A chronological view of bimbingan logs for both students and teachers.
- **Mobile-First Capture**: Optimized absence request forms for quick student use.

---

## 5. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate on all instructional and mentoring test suites.
- **Quality Gate**: zero violations in static analysis (`composer lint`).
- **Acceptance Criteria**:
    - Functional mapping of journals to departmental competencies.
    - Verified authenticated persistence for mentoring logs.
    - Functional "Competency Recap" table for cohort-wide monitoring.

---

_By completing this blueprint, Internara ensures that daily internship activities are
instructionally meaningful and rigorously documented._
