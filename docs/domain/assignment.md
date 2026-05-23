# Assignment Domain

## Purpose

Assignment powers the task-based learning workflow that structures a student's day-to-day 
internship activity. Teachers create tasks that translate abstract program requirements into 
concrete, trackable pieces of work with clear deadlines. Students complete and submit their work, 
and teachers evaluate it with grades and feedback. This is the primary mechanism for ongoing 
formative assessment throughout an internship — distinct from periodic rubric-based competency 
evaluations (Assessment) and qualitative mentor feedback (Evaluation). Everyday internship 
learning happens through assignments.

## Boundary

**In scope:** Task creation and management (title, description, due dates, attached resources, 
point values, associated rubric criteria), submission workflow (create draft, submit final, view 
submission status, track version history), file uploads for task resources and student 
submissions, grading (numeric score, percentage, rubric-referenced, with written feedback), 
submission lifecycle management (draft, submitted, graded, returned for revision), deadline 
enforcement and late submission flagging, individual and group task assignments, submission 
dashboards for both students and teachers, grade reports and analytics.

**Out of scope:** Competency rubric definitions (Assessment domain owns rubrics — Assignment 
can reference them for criteria-aligned grading), daily journaling (Logbook domain), attendance 
tracking (Attendance domain), certificate issuance (Certificate domain), program-level 
requirement definitions (Internship domain), mentor evaluation collection (Evaluation domain), 
incident reporting (Incident domain), guidance document acknowledgements (Guidance domain).

## Key Concepts

**Tasks.** A task is a well-defined unit of work assigned within an internship program. Teachers 
author tasks with: a title and detailed description of what is expected, a due date and time, 
optional attached resource files (templates, reference materials, examples), a point value or 
weight, optional association with specific rubric criteria from the Assessment domain for 
criteria-aligned grading, and assignment scope (all students, specific groups, or individuals). 
Tasks belong to an internship program and are visible to all enrolled students unless 
specifically targeted or gated by group membership.

**Submissions.** Students submit their completed work against tasks. Submissions support multiple 
formats: text (rich text responses, code), file uploads (documents, spreadsheets, presentations, 
images, code archives), or a combination of both. The submission system supports a drafting 
workflow — students can save work in progress and refine it before marking it as final. Once 
submitted (marked as final), the submission is locked against further edits. The system 
automatically detects and flags late submissions based on the task's due date but does not reject 
them — the teacher has discretion to accept or return late work. Each submission round is 
versioned for audit; the version history shows every draft save and final submit with timestamps.

**Grading and Feedback.** Teachers evaluate each submission and produce a grade: a numeric score 
(raw points or percentage), an optional mapping to rubric criteria scores (if the task references 
an assessment rubric), and written qualitative feedback. Feedback can include overall comments 
and per-criterion annotations. The graded submission is visible to the student, who can review 
the score and comments. Before finalization, teachers can adjust grades, but each adjustment 
requires a documented reason. Once a grade is finalized, it becomes immutable — further changes 
require an override record that preserves the original grade alongside the new one, with a reason 
for the change.

**Submission Lifecycle.** Each submission moves through a defined state machine. DRAFT: the 
student is actively working; the entry can be edited freely and is visible only to the student. 
SUBMITTED: the student has marked it as final; the submission is locked for student edits and 
enters the teacher's grading queue. GRADED: the teacher has assigned a score and feedback; the 
result is visible to the student. RETURNED: the teacher has sent the submission back to the 
student for revision, including feedback on what needs improvement; the student can create a new 
submission round based on the feedback. The RETURNED state enables an iterative improvement cycle 
— students learn from feedback, revise their work, and resubmit for re-evaluation. Each round 
(original + revisions) is versioned and preserved in the audit trail.

**Deadline Management.** Tasks have explicit due dates that drive dashboard visibility and 
notifications. Upcoming deadlines appear on student dashboards (countdown display) and mentor 
dashboards (which students have pending deadlines). Students can filter their task list by 
deadline proximity. Teachers receive notifications about unusually high concentrations of overdue 
submissions. Individual deadline extensions (accommodations for specific students) are supported 
and logged with the reason and granting teacher. Mass deadline extensions (adjusting for the 
entire class) are also supported with a single action.

