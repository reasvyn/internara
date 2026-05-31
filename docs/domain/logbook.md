# Logbook Domain

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
