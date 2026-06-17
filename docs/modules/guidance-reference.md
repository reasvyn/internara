# Guidance — Technical Reference

> **Last updated:** 2026-06-17
> **Changes:** sync — add Handbook and MonitoringVisit submodules; fix SupervisionStatus → SupervisionLogState entity; add missed SupervisionLog actions and Livewire components

Detailed structural and implementation reference for the **Guidance** module.

---

## Overview

Manages mentor-student supervision logs, field supervision visits, and mentoring relationship coordination.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `SupervisionLog/Actions/CreateSupervisionLogAction.php` | `CreateSupervisionLogAction` | `BaseCommandAction` |
| `SupervisionLog/Actions/DeleteLogAction.php` | `DeleteLogAction` | `BaseCommandAction` |
| `SupervisionLog/Actions/ReviewLogAction.php` | `ReviewLogAction` | `BaseCommandAction` |
| `SupervisionLog/Actions/VerifySupervisionLogAction.php` | `VerifySupervisionLogAction` | `BaseCommandAction` |
| `MonitoringVisit/Actions/CreateVisitAction.php` | `CreateVisitAction` | `BaseCommandAction` |
| `MonitoringVisit/Actions/VerifyVisitAction.php` | `VerifyVisitAction` | `BaseCommandAction` |
| `Handbook/Actions/CreateHandbookAction.php` | `CreateHandbookAction` | `BaseCommandAction` |
| `Handbook/Actions/UpdateHandbookAction.php` | `UpdateHandbookAction` | `BaseCommandAction` |
| `Handbook/Actions/DeleteHandbookAction.php` | `DeleteHandbookAction` | `BaseCommandAction` |
| `Handbook/Actions/AcknowledgeHandbookAction.php` | `AcknowledgeHandbookAction` | `BaseCommandAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `SupervisionLog/Models/SupervisionLog.php` | `SupervisionLog` | `BaseModel` |
| `MonitoringVisit/Models/MonitoringVisit.php` | `MonitoringVisit` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `SupervisionLog/Enums/SupervisionLogStatus.php` | `SupervisionLogStatus` | `LabelEnum`, `StatusEnum` | draft, submitted, verified |
| `SupervisionLog/Enums/SupervisionType.php` | `SupervisionType` | `LabelEnum` | onsite, remote, scheduled, emergency |
| `MonitoringVisit/Enums/VisitMethod.php` | `VisitMethod` | `LabelEnum` | onsite, remote |
| `Handbook/Enums/HandbookAudience.php` | `HandbookAudience` | `LabelEnum` | student, supervisor, teacher, all |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `SupervisionLog/Entities/SupervisionLogState.php` | `SupervisionLogState` | `BaseEntity` |
| `MonitoringVisit/Entities/VisitState.php` | `VisitState` | `BaseEntity` |
| `Handbook/Entities/HandbookEntity.php` | `HandbookEntity` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `SupervisionLog/Policies/SupervisionLogPolicy.php` | `SupervisionLogPolicy` | `BasePolicy` |
| `MonitoringVisit/Policies/MonitoringVisitPolicy.php` | `MonitoringVisitPolicy` | `BasePolicy` |

---

## Data / DTOs

| File | Class | Extends |
| ---- | ----- | ------- |
| `Handbook/Data/HandbookData.php` | `HandbookData` | `BaseData` |

## Events

| File | Class | Dispatched By |
| ---- | ----- | ------------- |
| `Handbook/Events/HandbookCreated.php` | `HandbookCreated` | `CreateHandbookAction` |
| `Handbook/Events/HandbookUpdated.php` | `HandbookUpdated` | `UpdateHandbookAction` |
| `Handbook/Events/HandbookDeleted.php` | `HandbookDeleted` | `DeleteHandbookAction` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `SupervisionLog/Livewire/SupervisionManager.php` | `SupervisionManager` | `BaseRecordManager` |
| `SupervisionLog/Livewire/SupervisorLogManager.php` | `SupervisorLogManager` | `Component` |
| `SupervisionLog/Livewire/StudentLogManager.php` | `StudentLogManager` | `Component` |
| `SupervisionLog/Livewire/SupervisorReviewManager.php` | `SupervisorReviewManager` | `Component` |
| `MonitoringVisit/Livewire/VisitManager.php` | `VisitManager` | `BaseRecordManager` |
| `MonitoringVisit/Livewire/StudentVisitList.php` | `StudentVisitList` | `Component` |
| `Handbook/Livewire/HandbookManager.php` | `HandbookManager` | `BaseRecordManager` |
| `Handbook/Livewire/StudentHandbookList.php` | `StudentHandbookList` | `Component` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Handbook/Livewire/Forms/HandbookForm.php` | `HandbookForm` |

---

## Routes

File: `routes/web/guidance.php`
Naming pattern: `guidance.{resource}.{action}`

## Views

Views are located in `resources/views/guidance/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Guidance/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `SupervisionLogFactory` | `SupervisionLog` |
| `MonitoringVisitFactory` | `MonitoringVisit` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_supervision_logs_table` | `supervision_logs` |
| `create_monitoring_visits_table` | `monitoring_visits` |

---


---

## Architectural Integration

- **Submodules**: `SupervisionLog`, `MonitoringVisit`, `Handbook`
- **Business Logic**: `app/Guidance/`
- **Routing**: `routes/web/guidance.php`
- **Views**: `resources/views/guidance/`
- **Testing**: `tests/Feature/Guidance/`, `tests/Unit/Guidance/`
- **Dependencies**: User, Program, Core

*For overview and business context, see [guidance.md](guidance.md).*
