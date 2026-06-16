# Incident — Technical Reference

> Last updated: 2026-06-16

Detailed structural and implementation reference for the **Incident** module.

---

## Overview

Manages workplace incident reports, severity classification, and resolution tracking.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `IncidentReport/Actions/ReportIncidentAction.php` | `ReportIncidentAction` | `BaseAction` |
| `IncidentReport/Actions/UpdateIncidentAction.php` | `UpdateIncidentAction` | `BaseAction` |
| `IncidentReport/Actions/ResolveIncidentAction.php` | `ResolveIncidentAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `IncidentReport/Models/IncidentReport.php` | `IncidentReport` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `IncidentReport/Enums/IncidentSeverity.php` | `IncidentSeverity` | `LabelEnum` | low, medium, high, critical |
| `IncidentReport/Enums/IncidentStatus.php` | `IncidentStatus` | `LabelEnum`, `StatusEnum` | reported, investigating, resolved, closed |
| `IncidentReport/Enums/IncidentType.php` | `IncidentType` | `LabelEnum` | accident, harassment, safety, misconduct, other |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `IncidentReport/Policies/IncidentReportPolicy.php` | `IncidentReportPolicy` | `BasePolicy` |

---

## Notifications

| File | Notification |
| ---- | ------------ |
| `IncidentReport/Notifications/IncidentReportedNotification.php` | `IncidentReportedNotification` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `IncidentReport/Livewire/IncidentManager.php` | `IncidentManager` | `BaseRecordManager` |
| `IncidentReport/Livewire/IncidentForm.php` | `IncidentForm` | `Component` |

---

## Routes

File: `routes/web/incident.php`
Naming pattern: `incident.{resource}.{action}`

## Views

Views are located in `resources/views/incident/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Incident/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `IncidentReportFactory` | `IncidentReport` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_incident_reports_table` | `incident_reports` |

---


---

## Architectural Integration

- **Submodules**: `IncidentReport`
- **Business Logic**: `app/Incident/`
- **Routing**: `routes/web/incident.php`
- **Views**: `resources/views/incident/`
- **Testing**: `tests/Feature/Incident/`, `tests/Unit/Incident/`
- **Dependencies**: User, Program, Core

*For overview and business context, see [incident.md](incident.md).*
