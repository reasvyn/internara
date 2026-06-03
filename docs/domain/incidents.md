# Incident Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** — all 11 files in [reference](incident-reference.md) exist

## Purpose

Incident manages structured issue reporting — from initial report through investigation
to resolution and closure.

---

## Design Principles

### 1. Severity-Driven Escalation

Incident severity determines response urgency. Low and medium incidents follow standard
workflows. High and critical incidents trigger out-of-band notifications to all
administrators immediately upon reporting.

### 2. Immutable Timeline

Every action on an incident — report, investigation update, resolution, closure — is
recorded permanently with actor identity, timestamp, and action details. The timeline
is append-only and never edited.

### 3. Resolution Accountability

Every incident must reach a resolution outcome: confirmed with action taken, confirmed
with no action, unfounded, or referred. Incidents are never left in an unresolved state
indefinitely — the system enforces progression toward closure.

---

## Domain Boundary

The Incident domain owns structured issue reporting and resolution — a formal workflow for capturing, investigating, and closing incidents that occur during the placement program. Any user can file an incident report with the date and time of occurrence, location, detailed description, category classification, severity level (low, medium, high, or critical), and supporting evidence uploads. Each severity level triggers different escalation behaviors, with high and critical incidents sending out-of-band notifications to all administrators. Incidents progress through a defined investigation workflow from reported through investigating to resolved and finally closed, with each resolution assigned one of four outcomes: confirmed with action taken, confirmed with no action, unfounded, or referred. An immutable timeline records every action — timestamp, actor, action type, and details — creating a complete audit trail.

Incident does not own user identity data (User), program definitions (Internship), student activity records (Attendance, Logbook), or any other operational domain data. It owns the incident report, the investigation record, and the resolution outcome. It does not manage the events or behaviors that may cause incidents — those belong to their respective domains.

The domain depends on User for reporter identity and on Admin for investigation management and notification delivery. Incident records are self-contained — they do not reference program-specific data, though incidents may be about program events.

---

## Key Features

- File an incident report with date and time, location, description, category, severity classification, and supporting evidence uploads.
- Classify incidents by severity into low, medium, high, and critical levels with automated escalation behavior.
- Progress incidents through an investigation workflow from reported through investigating, resolved, and closed.
- Record every action in an immutable timeline with timestamp, actor identity, action type, and details.
- Assign resolution outcomes as confirmed with action taken, confirmed with no action, unfounded, or referred.
- Send out-of-band notifications to all administrators when a high or critical severity incident is reported.
- File an incident from a prominent report button accessible from any page, opening a structured multi-field form.
- Select incident severity from a dropdown with color coding to visually distinguish low, medium, high, and critical levels.
- Upload evidence files via drag and drop on the incident report form with file previews.
- Track the status of filed incidents in a personal list with status badges and last-updated timestamps.
- View an incident detail page with a chronological timeline of all actions taken during investigation.
