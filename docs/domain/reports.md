# Reports Domain

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Extracted from the former Internship domain

## Purpose

The **Reports** domain manages the compilation, submission, review, and revision of final student internship reports. It defines the structured workflow that students must complete to document their findings and accomplishments, as well as the feedback loops between students, academic mentors, and host company supervisors.

Completing the final report is a core graduation requirement. The program closure audit checks the completeness of all student reports before final grades are compiled and certificates are issued.

---

## Design Principles

### 1. Revision Loop Workflow
The final report progresses through a rigorous evaluation workflow managed by `ReportStatus`:
`DRAFT` ➔ `SUBMITTED` ➔ `REVISION_REQUESTED` or `APPROVED`
- **Drafts**: Modified exclusively by students.
- **Review Mode**: Submitting a report locks it from student edits and routes it to the assigned academic mentor for review.
- **Revisions**: If the mentor requests changes, the report transitions to `REVISION_REQUESTED`. The system spawns a new `ReportRevision` record capturing the mentor's revision notes and locking the previous content state for historical auditing.

### 2. Multi-Perspective Feedback and Supervisor Notes
- **Academic Mentors (Teachers)** hold the primary authority to request revisions or approve reports.
- **Industrial Supervisors** have read-only visibility into student reports. They cannot issue formal revisions but can submit `ReportNotes` detailing workplace performance, comments, and project context to assist the academic evaluator.

---

## Domain Boundary

### Technical Ownership
- **Report Submissions**: Form entry, file uploads, and status tracking for final reports.
- **Revision Audits**: Storing historic revisions, capturing revision instructions, and managing version counters.
- **Supervisor Annotations**: Log details and notes submitted by industrial mentors.
- **Completion Auditing**: Providing helper queries to confirm whether student reports are complete and approved.

### Dependencies
- **Core**: Relies on `BaseModel`, `BaseAction`, and `SmartLogger` for auditing transitions.
- **User**: Resolves student and mentor user accounts.
- **Enrollment**: Placement records verify the student's company and academic year context.
- **Guidance**: Connects the supervision group structure to determine which mentor has evaluation rights for a student.

---

## Domain Rules & Invariants

- **R1 — Unique Report Scope**: A student can submit only one final report per active registration.
- **R2 — Evaluation Authority**: Only the assigned academic mentor or a system administrator has the authority to approve a report or request revisions.
- **R3 — Content Lockdown**: Submitted reports (status `SUBMITTED`) and approved reports (status `APPROVED`) are read-only and cannot be modified by the student.
- **R4 — Revision Traceability**: Requesting a revision requires a non-empty feedback string, which is archived in the `ReportRevision` trail.
- **R5 — Closure Block**: An internship program cannot be closed if there are associated reports with `PENDING` or `REVISION_REQUESTED` statuses.

---

## Key Features

- **Report Writer Workspace**: Interactive Livewire editor where students compose report summaries, structure sections, and upload final documents.
- **Revision History Panel**: Accordion view displaying previous versions, submission dates, and mentor feedback.
- **Supervisor Annotator**: Sidebar tool allowing company supervisors to submit observations during the review cycle.
- **Teacher Review Dashboard**: Grid where academic mentors filter reports by status, read contents, and execute approvals or revision requests.
- **Report Downloader**: Download generated report documents and attachments via secure HTTP controllers.
