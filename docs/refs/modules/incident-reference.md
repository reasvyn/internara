# Incident — Technical Reference

## Description

Detailed structural and implementation reference for the **Incident** module.

---

## Overview

Manages workplace incident reports, severity classification, and resolution tracking.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/IncidentReport/Actions/ReportIncidentAction.php` | `ReportIncidentAction` | `BaseCommandAction` |
| `Domain/IncidentReport/Actions/ResolveIncidentAction.php` | `ResolveIncidentAction` | `BaseCommandAction` |
| `Domain/IncidentReport/Actions/UpdateIncidentAction.php` | `UpdateIncidentAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/IncidentReport/Models/IncidentReport.php` | `IncidentReport` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/IncidentReport/Enums/IncidentSeverity.php` | `IncidentSeverity` | `LabelEnum` | — |
| `Domain/IncidentReport/Enums/IncidentStatus.php` | `IncidentStatus` | `LabelEnum` | — |
| `Domain/IncidentReport/Enums/IncidentType.php` | `IncidentType` | `LabelEnum` | — |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/IncidentReport/Policies/IncidentReportPolicy.php` | `IncidentReportPolicy` | `BasePolicy` |

## Notifications

| File                                                            | Notification                   |
| --------------------------------------------------------------- | ------------------------------ |
| `IncidentReport/Notifications/IncidentReportedNotification.php` | `IncidentReportedNotification` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/IncidentReport/Livewire/IncidentForm.php` | `IncidentForm` | `BaseFormView` |
| `Domain/IncidentReport/Livewire/IncidentManager.php` | `IncidentManager` | `BaseRecordManager` |

## Routes

File: `routes/web/incident.php` Named routes: `student.incidents.report`, `sysadmin.incidents`

## Views

Views are located in `resources/views/incident/`. See [UI/UX](../../guides/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/Incident/`. See [Testing](../../guides/infra/testing.md)
for the testing conventions.

## Factories

| Factory                 | Model            |
| ----------------------- | ---------------- |
| `IncidentReportFactory` | `IncidentReport` |

## Migrations

| Migration                       | Table              |
| ------------------------------- | ------------------ |
| `create_incident_reports_table` | `incident_reports` |

---

## Architectural Integration

- **Submodules**: `IncidentReport`
- **Business Logic**: `app/Modules/Incident/`
- **Routing**: `routes/web/incident.php`
- **Views**: `resources/views/incident/`
- **Testing**: `tests/Incident/`
- **Dependencies**: User, Program, Core

_For overview and business context, see [incident.md](incident.md)._
