# Incident Domain

## Purpose

Incident manages structured issue reporting — from initial report through investigation
to resolution and closure.

---

## Models

| Model | Key Fields |
|---|---|
| `IncidentReport` | registration_id, incident_date, type, severity, description, status |

## Actions

| Action | Type |
|---|---|
| `ReportIncidentAction` | Command |
| `UpdateIncidentAction` | Command |
| `ResolveIncidentAction` | Command |

## Where to Find It

- `app/Domain/Incident/Models/IncidentReport.php`
- `app/Domain/Incident/Actions/`
