# Incident

> **Last updated:** 2026-06-10

Structured incident reporting, severity classification, investigation workflow, resolution tracking, and escalation management.

## Purpose & Boundary

Incident provides a formal channel for reporting workplace issues during internships. Any authenticated user — student, teacher, supervisor, or admin — can submit an incident report. Incidents are classified by severity (LOW to CRITICAL), which determines routing and notification behavior. Reports progress through an investigation workflow (REPORTED → INVESTIGATING → RESOLVED → CLOSED) with an immutable timeline. Evidence files can be attached via Spatie Media Library.

Out of scope: daily complaints in logbooks (Journals), disciplinary actions (SysAdmin), general support tickets.

## Submodules

### IncidentReport
Core entity: date/time, location, description, category, severity, current status, resolution outcome, and evidence file attachments. Immutable after creation — reports cannot be deleted, only status-transitioned. Linked to the reporter (always recorded — no anonymous reports), optional affected student, and optional program.

## Key Concepts

### Severity Classification

Four severity levels determine routing and urgency:
- **LOW**: Minor concern, routed to assigned mentor.
- **MEDIUM**: Notable issue, routed to assigned supervisor.
- **HIGH**: Serious problem, triggers out-of-band notifications to all admins.
- **CRITICAL**: Immediate danger, triggers immediate email + in-app notifications to all superadmin and admin users.

Severity is set by the reporter. Admins can escalate severity during investigation.

### Investigation Workflow

Incidents follow a mandatory state progression: `REPORTED` → `INVESTIGATING` → `RESOLVED` → `CLOSED`. Each transition requires an authorized actor (admin for INVESTIGATING, assigned investigator for RESOLVED). Transitions cannot skip steps — REPORTED must go to INVESTIGATING before RESOLVED. Each transition is recorded in an immutable timeline with timestamp, actor, action type, and details.

### Resolution Outcomes

When transitioning to RESOLVED, the investigator must select one of four outcomes:
- `CONFIRMED_ACTION_TAKEN` — Issue confirmed and corrective action applied.
- `CONFIRMED_NO_ACTION` — Issue confirmed but no action needed.
- `UNFOUNDED` — Report could not be substantiated.
- `REFERRED` — Issue referred to external authority.

### Immutable Timeline

Every state transition, comment, and status change is recorded as an immutable timeline entry. Entries cannot be modified or deleted. The complete incident history is always available for audit.

## Dependencies

- Core (base classes, SmartLogger)
- Program (program context)
- Enrollment (optional student context)
- User (reporter identity)

## Used By

- SysAdmin (escalation handling, pulse monitoring)
- Evaluation (incident data may influence program quality evaluation)
