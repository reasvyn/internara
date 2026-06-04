# Incident — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Incident** domain.

---

## Overview

Tracks incident reports and workplace concerns requiring intervention

### Domain Statistics
- **Actions**: 3 business logic operations
- **Models**: 1 data entities
- **Livewire Components**: 2 UI components
- **Policies**: 1 authorization rules
- **Aggregates**: 1 domain aggregates

### Aggregates
- `IncidentReport`

---

## Dependency Graph

This domain depends on:
- **Core**
- **Enrollment**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/IncidentReport/Actions/ReportIncidentAction.php` | `ReportIncidentAction` | `BaseAction` |
| `Aggregates/IncidentReport/Actions/ResolveIncidentAction.php` | `ResolveIncidentAction` | `BaseAction` |
| `Aggregates/IncidentReport/Actions/UpdateIncidentAction.php` | `UpdateIncidentAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/IncidentReport/Models/IncidentReport.php` | `IncidentReport` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/IncidentReport/Livewire/IncidentForm.php` | `IncidentForm` | `Component` |
| `Aggregates/IncidentReport/Livewire/IncidentManager.php` | `IncidentManager` | `BaseRecordManager` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/IncidentReport/Policies/IncidentReportPolicy.php` | `IncidentReportPolicy` |

---

## File Organization

```
app/Domain/Incident/
├── Aggregates/           ← Aggregate roots
│   └── {Aggregate}/
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

*For overview and business context, see [incident.md](incident.md)*
