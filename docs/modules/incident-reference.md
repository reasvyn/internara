# Incident — Technical Reference

> **Last updated:** 2026-07-31 **Changes:** sync — fix IncidentStatus/IncidentType, IncidentForm extends, route names, test paths

## Description

Detailed structural and implementation reference for the **Incident** module.

---

## Overview

Manages workplace incident reports, severity classification, and resolution tracking.

## Actions

| File                                               | Class                   | Extends             |
| -------------------------------------------------- | ----------------------- | ------------------- |
| `IncidentReport/Actions/ReportIncidentAction.php`  | `ReportIncidentAction`  | `BaseCommandAction` |
| `IncidentReport/Actions/UpdateIncidentAction.php`  | `UpdateIncidentAction`  | `BaseCommandAction` |
| `IncidentReport/Actions/ResolveIncidentAction.php` | `ResolveIncidentAction` | `BaseCommandAction` |

---

## Models

| File                                       | Class            | Extends     |
| ------------------------------------------ | ---------------- | ----------- |
| `IncidentReport/Models/IncidentReport.php` | `IncidentReport` | `BaseModel` |

---

## Enums

| File                                        | Enum               | Implements                | Values                                          |
| ------------------------------------------- | ------------------ | ------------------------- | ----------------------------------------------- |
| `IncidentReport/Enums/IncidentSeverity.php` | `IncidentSeverity` | `LabelEnum`               | low, medium, high, critical                     |
| `IncidentReport/Enums/IncidentStatus.php`   | `IncidentStatus`   | `StatusEnum`, `ColorableEnum` | reported, investigating, resolved, closed     |
| `IncidentReport/Enums/IncidentType.php`     | `IncidentType`     | `LabelEnum`               | accident, safety_violation, harassment, disciplinary, other |

---

## Policies

| File                                               | Policy                 | Extends      |
| -------------------------------------------------- | ---------------------- | ------------ |
| `IncidentReport/Policies/IncidentReportPolicy.php` | `IncidentReportPolicy` | `BasePolicy` |

---

## Notifications

| File                                                            | Notification                   |
| --------------------------------------------------------------- | ------------------------------ |
| `IncidentReport/Notifications/IncidentReportedNotification.php` | `IncidentReportedNotification` |

## Livewire Components

| File                                          | Component         | Extends             |
| --------------------------------------------- | ----------------- | ------------------- |
| `IncidentReport/Livewire/IncidentManager.php` | `IncidentManager` | `BaseRecordManager` |
| `IncidentReport/Livewire/IncidentForm.php`    | `IncidentForm`    | `BaseFormView`     |

---

## Routes

File: `routes/web/incident.php` Named routes: `student.incidents.report`, `sysadmin.incidents`

## Views

Views are located in `resources/views/incident/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/Incident/`. See [Testing](../infrastructure/testing.md)
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
- **Business Logic**: `app/Incident/`
- **Routing**: `routes/web/incident.php`
- **Views**: `resources/views/incident/`
- **Testing**: `tests/Incident/`
- **Dependencies**: User, Program, Core

_For overview and business context, see [incident.md](incident.md)._
