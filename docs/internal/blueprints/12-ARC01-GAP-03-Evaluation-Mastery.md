# Application Blueprint: Evaluation & Certification Mastery (ARC01-GAP-03)

**Series Code**: `ARC01-GAP-03` **Status**: `Planned`

> **System Requirements Specification Alignment:** This blueprint implements the **Assessment &
> Reporting** ([SYRS-F-301], [SYRS-F-302]) requirements of the authoritative
> **[System Requirements Specification](../system-requirements-specification.md)**.

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Complete the instructional loop by formalizing mid-program and final
evaluations, scoring logic, and the generation of verifiable completion documents.

**Objectives**:

- **Formative Assessment**: Establish mid-program feedback mechanisms to evaluate student progress
  before final certification.
- **Participation Scoring**: Implement logic that links engagement (attendance/journals) to
  objective competency-linked scores.
- **Instructional Loop Closure**: Implementation of the final **Summative Assessment** and scoring
  finalization.
- **Credentialing**: Ensure all evaluations result in a verifiable record of achievement for the
  student.

---

## 2. Functional Specification (MVP Focus)

### 2.1 Capability Set

- **Formative Assessment Framework**: A simplified mid-program feedback mechanism for supervisors to
  evaluate student progress.
- **Competency-Linked Scoring**: Logic that calculates scores based on validated journal entries,
  mentoring attendance, and supervisor feedback.
- **Instructional Transcript Engine**: A print-friendly (PDF) summary generator for student
  competency attainment and program completion.
- **Placement Readiness Traceability**: Administrative tools to track student completion status
  across all graduation prerequisites.

### 2.2 Stakeholder Personas

- **Teacher**: Performs formative and summative evaluations and generates student transcripts.
- **Staff**: Verifies final program completion and readiness for graduation.
- **Student**: Downloads the final transcript of competency attainment.

---

## 3. Architectural Impact (Logical View)

### 3.1 Domain Integration & Data Architecture

- **Assessment Module**:
    - Implementation of `evaluation_records` (UUID, registration_id, type, scores, feedback).
    - Scoring engine logic for aggregating claims and attendance.
- **Report Module**:
    - Implementation of the `TranscriptService` for document orchestration.

### 3.2 Logic Invariants

- **The Completion Invariant**: Transcripts can only be generated once the internship period is
  closed and all mandatory evaluations are submitted.
- **The Scoring Invariant**: Scores must be derived from verifiable evidence (approved journals,
  attendance logs, and signed evaluations).

---

## 4. Presentation Strategy (MVP UI)

### 4.1 UI Design Invariants

- **Evaluation Dashboards**: Role-specific views for Teachers and Mentors to complete assessments.
- **Print-Friendly Templates**: Simplified, high-fidelity PDF templates for transcripts (ID/EN).
- **Readiness Badges**: Visual indicators (MaryUI) for completion status in staff views.

---

## 5. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate on assessment and reporting suites.
- **Quality Gate**: zero static analysis violations via `composer lint`.
- **Acceptance Criteria**:
    - Successful generation of a printable competency transcript for a student.
    - Verified accuracy of participation-weighted scoring logic.
    - Administrative view correctly identifies students ready for program completion.

---

_By completing this blueprint, Internara ensures that every internship results in a verifiable,
data-backed credential of competency attainment._