## Requirements

### User Stories & Rules

- **Teacher:** As a teacher, I want to create assignments with due dates so that students have clear tasks to complete
- **Teacher:** As a teacher, I want to publish assignments so that students can see and work on them
- **Teacher:** As a teacher, I want to grade submissions with scores and feedback so that students know their performance
- **Teacher:** As a teacher, I want to return submissions for revision so that students can improve their work
- **Student:** As a student, I want to view my pending and past assignments so that I can manage my workload
- **Student:** As a student, I want to submit my work, optionally saving drafts first, so that I can refine before finalizing
- **Student:** As a student, I want to receive grades and feedback so that I understand my strengths and areas for improvement
- **Student:** As a student, I want to resubmit after revision so that I can demonstrate improvement
- **System:** As the system, I want to enforce the submission state machine so that no invalid transitions occur
- **System:** As the system, I want to detect and flag late submissions so that teachers can make informed grading decisions
- Once finalized, grades cannot be changed without an override record preserving the original 
grade and documenting the reason.
- Late submissions are flagged with the computed delay duration but never automatically rejected 
— teachers retain discretion.
- RETURNED submissions create a new version; the original submission and grade are preserved in 
the audit trail.
- Task due dates must fall within the internship program's date range.
- Teachers can only grade submissions from students in their assigned mentorship groups, enforced 
through Mentor domain assignment data.
- Deleting a task requires explicit confirmation and cascades to all associated submissions with 
a logged archive event.
- A student can have at most one active (non-graded, non-returned) submission version per task at 
any time.

### Process Flow

```
Assignment Lifecycle:

DRAFT ──→ PUBLISHED ──→ CLOSED

Submission Lifecycle:

DRAFT ──→ SUBMITTED ──→ VERIFIED ──→ GRADED (immutable)
            │                │
            ↓                ↓
    REVISION_REQUIRED   REVISION_REQUIRED
            │
            ↓
          DRAFT  (resubmit cycle)
```

- **Assignment**: DRAFT (teacher authoring), PUBLISHED (visible to students, accepting submissions), CLOSED (no further submissions)
- **Submission**: DRAFT (student editing, not yet final), SUBMITTED (final, in grading queue), VERIFIED (checked for completeness), GRADED (score and feedback recorded, immutable)
- **REVISION_REQUIRED**: Teacher returned for improvements — student starts a new DRAFT round
- Once GRADED, submissions are immutable — corrections require an override record

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateAssignmentAction` | Creates a new task assignment |
| `UpdateAssignmentAction` | Updates an assignment before it receives submissions |
| `DeleteAssignmentAction` | Deletes an unpublished assignment |
| `PublishAssignmentAction` | Publishes an assignment, making it visible to students |
| `SubmitAssignmentAction` | Submits a student's work as final |
| `GradeSubmissionAction` | Grades a submission with score and feedback |
| `VerifySubmissionAction` | Verifies a submission's completeness before grading |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Assignment`, `AssignmentType`, `Submission` |
| **Entities** | `AssignmentRules` (mandatory flag, overdue checks); `SubmissionState` (editability, verification status) |
| **Enums** | `AssignmentStatus` — `DRAFT`, `PUBLISHED`, `CLOSED`; `SubmissionStatus` — `DRAFT`, `SUBMITTED`, `VERIFIED`, `GRADED`, `REVISION_REQUIRED` |
| **Livewire** | `AssignmentManager`, `Submission`, `SubmissionGrading` |
| **Policies** | `AssignmentPolicy`, `SubmissionPolicy` |
| **Notifications** | `AssignmentNotification`, `SubmissionFeedbackNotification` |
| **Form Requests** | `CreateAssignmentRequest`, `SubmitAssignmentRequest` |

## Dependencies

| Dependency | Reason |
|---|---|
| Internship | Tasks belong to programs; program date ranges constrain task deadline placement |
| Registration | Student program enrollment determines task visibility and submission eligibility 
|
| Assessment | Optional: tasks can reference rubric criteria for criteria-aligned grading |
| Document | File attachments for task resources and student submission uploads |
| Core | BaseAction for operations, BaseModel for persistence, SmartLogger for audit, 
BaseRecordManager for the teacher CRUD interface |


