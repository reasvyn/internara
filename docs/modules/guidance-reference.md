# Guidance — Technical Reference

> Last updated: 2026-06-16

Detailed structural and implementation reference for the **Guidance** module.

---

## Overview

Manages mentor-student supervision logs, field supervision visits, and mentoring relationship coordination.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `SupervisionLog/Actions/CreateSupervisionLogAction.php` | `CreateSupervisionLogAction` | `BaseAction` |
| `SupervisionLog/Actions/VerifySupervisionLogAction.php` | `VerifySupervisionLogAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `SupervisionLog/Models/SupervisionLog.php` | `SupervisionLog` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `SupervisionLog/Enums/SupervisionLogStatus.php` | `SupervisionLogStatus` | `LabelEnum`, `StatusEnum` | draft, submitted, verified |
| `SupervisionLog/Enums/SupervisionType.php` | `SupervisionType` | `LabelEnum` | onsite, remote, scheduled, emergency |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `SupervisionLog/Entities/SupervisionStatus.php` | `SupervisionStatus` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `SupervisionLog/Policies/SupervisionLogPolicy.php` | `SupervisionLogPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `SupervisionLog/Livewire/SupervisionManager.php` | `SupervisionManager` | `BaseRecordManager` |
| `SupervisionLog/Livewire/SupervisorLogManager.php` | `SupervisorLogManager` | `Component` |

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

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_supervision_logs_table` | `supervision_logs` |

---


---

## Architectural Integration

- **Submodules**: `SupervisionLog`
- **Business Logic**: `app/Guidance/`
- **Routing**: `routes/web/guidance.php`
- **Views**: `resources/views/guidance/`
- **Testing**: `tests/Feature/Guidance/`, `tests/Unit/Guidance/`
- **Dependencies**: User, Program, Core

*For overview and business context, see [guidance.md](guidance.md).*
