# Logbook Domain
> Last updated: 2026-06-01
> **Status:** ✅ **Implemented** (11 files) + ⏳ **Planned enhancements** (see [reference](logbook-reference.md))

## Purpose

Logbook manages daily student journal entries — activities, learnings, challenges, and plans,
with mentor review workflow.

---

## Design Principles

### 1. One Entry Per Day

Students can submit at most one logbook entry per calendar day.

### 2. Draft → Submitted → Verified

Entries flow through DRAFT → SUBMITTED → VERIFIED (with optional REVISION_REQUIRED loop).
Mentors review and verify entries.

---

## Domain Boundary

The Logbook domain owns daily student journaling — the chronological record of each student's activities, learnings, challenges, and plans throughout their placement. Students write daily entries describing what they did, what they learned, challenges they faced, and what they plan to do next, with optional file attachments. Each entry moves through a defined workflow from draft to submitted, with mentors able to acknowledge or return entries for revision before final verification. A calendar view color-codes days by entry status: green for verified, yellow for submitted, blue for draft, and gray for days with no entry. The system enforces a strict one-entry-per-calendar-day limit per student.

Logbook does not own student identity data (User/Mentee), program definitions (Internship), attendance records (Attendance), or mentor assignments (Mentor). It stores the journal content and manages the review workflow but does not own the identities of the journal's author or reviewer.

The domain depends on User for student identity, on Mentee for the student-program link, and on Mentor for the reviewer who verifies entries. It provides compliance monitoring by auto-notifying mentors when students miss consecutive days, escalating to program coordinators after a configurable threshold.

---

## Key Features

- Write a daily journal entry recording activities, learnings, challenges encountered, and plans for the next day with optional file attachments.
- Save entries as drafts and submit them later, moving through a draft to submitted to verified workflow.
- Allow mentors to review entries and either acknowledge them or return them for revision with comments.
- View a color-coded calendar showing entry status for every day of the placement period.
- Notify mentors automatically when a student has not submitted an entry for a configurable number of consecutive days.
- Enforce a maximum of one logbook entry per student per calendar day.
- Write daily journal entries with a rich text editor and automatic draft auto-save to prevent data loss.
- Attach files via drag and drop on the journal entry form with upload progress indicators.
- View a color-coded calendar with each day showing a status indicator for verified, submitted, draft, or missing entries.
- See a compliance progress bar showing the percentage of placement days with a logged entry.
- Access a mentor review sidebar where mentors can approve, acknowledge, or return entries for revision with comments.
- Apply a digital signature via canvas or uploaded signature image to formally verify reviewed entries.
- Click on any calendar day to view, edit, or create the journal entry for that date.
 - Capture photos directly from the device camera as part of the daily journal entry.
 - Add photo captions and timestamps to each attached image for activity documentation.

---

## Planned Enhancements — L1: Industry Supervisor Feedback Container

### Background

Audit of the 4 stated logbook goals:
1. Track student progress — daily activities ✅, work competency ⚠️, DUDI input ❌
2. Evidence of PKL execution ✅
3. Help prepare PKL report materials ⚠️
4. Help industry supervisors assess 🟠 Partial

Currently logbook only supports verification by school teachers (`school_teacher`). Industry supervisors (`industry_supervisor`) have no place to provide notes, feedback, or ratings. In practice, DUDI engagement is optional — many industry parties do not participate strictly, so students must not be blocked when supervisors are inactive. The only mandatory obligation from supervisors is the final grade according to each school's rubric.

### Design Principles

1. **Dual verification, not dual gate** — School teachers remain the sole authority for status transitions (`verify` / `revision_required`). Industry supervisors get an optional container for input. No entry status ever waits on a supervisor action.
2. **Optional participation** — All supervisor features are *opt-in*. If a supervisor does not participate, the student is never blocked. Entries can be fully verified by the teacher without any supervisor note.
3. **Gradualism** — Implementation in phases: (1) per-entry notes, (2) report compilation, (3) final rubric assessment.

### Solution Design

#### Phase 1 — Supervisor Note per Entry

Add optional fields to the `Logbook` model:

| Field | Type | Nullable | Description |
|---|---|---|---|
| `supervisor_note` | `text` | ✅ Yes | Supervisor's feedback for this entry |
| `supervisor_reviewed_at` | `timestamp` | ✅ Yes | When the supervisor reviewed the entry |
| `supervisor_id` | `uuid` (FK → users) | ✅ Yes | Which supervisor wrote the note |

**Relationship:** Logbook belongsTo `supervisor` (User).

**Rules:**
- `supervisor_note` can be filled at any time by the supervisor assigned to the student's registration
- Filling `supervisor_note` does NOT change the entry status (`draft/submitted/verified` proceeds independently)
- School teacher remains the sole authority for status transitions (via existing `UpdateLogbookAction`)
- Supervisors see their students' entries in `LogbookManager` (view scoping already exists for `industry_supervisor`)
- Supervisors fill `supervisor_note` from the `LogbookManager` component

**UI Changes:**
- Add "Supervisor Note" column in `LogbookManager` table (editable for supervisor, read-only for teacher)
- Supervisor's modal shows `supervisor_note` textarea field
- Teacher sees supervisor notes as read-only in the entry detail view

#### Phase 2 — PKL Report Compilation

New action `CompileLogbookReportAction`:

```
Input:  registration_id, date_from, date_to, include_supervisor_notes (bool)
Process:
  1. Fetch all entries with status = verified within date range
  2. Collect learning_outcomes per entry
  3. Collect supervisor_note if requested
  4. Render Blade template → PDF via DomPDF
Output: StreamedResponse PDF

Report layout:
  - Header: student name, company name, PKL period
  - Chronological table: date | activity | learning outcomes | supervisor note
  - Photo appendix per entry (if available)
```

#### Phase 3 — Supervisor Final Assessment

New model `IndustryAssessment` (in Logbook or Assessment domain):

| Field | Type | Description |
|---|---|---|
| `registration_id` | uuid (FK) | FK to student registration |
| `supervisor_id` | uuid (FK → users) | Supervisor who assessed |
| `score` | decimal(5,2) | Final score (0-100) |
| `rubric_data` | json | Per-criteria rubric scores (dynamic per school) |
| `notes` | text | Overall comments |
| `submitted_at` | timestamp | When assessment was submitted |

**Rules:**
- One student has at most 1 `IndustryAssessment` per supervisor
- Does NOT block any PKL process — entirely optional
- Final score filled via `IndustryAssessmentForm` Livewire component on supervisor's page
- Rubric is dynamic — stored as JSON defined by the school

**Dependency:** `rubric_data` uses JSON array of objects format:
```json
[
  {"criterion": "Discipline", "weight": 25, "score": 80},
  {"criterion": "Technical Skills", "weight": 40, "score": 85},
  {"criterion": "Communication", "weight": 20, "score": 75},
  {"criterion": "Initiative", "weight": 15, "score": 90}
]
```
Total auto-calculated: `sum(weight * score / 100)`.

### Implementation Priority

| Feature | Priority | Status |
|---|---|---|
| Per-entry supervisor note (`supervisor_note`) | 🔴 High | ⏳ Planned |
| PKL report compilation (`CompileLogbookReportAction`) | 🟠 Medium | ⏳ Planned |
| Final rubric assessment (`IndustryAssessment`) | 🟠 Medium | ⏳ Planned |
| PDF export from logbook page | 🟡 Low | ⏳ Planned |
