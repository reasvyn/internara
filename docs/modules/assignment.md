# Assignment

> **Last updated:** 2026-06-08

Task management and submission tracking: assignments with deadlines, student submissions with draft workflow, grading with feedback, and revision loops.

## Purpose & Boundary

Assignment manages the full lifecycle of coursework tasks within internship programs. Teachers create assignments with descriptions, due dates, point values, and optional rubric references and file attachments. Students submit work through a draft-submit workflow with version history. Teachers grade submissions with numeric scores and written feedback, and can request revisions that restart the submission cycle.

Out of scope: daily logbook entries (Journals), rubric-based competency assessment (Assessment), final grade aggregation (Reports).

## Submodules

### Assignment
Task definition entity: title, description, due dates, point value, optional rubric reference, and resource file attachments (via Spatie Media Library). Teachers CRUD assignments scoped to a program. Per-student extensions adjust individual deadlines. Deadlines cannot be set in the past.

### Submission
Student work entity: content, file uploads, status lifecycle, score, grader feedback, and grader identity. Status progression: `draft` → `submitted` → `verified` → `graded`. Teachers can return `submitted` to `draft` via `revision_required`. `graded` is terminal. Late submissions are flagged but accepted (configurable to block). Version history is preserved as immutable snapshots on every status transition.

## Key Concepts

### Draft→Submit→Grade Workflow

Submissions follow a controlled lifecycle:
1. **DRAFT**: Student writes and saves incrementally. Multiple saves create version snapshots.
2. **SUBMITTED**: Student finalizes submission. Teachers can return to DRAFT with revision request.
3. **VERIFIED**: Teacher acknowledges receipt (optional step depending on program config).
4. **GRADED**: Teacher assigns numeric score and written feedback. Terminal state — no further edits.

### Version History

Every save, submit, and revision creates an immutable version snapshot capturing the full submission state, timestamp, and actor. Students and teachers can browse the version timeline for audit purposes. Version data is append-only and stored as JSON snapshots.

### Late Submission Handling

Submissions received after the assignment deadline are automatically flagged as `late`. The original deadline is preserved alongside the submission timestamp. The system can be configured to either accept late submissions with a flag or block them entirely. Teachers can grant per-student extensions that adjust the deadline for specific individuals.

## Dependencies

- Core (base classes)
- Program (program context for assignment scoping)
- Enrollment (registration context for student access)

## Used By

- Reports (assignment grades feed into final grade card)
