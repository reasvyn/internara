# Assignment — Documentation Overview

> Last updated: 2026-06-04
> Changes: Rewrote overview with developer-friendly content, added error handling, failure modes, and CLI commands

Task management and submission tracking: assignments with deadlines, student submissions, grading, and revision workflow.

For complete technical reference including API, models, actions, and components, see [assignment-reference.md](assignment-reference.md).

---

## Key Principles

- **Assignments define tasks** — teachers create assignments with title, description, due dates, point value, rubric references, and optional resources (file attachments).
- **Submissions have a draft workflow** — students write and save drafts before final submission. DRAFT → SUBMITTED → VERIFIED → GRADED. Teachers can request REVISION_REQUIRED which returns to DRAFT.
- **Grading includes numeric score and feedback** — teachers assign a numeric score and written feedback. Rubric-referenced grading is optional.
- **Version history is preserved** — every save and submit is versioned. Teachers and students can view the submission history for audit purposes.

---

## Context Boundary

Owns assignment and submission models. Teachers manage assignments; students submit work. Program provides the context (which program the assignment belongs to). Assessment may reference rubric standards.

---

## Domain Rules

- **Deadlines cannot be in the past** when creating/editing an assignment. Late submissions are flagged but not blocked (configurable).
- **Submissions follow a lifecycle**: DRAFT → SUBMITTED → VERIFIED → GRADED. SUBMITTED can return to DRAFT via REVISION_REQUIRED. GRADED is terminal.
- **Grading feedback is required** before marking a submission as GRADED. Numeric score must be within the assignment's point range.
- **Late submissions** are automatically flagged. The original deadline is recorded alongside the submission timestamp.
- **Extensions** can be granted by the teacher on a per-student basis, adjusting the deadline for specific individuals.
- **Version history**: each status transition creates a version snapshot. Immutable after creation.

---

## Aggregates

- **Assignment**: Task definition — title, description, due dates, points, rubric reference, resources. Teachers CRUD. Extensions per student.
- **Submission**: Student work — content, file uploads, status, score, feedback, grader identity. Draft workflow with version history. Late flagging.

---

## Error Handling & Failure Modes

- **Submission after deadline (no extension)**: The submission is accepted but flagged as `LATE`. The teacher sees the late flag in the grading UI. Configurable: can block late submissions entirely.
- **Grading without score**: The system requires a numeric score within the assignment's point range. `ValidationFailedException` if missing or out of range.
- **Modifying a GRADED submission**: The system blocks edits with a `RejectedException`. The UI shows read-only mode for graded submissions.
- **Version history corruption**: Version snapshots are append-only and immutable. If a version fails to save, the entire submission operation is rolled back.

---

## Quick References

### Actions & Business Logic
- **7** actions across all aggregates
- Assignment CRUD, submission draft/save/submit, grading, extension management, late flagging

### Data & Persistence
- **3** models: `Assignment`, `Submission`, `SubmissionVersion`
- UUID PKs, `HasFactory`. Submission has unique constraint preventing duplicates per student per assignment. Version history as JSON snapshots

### User Interface
- **3** Livewire components for real-time interaction
- Assignment list/manager (teacher), submission form with draft autosave (student), grading panel (teacher)

### Authorization
- **2** authorization policies
- Teachers manage assignments and grade, students submit own work, admins oversee all

---

For complete technical reference, see [assignment-reference.md](assignment-reference.md).
