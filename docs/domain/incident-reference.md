# Incident — API Reference
> Last updated: 2026-05-23
> Changes: fix: complete system initialization overhaul — security, middleware, recovery, form objects, docs


Total: 11 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Incident/Actions/ReportIncidentAction.php` | `ReportIncidentAction` | `BaseAction` | Reports a new incident with severity/type |
| `Incident/Actions/ResolveIncidentAction.php` | `ResolveIncidentAction` | `BaseAction` | Resolves an incident with resolution notes |
| `Incident/Actions/UpdateIncidentAction.php` | `UpdateIncidentAction` | `BaseAction` | Updates incident details |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Incident/Enums/IncidentSeverity.php` | `IncidentSeverity` | `LabelEnum` | Severity levels (low, medium, high, critical) |
| `Incident/Enums/IncidentStatus.php` | `IncidentStatus` | `StatusEnum` | Incident lifecycle status |
| `Incident/Enums/IncidentType.php` | `IncidentType` | `LabelEnum` | Incident type classification |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Incident/Livewire/IncidentForm.php` | `IncidentForm` | `Component` | Incident reporting form |
| `Incident/Livewire/IncidentManager.php` | `IncidentManager` | `BaseRecordManager` | CRUD manager for incident reports |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Incident/Models/IncidentReport.php` | `IncidentReport` | `BaseModel` | Eloquent model for incident reports |

## Notifications

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `Incident/Notifications/IncidentReportedNotification.php` | `IncidentReportedNotification` | `Notification` | Notification for reported incidents |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Incident/Policies/IncidentReportPolicy.php` | `IncidentReportPolicy` | `BasePolicy` | Authorization for incident operations |

## Where to Find It

- `app/Domain/Incident/Models/IncidentReport.php`
- `app/Domain/Incident/Actions/`
