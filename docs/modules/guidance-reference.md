# Guidance — Technical Reference

> **Last updated:** 2026-07-21 **Changes:** sync — remove Handbook submodule (moved back to Document); update submodule list

## Description

Detailed structural and implementation reference for the **Guidance** module.

---

## Overview

Manages mentor-student supervision logs, field supervision visits, and mentoring relationship
coordination.

## Actions

| File                                                    | Class                        | Extends             |
| ------------------------------------------------------- | ---------------------------- | ------------------- |
| `SupervisionLog/Actions/CreateSupervisionLogAction.php` | `CreateSupervisionLogAction` | `BaseCommandAction` |
| `SupervisionLog/Actions/CreateLogAction.php`            | `CreateLogAction`            | `BaseCommandAction` |
| `SupervisionLog/Actions/DeleteLogAction.php`            | `DeleteLogAction`            | `BaseCommandAction` |
| `SupervisionLog/Actions/ReviewLogAction.php`            | `ReviewLogAction`            | `BaseCommandAction` |
| `SupervisionLog/Actions/VerifySupervisionLogAction.php` | `VerifySupervisionLogAction` | `BaseCommandAction` |
| `MonitoringVisit/Actions/CreateVisitAction.php`         | `CreateVisitAction`          | `BaseCommandAction` |
| `MonitoringVisit/Actions/VerifyVisitAction.php`         | `VerifyVisitAction`          | `BaseCommandAction` |

---

## Models

| File                                         | Class             | Extends     |
| -------------------------------------------- | ----------------- | ----------- |
| `SupervisionLog/Models/SupervisionLog.php`   | `SupervisionLog`  | `BaseModel` |
| `MonitoringVisit/Models/MonitoringVisit.php` | `MonitoringVisit` | `BaseModel` |

---

## Enums

| File                                            | Enum                   | Implements                | Values                               |
| ----------------------------------------------- | ---------------------- | ------------------------- | ------------------------------------ |
| `SupervisionLog/Enums/SupervisionLogStatus.php` | `SupervisionLogStatus` | `LabelEnum`, `StatusEnum` | draft, submitted, verified           |
| `SupervisionLog/Enums/SupervisionType.php`      | `SupervisionType`      | `LabelEnum`               | onsite, remote, scheduled, emergency |
| `MonitoringVisit/Enums/VisitMethod.php`         | `VisitMethod`          | `LabelEnum`               | onsite, remote                       |

---

## Entities

| File                                              | Class                 | Extends      |
| ------------------------------------------------- | --------------------- | ------------ |
| `SupervisionLog/Entities/SupervisionLogState.php` | `SupervisionLogState` | `BaseEntity` |
| `MonitoringVisit/Entities/VisitState.php`         | `VisitState`          | `BaseEntity` |

---

## Policies

| File                                                 | Policy                  | Extends      |
| ---------------------------------------------------- | ----------------------- | ------------ |
| `SupervisionLog/Policies/SupervisionLogPolicy.php`   | `SupervisionLogPolicy`  | `BasePolicy` |
| `MonitoringVisit/Policies/MonitoringVisitPolicy.php` | `MonitoringVisitPolicy` | `BasePolicy` |

---

## Data / DTOs

_No DTOs for this module._

## Events

_No events for this module._

## Livewire Components

| File                                                  | Component                 | Extends             |
| ----------------------------------------------------- | ------------------------- | ------------------- |
| `SupervisionLog/Livewire/SupervisionManager.php`      | `SupervisionManager`      | `BaseRecordManager` |
| `SupervisionLog/Livewire/SupervisorLogManager.php`    | `SupervisorLogManager`    | `Component`         |
| `SupervisionLog/Livewire/StudentLogManager.php`       | `StudentLogManager`       | `Component`         |
| `SupervisionLog/Livewire/SupervisorReviewManager.php` | `SupervisorReviewManager` | `Component`         |
| `MonitoringVisit/Livewire/VisitManager.php`           | `VisitManager`            | `BaseRecordManager` |
| `MonitoringVisit/Livewire/StudentVisitList.php`       | `StudentVisitList`        | `Component`         |

## Livewire Forms

_No Livewire forms for this module._

---

## Routes

File: `routes/web/guidance.php` Naming pattern: `guidance.{resource}.{action}`

## Views

Views are located in `resources/views/guidance/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/{Feature,Unit}/Guidance/`. See [Testing](../infrastructure/testing.md)
for the testing conventions.

## Factories

| Factory                  | Model             |
| ------------------------ | ----------------- |
| `SupervisionLogFactory`  | `SupervisionLog`  |
| `MonitoringVisitFactory` | `MonitoringVisit` |

## Migrations

| Migration                        | Table               |
| -------------------------------- | ------------------- |
| `create_supervision_logs_table`  | `supervision_logs`  |
| `create_monitoring_visits_table` | `monitoring_visits` |

---

## Architectural Integration

- **Submodules**: `SupervisionLog`, `MonitoringVisit`
- **Business Logic**: `app/Guidance/`
- **Routing**: `routes/web/guidance.php`
- **Views**: `resources/views/guidance/`
- **Testing**: `tests/Guidance/`, `tests/Guidance/`
- **Dependencies**: User, Program, Core

_For overview and business context, see [guidance.md](guidance.md)._
