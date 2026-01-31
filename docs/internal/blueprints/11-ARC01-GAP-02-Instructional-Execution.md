# Application Blueprint: Instructional Execution, Mentoring & Evaluation (ARC01-GAP-02)

**Series Code**: `ARC01-GAP-02` **Status**: `Done`

> **System Requirements Specification Alignment:** This blueprint implements the **Progress
> Monitoring** ([SYRS-F-201], [SYRS-F-202]) and **Assessment & Reporting** ([SYRS-F-301],
> [SYRS-F-302]) requirements of the authoritative
> **[System Requirements Specification](../system-requirements-specification.md)**.

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Formalize the complete daily instructional loop and its transition into
formal academic evaluation and credentialing.

**Objectives**:

- **Competency Mapping**: Establish a linear departmental rubric for students to claim skills during
  journal entries.
- **Mentoring Dialogue**: Bridge Teacher and Mentor roles with authenticated mentoring logs to
  document student growth.
- **Submission Governance**: Implement journal submission windows and a simplified mechanism for
  student absence requests (Leave/Permit).
- **Formative & Summative Assessment**: Establish feedback mechanisms and final evaluation protocols
  to assess student mastery.
- **Participation Scoring**: Implement logic that links engagement (attendance/journals) to
  objective scores.
- **Credentialing**: Ensure all activities result in a verifiable record of achievement (Transcript)
  for the student.

---

## 2. Functional Specification (MVP Focus)

### 2.1 Capability Set

- **Competency Registry**: A simplified framework for Teachers to define department-specific skills
  that students claim during journal entry.
- **Mentoring Session Registry**: A centralized log for documenting formal interaction between
  supervisors (Teachers/Mentors) and students using `MentoringVisit` and `MentoringLog`.
- **Submission & Absence Management**: A governance engine for journal deadlines and a workflow for
  student absence requests (Leave/Sick).
- **Formative Assessment Framework**: Mid-program feedback tools for supervisors to evaluate student
  progress.
- **Competency-Linked Scoring**: Logic calculating scores based on validated journal entries,
  attendance, and supervisor evaluations.
- **Instructional Transcript Engine**: A print-friendly (PDF) generator for student competency
  attainment and program completion.
- **Instructional Progress Recap**: Dashboards for Teachers to monitor cohort-wide skill mastery and
  completion readiness.

### 2.2 Stakeholder Personas

- **Teacher**: Defines competencies, verifies student claims, performs evaluations, and generates
  transcripts.
- **Industry Supervisor (Mentor)**: Authenticates to log mentoring sessions and provide industry
  evaluations.
- **Student**: Logs journals, claims competencies, requests absences, and downloads their final
  transcript.
- **Staff**: Verifies final program completion and readiness for graduation.

---

## 3. Architectural Impact (Logical View)

### 3.1 Domain Integration & Data Architecture

- **Assessment Module**:
    - Creation of `competencies` (UUID, code, name, department_id).
    - Creation of `journal_competency`: A many-to-many pivot table linking Journals and
      Competencies for performant aggregation.
    - Implementation of `assessments`: Records for formative and summative evaluations (UUID, type,
      scores, feedback).
    - Scoring engine logic for aggregating claims and attendance engagement.
- **Internship Module (Centralized Supervision)**:
    - Implementation of `mentoring_sessions`: A unified registry for guidance logs utilizing
      `MentoringVisit` and `MentoringLog`.
- **Attendance Module**:
    - Implementation of `absence_requests` (UUID, registration_id, student_id, type, reason, status).
- **Report Module**:
    - Implementation of the `TranscriptService` for student achievement document orchestration.

### 3.2 Logic Invariants

- **The Claim Invariant**: Students can only claim competencies mapped to their specific department.
- **The Submission Window Invariant**: Journals cannot be submitted or edited outside the
  operational window defined by `setting('journal_submission_window', 7)` days.
- **The Absence Conflict Invariant**: The system must reject `checkIn()` attempts if an
  `absence_request` for the same date has been approved.
- **The Completion Invariant**: Transcripts can only be generated once the internship period is
  closed and all mandatory evaluations/assignments are submitted.
- **The Scoring Invariant**: Scores must be derived from verifiable evidence (approved journals,
  attendance, and signed evaluations).

---

## 4. Presentation Strategy (MVP UI)

### 4.1 UI Design Invariants

- **Smart Claim Selection**: Integrated dropdowns in the journal form with contextual skill
  suggestions.
- **Unified Mentoring Timeline**: A single view of all teacher and mentor logs for comprehensive
  tracking.
- **Evaluation Dashboards**: Role-specific interfaces for completing formative and summative
  assessments.
- **Print-Friendly Templates**: Simplified PDF templates for transcripts in Indonesian and English.
- **Mobile-First Capture**: Optimized forms for quick on-site use (Absences, Mentoring,
  Evaluations).

---

## 5. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate on all instructional, mentoring, and assessment test suites.
- **Quality Gate**: zero violations in static analysis (`composer lint`).
- **Acceptance Criteria**:
    - Functional mapping of journals to departmental competencies.
    - Successful generation of a printable competency transcript for a student.
    - Verified accuracy of participation-weighted scoring logic.
    - Verified authenticated persistence for mentoring logs.
    - Functional "Competency Recap" and "Readiness" views for monitoring.

---

_By completing this combined blueprint, Internara ensures that daily activities are
instructionally meaningful, rigorously documented, and correctly transformed into verifiable
academic credentials._