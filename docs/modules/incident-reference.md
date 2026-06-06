# Incident — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Incident** module.

---

## Overview

Tracks incident reports and workplace concerns requiring intervention

### Module Statistics
- **Actions**: 3 business logic operations
- **Models**: 1 data entities
- **Livewire Components**: 2 UI components
- **Policies**: 1 authorization rules
- **Submodules**: 1 module submodules

### Submodules
- `IncidentReport`

---

## Dependency Graph

This module depends on:
- **Core**
- **Enrollment**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `IncidentReport/Actions/ReportIncidentAction.php` | `ReportIncidentAction` | `BaseAction` |
| `IncidentReport/Actions/ResolveIncidentAction.php` | `ResolveIncidentAction` | `BaseAction` |
| `IncidentReport/Actions/UpdateIncidentAction.php` | `UpdateIncidentAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `IncidentReport/Models/IncidentReport.php` | `IncidentReport` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `IncidentReport/Livewire/IncidentForm.php` | `IncidentForm` | `Component` |
| `IncidentReport/Livewire/IncidentManager.php` | `IncidentManager` | `BaseRecordManager` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `IncidentReport/Policies/IncidentReportPolicy.php` | `IncidentReportPolicy` |

---

## File Organization

```
app/Incident/
├──            ← Submodule roots
│   └── {SubModule}/
│       ├── Actions/
│       ├── Models/
│       ├── Policies/
│       └── Livewire/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `IncidentReport`
- **Business Logic (`app/`)**: Located in [app/Incident/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Incident/)
- **Routing (`routes/`)**: [routes/web/incident.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/incident.php)
- **Views (`views/`)**: Blade templates and layouts are in [resources/views/incident/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/incident/)
- **Testing (`tests/`)**: Feature `tests/Feature/Incident/`, Unit `tests/Unit/Incident/`


*For overview and business context, see [incident.md](incident.md)*
