# Incident — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **Incident** module.

---

## Overview

Manages workplace incident reports, severity classification, and resolution tracking.

### Submodules

- `IncidentReport` — Incident documentation and management

---

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

---

## File Organization

```
app/Incident/
└── IncidentReport/
    ├── Actions/
    │   ├── ReportIncidentAction.php
    │   ├── ResolveIncidentAction.php
    │   └── UpdateIncidentAction.php
    ├── Enums/
    │   ├── IncidentSeverity.php
    │   ├── IncidentStatus.php
    │   └── IncidentType.php
    ├── Livewire/
    │   ├── IncidentForm.php
    │   └── IncidentManager.php
    ├── Models/IncidentReport.php
    ├── Notifications/IncidentReportedNotification.php
    └── Policies/IncidentReportPolicy.php
```

---

## Architectural Integration

- **Submodules**: `IncidentReport`
- **Business Logic**: `app/Incident/`
- **Routing**: `routes/web/incident.php`
- **Views**: `resources/views/incident/`
- **Testing**: `tests/Feature/Incident/`, `tests/Unit/Incident/`
- **Dependencies**: User, Program, Core

*For overview and business context, see [incident.md](incident.md).*
