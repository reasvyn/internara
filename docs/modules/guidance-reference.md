# Guidance — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **Guidance** module.

---

## Overview

Manages mentor-student supervision logs, field supervision visits, and mentoring relationship coordination.

### Submodules

- `SupervisionLog` — Field supervision visit logs

---

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

---

## File Organization

```
app/Guidance/
└── SupervisionLog/
    ├── Actions/
    │   ├── CreateSupervisionLogAction.php
    │   └── VerifySupervisionLogAction.php
    ├── Entities/SupervisionStatus.php
    ├── Enums/
    │   ├── SupervisionLogStatus.php
    │   └── SupervisionType.php
    ├── Livewire/
    │   ├── SupervisionManager.php
    │   └── SupervisorLogManager.php
    ├── Models/SupervisionLog.php
    └── Policies/SupervisionLogPolicy.php
```

---

## Architectural Integration

- **Submodules**: `SupervisionLog`
- **Business Logic**: `app/Guidance/`
- **Routing**: `routes/web/guidance.php`
- **Views**: `resources/views/guidance/`
- **Testing**: `tests/Feature/Guidance/`, `tests/Unit/Guidance/`
- **Dependencies**: User, Program, Core

*For overview and business context, see [guidance.md](guidance.md).*
