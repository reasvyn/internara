# Incident Reporting («Pelaporan Insiden»)

**Event:** Reporting and managing workplace incidents involving students during the internship.

**Phase:** 4 — Operations

**Previous Event:** [Student Registration](student-registration.md)

**Next Event:** (continues operations; incident is a parallel event)

---

## Overview

Workplace incidents — accidents, safety violations, harassment, disciplinary issues — can occur during internships. The incident reporting system allows students, teachers, and supervisors to report incidents and track their resolution.

Incident reporting is a **non-blocking, informational** feature. An open incident does **not** halt any operational activity (logbook, attendance, etc.) unless the admin explicitly suspends the student's registration.

## Trigger

- Accident or injury at the workplace
- Safety violation observed
- Harassment or discrimination complaint
- Disciplinary issue (student or company-side)
- Any other workplace concern

## Pre-conditions

- Student has an active registration
- User is logged in (any role can report)

## Actors

| Actor | Role | Can report | Can resolve | Can close |
|---|---|---|---|---|
| Student | STUDENT | Yes (own) | No | No |
| Teacher | TEACHER | Yes (own students) | Yes | Yes |
| Admin | ADMIN, SUPER_ADMIN | Yes | Yes | Yes |
| Supervisor | SUPERVISOR | Yes (assigned students) | No | No |

---

## Event A: Reporting an Incident

### Flow

```
Student → Report Incident → Fill Details → Submit
```

Navigate to **Student → Report Incident**.

| Field | Validation | Description |
|---|---|---|
| **Internship Registration** | Required | Which internship |
| **Incident Date** | Required | When it occurred |
| **Type** | Required | `accident`, `safety_violation`, `harassment`, `disciplinary`, `other` |
| **Severity** | Required | `low`, `medium`, `high`, `critical` |
| **Location** | Optional | Where it happened |
| **Description** | Required, min 20 chars | Detailed account |
| **Action Taken** | Optional | Immediate response |

`ReportIncidentAction` (`app/Actions/Incident/ReportIncidentAction.php`) creates the report with status `REPORTED`.

---

## Event B: Investigating and Resolving

### Flow

```
Admin → Incidents → Select → Resolve → Add Notes → Save
```

Navigate to **Admin → Incidents**.

### Resolve

`ResolveIncidentAction` (`app/Actions/Incident/ResolveIncidentAction.php`):
1. Validates incident is not already in a terminal state
2. Sets status to `RESOLVED` or `CLOSED`
3. Records `resolved_at`, `resolved_by`, and `resolution_notes`

### Status Lifecycle

```
REPORTED ──► INVESTIGATING ──► RESOLVED ──► CLOSED (terminal)
```

Defined in `App\Enums\Incident\IncidentStatus`.

---

## Severity & Notification

| Severity | Notification |
|---|---|
| `LOW` | None |
| `MEDIUM` | Email to assigned teacher |
| `HIGH` | Instant notification to all admins + teacher |
| `CRITICAL` | Instant notification to all admins + teacher |

> Note: Notification automation is not yet implemented — incident reporting is currently limited to database logging.

---

## Non-Blocking Principle

An open incident **never blocks** operational activities. If the admin determines that the student should pause their internship, the **account status** or **registration status** should be changed separately.

---

## Models

| Model | Table |
|---|---|
| `App\Models\IncidentReport` | `incident_reports` |

## Enums

| Enum | Cases |
|---|---|
| `App\Enums\Incident\IncidentType` | ACCIDENT, SAFETY_VIOLATION, HARASSMENT, DISCIPLINARY, OTHER |
| `App\Enums\Incident\IncidentSeverity` | LOW, MEDIUM, HIGH, CRITICAL |
| `App\Enums\Incident\IncidentStatus` | REPORTED, INVESTIGATING, RESOLVED, CLOSED |

## Actions

| Action | Purpose |
|---|---|
| `ReportIncidentAction` | Creates a new incident report |
| `UpdateIncidentAction` | Updates incident details or status |
| `ResolveIncidentAction` | Resolves or closes an incident |

## Livewire Components

| Component | Route | View |
|---|---|---|
| `App\Livewire\Incident\IncidentManager` | `admin/incidents` (name: `admin.incidents`) | `livewire.incident.incident-manager` |
| `App\Livewire\Incident\IncidentForm` | `student/incidents/report` (name: `student.incidents.report`) | `livewire.incident.incident-form` |

## Key Rules

| Rule | Enforcement |
|---|---|
| **Anyone can report** | No role restriction on creation |
| **Non-blocking** | Incidents do not gate any operational feature |
| **Severity-based notification** | Enums exist; notification implementation pending |
| **Status is forward-only** | No re-opening after CLOSED |
| **Only teacher/admin can resolve** | Student/supervisor cannot resolve |
| **No supervisor dependency** | Teacher/admin handles investigation and resolution |
