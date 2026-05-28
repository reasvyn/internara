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

## Models

| Model | Key Fields |
|---|---|
| `Logbook` | user_id, registration_id, date, content, status, is_verified |

## Actions

| Action | Type |
|---|---|
| `CreateLogbookAction` | Command |
| `UpdateLogbookAction` | Command |
| `SubmitLogbookAction` | Command |
| `DeleteLogbookAction` | Command |

## Where to Find It

- `app/Domain/Logbook/Models/Logbook.php`
- `app/Domain/Logbook/Actions/`
