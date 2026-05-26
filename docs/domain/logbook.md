# Logbook Domain

## Purpose

Logbook provides the daily journaling system where students record their internship experiences. 
Each day, students write about what they worked on, what they learned, challenges they faced, and 
their plans for the next session. This serves dual purposes: it develops the professional habit 
of reflective practice — a key competency for any professional — and it gives mentors 
real-time visibility into the student's day-to-day activities, challenges, and growth. The 
logbook is intentionally kept simple in structure: structured daily entries with a clear 
submission workflow (draft, submit, mentor acknowledge). The simplicity keeps the barrier to 
writing low, which encourages consistency — and consistency is the primary goal.

## Boundary

**In scope:** Daily log entry creation (date, activities description, learnings and insights, 
challenges encountered, plans for next session), draft editing before submission (entries can be 
saved and refined over multiple sessions), submission workflow (DRAFT → SUBMITTED → 
MENTOR_ACKNOWLEDGED, with optional RETURNED state from mentor to student), mentor review and 
acknowledgement (mentor reads and marks as acknowledged, can add comments), photo and document 
attachments to entries via media library, log entry history and calendar-based view, submission 
pattern monitoring (consecutive missed days, submission frequency statistics), logbook export for 
portfolio compilation or program reporting.

**Out of scope:** Task management and graded work submissions (Assignment domain), attendance 
clock-in and absence management (Attendance domain), structured evaluation forms and scoring 
(Evaluation domain), incident reporting (Incident domain), report generation for program 
requirements (Internship and Document domains), guidance document acknowledgements (Guidance 
domain), competency rubric scoring (Assessment domain), supervision notes (Mentor domain).

## Key Concepts

**Log Entries.** A log entry captures one day of internship activity in a structured format. Each 
entry includes: the date (which must fall within the internship period and cannot be in the 
future), a description of activities performed (what the student worked on — this is the core 
content), learnings and insights (what the student learned from the day's work — the reflective 
component), challenges encountered (difficulties, blockers, questions that arose — important 
for mentor awareness), and plans for the next session (what the student intends to work on next). 
Entries can optionally include attachments — photos of work in progress, screenshots of code or 
designs, scanned documents, or any relevant files — stored through the media library. The 
structure guides students toward meaningful daily reflection without being burdensome.

**Submission Workflow.** Entries progress through a three-state lifecycle with an optional fourth 
state. DRAFT: the student is composing or refining the entry; drafts are editable and visible 
only to the student. SUBMITTED: the student marks the entry as ready for review; submitted 
entries are visible to the mentor and locked against further editing by the student. 
MENTOR_ACKNOWLEDGED: the mentor has read the entry and marked it as acknowledged; acknowledged 
entries are completely immutable — no further edits by either party. Optionally, a SUBMITTED 
entry can be RETURNED by the mentor if they need more information or want the student to expand 
on something; the entry returns to DRAFT status (but with the mentor's comments visible), and the 
student can revise and resubmit. The return option is at the mentor's discretion.

**Mentor Review.** Mentors see a feed of submitted log entries from all their assigned students, 
ordered by date (most recent first). Each entry shows the student's name, date, a preview of the 
content, and the current status. Mentors can: read the full entry, view any attached files, add 
optional comments or questions (visible to the student), and mark the entry as acknowledged. The 
mentor's dashboard prominently highlights: pending acknowledgements (entries SUBMITTED but not 
yet acknowledged), entries with mentor comments awaiting student response (if the entry was 
returned), and submission gaps (students who have not created any entries recently). Mentors can 
filter the feed by student, date range, submission status, and date.

**Compliance Monitoring.** The system watches for submission gaps and flags them proactively. If 
a student goes N consecutive days without creating any log entry (N is configurable per program, 
default 3 days), the mentor receives a notification. If the gap extends further (default 5+ 
days), the program coordinator is also notified. These escalating notifications ensure that 
logbook compliance problems are addressed promptly rather than discovered at the end of the 
internship. Beyond gap detection, the system tracks submission statistics: entries per week, 
percentage of internship days logged, average acknowledgement turnaround time, and submission 
consistency over time. These statistics are available for mentor dashboards and program-level 
reporting.

