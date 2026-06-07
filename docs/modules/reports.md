# Reports — Documentation Overview

> Last updated: 2026-06-06  
> Changes: Redefined module scope from document writing to Final Student Grade Cards (Rapor PKL),
> aggregating supervisor, teacher, and exam grades.

This module manages the student's Final Grade Card (_Rapor PKL_), which aggregates all assessment
metrics at the end of the internship period and locks the student's final marks before certificate
issuance.

For complete technical reference including API, models, actions, and components, see
[reports-reference.md](reports-reference.md).

---

## Key Principles

- **Grade Aggregation** — The grade card automatically compiles the final composite score based on
  the program's defined weights. The standard formula evaluates:
  $$\text{Final Grade} = (\text{Industry Supervisor Score} \times 40\%) + (\text{School Teacher Score} \times 20\%) + (\text{Exam/Presentation Score} \times 40\%)$$
- **Immutable Results** — Once the report card is marked as `finalized`, it is signed off by the
  coordinator and locked. Further grade changes are blocked to preserve academic integrity.
- **Certificate Trigger** — A finalized Rapor PKL record is the strict prerequisite that unlocks
  eligibility for certificate generation.
- **Qualitative Feedback Registry** — Captures overall testimonial/notes from host companies to be
  printed on the back page of the final report sheet.
- **Standalone Archiving** — The grade card is designed to persist for historical archiving even if
  the student's user account, department, or active registration is deleted. A full snapshot of
  student identity, internship metadata, hosting company, department, and supervisor names is
  captured on save.

---

## Context Boundary

The **Reports** module consumes:

- **Enrollment (`registrations`):** Links 1:1 with the student's active enrollment record. When the
  registration is deleted, the foreign key `registration_id` is set to null, keeping the report
  intact using its snapshotted fields.
- **Assessment (`assessments`):** Gathers scores submitted by school mentors and industry
  supervisors.
- **Assignment (`submissions`):** Final report document submission is treated as a regular
  coursework assignment (rather than being managed in this module), and its grade is pulled from the
  assignment's graded submission.

The **Reports** module provides data to:

- **Certification (`certificates`):** Exposes finalized grades and validation details.

---

## Module Rules

- **Strict 1:1 Registration Link:** One report card per student enrollment. Attempting to create a
  second record throws a `ConflictException`.
- **Finalization Constraint:** A report card cannot be finalized if any required grading component
  (supervisor score, teacher score, exam score) is missing, unless the teacher has activated the
  _Dual Mentor Fallback/Proxy_ bypass in the Assessment module.
- **Finalized is Terminal:** Once a Rapor PKL is `finalized`, its scores are locked and immutable.
  Any corrections require administrative override privileges.

---

## Submodules

- **Report (Grade Card):** Core business entity tracking the student's registration ID, component
  scores (supervisor, teacher, exam), composite score, qualitative feedback, and finalization
  status.

---

## Error Handling & Failure Modes

- **Finalizing Incomplete Grades:** Attempting to finalize a report card with missing scores returns
  a `RejectedException` unless a fallback bypass is logged.
- **Post-Finalization Edit Attempt:** Any write command targeting a finalized record is rejected
  with a `RejectedException`.
- **Missing Enrollment Context:** Attempting to compile a grade card for an inactive or pending
  registration throws a `NotFoundException`.

---

## Quick References

### Actions & Business Logic

- **3** actions across the submodule:
    - `CalculateFinalGradeAction` — Aggregates and calculates composite grades.
    - `FinalizeReportCardAction` — Locks report card and flags certificate eligibility.
    - `UpdateReportCardAction` — Admin-only adjustments.

### Data & Persistence

- **1** model: `Report` (stores scores, letter grade, and supervisor qualitative feedback).
- UUID PKs, unique constraint on `registration_id`.

### User Interface

- **2** Livewire components:
    - `ReportCardViewer` — Student view of grade details.
    - `ReportCardManager` — Coordinator panel to verify, adjust, and sign off grade cards.

---

For complete technical reference, see [reports-reference.md](reports-reference.md).
