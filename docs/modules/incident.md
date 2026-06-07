# Incident — Documentation Overview

> Last updated: 2026-06-04 Changes: Rewrote overview with developer-friendly content, added error
> handling, failure modes, and CLI commands

Structured incident reporting, investigation, and resolution workflow.

For complete technical reference including API, models, actions, and components, see
[incident-reference.md](incident-reference.md).

---

## Key Principles

- **Any user can report** — students, teachers, supervisors, and admins can submit an incident
  report. Reports are not anonymous (actor is recorded).
- **Severity drives routing** — LOW/MEDIUM incidents are routed to the assigned mentor or
  supervisor. HIGH/CRITICAL incidents trigger out-of-band notifications to all admins.
- **Investigation is tracked** — incidents progress through REPORTED → INVESTIGATING → RESOLVED →
  CLOSED. Each action is recorded in an immutable timeline.
- **Evidence can be attached** — photos, documents, and other files can be attached as media via
  Spatie Media Library.

---

## Context Boundary

Incidents are linked to a program and optionally to a specific student or placement. SysAdmin
escalation path for HIGH/CRITICAL incidents. Journals may reference incidents in logbook entries.

---

## Module Rules

- **Severity classification**: LOW (minor concern), MEDIUM (notable issue), HIGH (serious problem),
  CRITICAL (immediate danger). Severity is set by the reporter and can be escalated by admin.
- **Investigation workflow**: REPORTED (initial) → INVESTIGATING (admin assigned) → RESOLVED (action
  taken) → CLOSED (final). Only CLOSED is terminal.
- **Resolution outcomes**: CONFIRMED_ACTION_TAKEN, CONFIRMED_NO_ACTION, UNFOUNDED, REFERRED. Must be
  selected when transitioning to RESOLVED.
- **Immutable timeline**: every state transition, comment, and action is recorded with timestamp,
  actor, action type, and details. Cannot be modified or deleted.
- **CRITICAL notifications**: HIGH/CRITICAL severity triggers immediate out-of-band notifications
  (email + in-app) to all superadmin and admin users.
- **Reports cannot be deleted** — once submitted, an incident report is permanent. Only status
  transitions are allowed.

---

## Submodules

- **IncidentReport**: The core entity — date/time, location, description, category, severity,
  status, resolution outcome, evidence files. Immutable after creation. Linked to a reporter,
  optional student, and optional program.

---

## CLI Commands

| Command                               | Purpose                                                                 |
| ------------------------------------- | ----------------------------------------------------------------------- |
| `php artisan incident:escalate-stale` | Escalate incidents stuck in INVESTIGATING beyond configurable threshold |

---

## Error Handling & Failure Modes

- **Deleting an incident report**: The system blocks with a `RejectedException` — submitted reports
  are immutable. The UI hides the delete button.
- **Skipping investigation step**: Attempting to transition directly from REPORTED to RESOLVED is
  blocked. INVESTIGATING is required.
- **Missing resolution outcome**: Transitioning to RESOLVED without selecting an outcome throws a
  `ValidationFailedException`.
- **Escalation delay**: If no admin action is taken within the configurable threshold, the
  `incident:escalate-stale` command alerts the next escalation level.

---

## Quick References

### Actions & Business Logic

- **3** actions across all submodules
- Incident report submission, investigation status transitions, resolution finalization

### Data & Persistence

- **1** model: `IncidentReport`
- UUID PK, `HasFactory`, media library attachments for evidence. StatusEnum for severity and
  workflow state

### User Interface

- **2** Livewire components
- Incident report form, incident manager (table with severity filters, status timeline view)

### Authorization

- **1** policy
- Any authenticated user can create reports. Admins manage investigation workflow

---

For complete technical reference, see [incident-reference.md](incident-reference.md).