**Calendar View.** Both students and mentors can view log entries on an interactive calendar. 
Each day is color-coded to show status: green for acknowledged, yellow for submitted (pending 
acknowledgement), blue for draft (not yet submitted), gray for no entry, and red for a flagged 
gap. The calendar provides instant visual awareness of logging consistency. Clicking a day shows 
the entry preview (or an "add entry" button for empty days within the allowed range). This view 
is particularly useful for mentors during supervision sessions to quickly assess how the student 
is tracking.

## Requirements

### User Stories & Rules

- **Student:** As a student, I want to create daily log entries so that I document my internship activities and reflections
- **Student:** As a student, I want to save drafts before submitting so that I can refine my entries
- **Student:** As a student, I want to attach files to my log entries so that I can include supporting evidence
- **Mentor:** As a mentor, I want to view my mentees' submitted log entries so that I can monitor their progress
- **Mentor:** As a mentor, I want to acknowledge entries or return them for revision so that students get feedback
- **Admin:** As an admin, I want to view compliance reports so that I can identify students with submission gaps
- **System:** As the system, I want to notify mentors of submission gaps so that issues are addressed early
- Log entries cannot be backdated beyond a configurable number of days (default 7 days) — this 
prevents bulk retroactive filling of the logbook.
- Acknowledged entries are completely immutable — no edits, no deletions, no changes of any 
kind. Corrections require the mentor to return the entry (if not yet acknowledged) or the student 
to add a new entry referencing the original.
- Each student can have at most one log entry per calendar day — duplicate entries for the same 
day are not permitted.
- Log entries are private between the student and their assigned mentor — not visible to other 
students, mentors not assigned to the student, or admins without explicit access.
- Consecutive days without an entry beyond the program's threshold triggers automatic 
notification escalation (mentor → coordinator).
- Students cannot create entries for future dates — entries must be for today or past dates 
within the allowed backdate window.
- Mentor acknowledgement is not a quality evaluation — it is a simple confirmation that the 
entry has been read. Quality feedback flows through Evaluation or Mentor domains.

### Process Flow

```
Log Entry Lifecycle:

DRAFT ──→ SUBMITTED ──→ MENTOR_ACKNOWLEDGED (immutable)
               │
               ↓
       REVISION_REQUIRED ──→ DRAFT (resubmit cycle)
```

- **DRAFT**: Student composing — editable, visible only to the student
- **SUBMITTED**: Marked as final — locked for student, visible to mentor
- **MENTOR_ACKNOWLEDGED**: Mentor read and acknowledged — completely immutable
- **REVISION_REQUIRED**: Mentor returned for more information — returns to DRAFT with mentor's comments
- Each student can have at most one log entry per calendar day
- Consecutive days without an entry beyond threshold triggers automatic notification escalation

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateLogbookAction` | Creates a new logbook entry |
| `UpdateLogbookAction` | Updates a draft logbook entry |
| `SubmitLogbookAction` | Submits a log entry for mentor review |
| `DeleteLogbookAction` | Deletes a draft logbook entry |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Logbook` (log entry with date, content, learning outcomes, attachments) |
| **Entity** | `LogbookState` (editability, verification status checks) |
| **Enums** | `LogbookStatus` — `DRAFT`, `SUBMITTED`, `VERIFIED`, `REVISION_REQUIRED` |
| **Http/Requests** | `CreateLogbookRequest` |
| **Livewire** | `LogbookEntry`, `LogbookManager` |
| **Policy** | `LogbookPolicy` |

## Dependencies

| Dependency | Reason |
|---|---|
| Registration | Links the student to their internship program, providing context and date 
boundaries for allowed entry dates |
| User | Student identity for entries, mentor identity for acknowledgements and comments |
| Core | BaseAction, BaseModel, SmartLogger, BaseRecordManager for the listing and management UI |


