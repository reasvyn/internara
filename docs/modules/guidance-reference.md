# Guidance — Technical Reference

> **Last updated:** 2026-07-21 **Changes:** sync — move SupervisionLog to Journals; Guidance now only contains MonitoringVisit

## Description

Detailed structural and implementation reference for the **Guidance** module.

---

## Overview

Manages field monitoring visit scheduling, verification, and tracking for internship programs.

## Actions

| File                                                | Class                | Extends             |
| --------------------------------------------------- | -------------------- | ------------------- |
| `MonitoringVisit/Actions/CreateVisitAction.php`     | `CreateVisitAction`  | `BaseCommandAction` |
| `MonitoringVisit/Actions/VerifyVisitAction.php`     | `VerifyVisitAction`  | `BaseCommandAction` |

---

## Models

| File                                         | Class             | Extends     |
| -------------------------------------------- | ----------------- | ----------- |
| `MonitoringVisit/Models/MonitoringVisit.php` | `MonitoringVisit` | `BaseModel` |

---

## Enums

| File                                    | Enum       | Implements | Values                               |
| --------------------------------------- | ---------- | ---------- | ------------------------------------ |
| `MonitoringVisit/Enums/VisitMethod.php` | `VisitMethod` | `LabelEnum` | onsite, remote                       |

---

## Entities

| File                                  | Class        | Extends      |
| ------------------------------------- | ------------ | ------------ |
| `MonitoringVisit/Entities/VisitState.php` | `VisitState` | `BaseEntity` |

---

## Policies

| File                                                 | Policy                  | Extends      |
| ---------------------------------------------------- | ----------------------- | ------------ |
| `MonitoringVisit/Policies/MonitoringVisitPolicy.php` | `MonitoringVisitPolicy` | `BasePolicy` |

---

## Data / DTOs

_No DTOs for this module._

## Events

_No events for this module._

## Livewire Components

| File                                              | Component         | Extends             |
| ------------------------------------------------- | ----------------- | ------------------- |
| `MonitoringVisit/Livewire/VisitManager.php`       | `VisitManager`    | `BaseRecordManager` |
| `MonitoringVisit/Livewire/StudentVisitList.php`   | `StudentVisitList`| `Component`         |

## Livewire Forms

_No Livewire forms for this module._

---

## Routes

File: `routes/web/guidance.php` Naming pattern: `guidance.{resource}.{action}`

## Views

Views are located in `resources/views/guidance/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/Guidance/`. See [Testing](../infrastructure/testing.md)
for the testing conventions.

## Factories

| Factory                  | Model             |
| ------------------------ | ----------------- |
| `MonitoringVisitFactory` | `MonitoringVisit` |

## Migrations

| Migration                        | Table               |
| -------------------------------- | ------------------- |
| `create_monitoring_visits_table` | `monitoring_visits` |

---

## Architectural Integration

- **Submodules**: `MonitoringVisit`
- **Business Logic**: `app/Guidance/`
- **Routing**: `routes/web/guidance.php`
- **Views**: `resources/views/guidance/`
- **Testing**: `tests/Guidance/`
- **Dependencies**: User, Program, Core

_For overview and business context, see [guidance.md](guidance.md)._
