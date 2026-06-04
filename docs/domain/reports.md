# Reports — Documentation Overview

> Last updated: 2026-06-04
> Changes: Rewrote overview with developer-friendly content, added error handling, failure modes, and CLI commands

Student final report writing, revision workflow, and supervisor review.

For complete technical reference including API, models, actions, and components, see [reports-reference.md](reports-reference.md).

---

## Key Principles

- **Reports are student-authored** — each student writes a final report summarizing their internship experience. Reports are written incrementally with a draft workflow.
- **Revision workflow mirrors logbook** — reports follow DRAFT → SUBMITTED → VERIFIED flow. Supervisors can request revisions (REVISION_REQUIRED → DRAFT).
- **Supervisors add notes** — company supervisors can attach notes to student reports. These are visible to the student and the mentor.
- **Reports are part of program closure** — all reports must be in VERIFIED status before a program can proceed to closure and archival.

---

## Context Boundary

Consumes enrollment context (which student is in which program). Program provides the report requirements (format, word count, structure). Guidance provides the supervisor who reviews the report. SysAdmin controls visibility settings.

---

## Domain Rules

- **One report per student per program.** Attempting to create a second report throws a `ConflictException`.
- **Report workflow**: DRAFT → SUBMITTED → VERIFIED. Supervisor can return SUBMITTED to DRAFT via REVISION_REQUIRED. VERIFIED is terminal (immutable).
- **Supervisor notes are append-only**: notes are added, not edited or deleted. Full audit trail.
- **All reports must be verified** before the program can be closed/archived. The closure readiness check validates this.
- **Scoring**: reports receive a numeric score and written feedback from the reviewer. Scores contribute to final grade aggregation.

---

## Aggregates

- **Report**: Student-authored document — title, content, attachments, status. Tracks submission date, verification date, score, and feedback. Version history preserved.

---

## Error Handling & Failure Modes

- **Duplicate report**: Blocked with "A report already exists for this program." Student sees a link to their existing draft.
- **Submission without content**: The system validates minimum content length before allowing SUBMITTED status. Short or empty reports are rejected.
- **Verification without score**: The system requires a numeric score and written feedback before marking a report as VERIFIED.
- **Missing supervisor note permission**: If a supervisor is not assigned to the student's placement, they cannot add notes. The system returns a 403.

---

## Quick References

### Actions & Business Logic
- **5** actions across all aggregates
- Report CRUD, submit, verify/score, revision request, supervisor notes

### Data & Persistence
- **2** models: `Report`, `ReportNote`
- UUID PKs, `HasFactory`. Report has unique constraint on (student_id, program_id). Version history stored as JSON

### User Interface
- **1** Livewire component
- Report editor with preview, submission button, revision history viewer, supervisor notes panel

### Authorization
- **0** policies (authorization handled by calling layer)
- Students manage own reports, mentors verify, supervisors add notes

---

For complete technical reference, see [reports-reference.md](reports-reference.md).
