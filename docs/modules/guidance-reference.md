# Guidance — Technical Reference

> Last updated: 2026-06-06  
> Changes: Removed references to the eliminated mentor, mentee, and handbook models and tables.

Detailed structural and implementation reference for the **Guidance** module.

---

## Overview

Manages teacher field supervision logs and mentoring relations coordinates.

### Module Statistics

- **Actions**: 4 business logic operations
- **Models**: 1 data entity (`SupervisionLog`)
- **Livewire Components**: 2 UI components
- **Policies**: 1 authorization rules
- **Submodules**: 1 module submodules

### Submodules

- **SupervisionLog**: Private field visitation, virtual meeting, or phone log tracking.

---

## Dependency Graph

This module depends on:

- **Core** (base classes)
- **Enrollment** (registration records)
- **User** (students, teachers, and profiles)

---

## Actions

| File                                                    | Class                        | Extends      |
| ------------------------------------------------------- | ---------------------------- | ------------ |
| `SupervisionLog/Actions/CreateSupervisionLogAction.php` | `CreateSupervisionLogAction` | `BaseAction` |
| `SupervisionLog/Actions/VerifySupervisionLogAction.php` | `VerifySupervisionLogAction` | `BaseAction` |
| `Actions/AssignMentorAction.php`                        | `AssignMentorAction`         | `BaseAction` |
| `Actions/RemoveMentorAction.php`                        | `RemoveMentorAction`         | `BaseAction` |

---

## Models

| File                                       | Class            |
| ------------------------------------------ | ---------------- |
| `SupervisionLog/Models/SupervisionLog.php` | `SupervisionLog` |

---

## Livewire Components

| File                                                | Component               | Extends             |
| --------------------------------------------------- | ----------------------- | ------------------- |
| `SupervisionLog/Livewire/SupervisionLogManager.php` | `SupervisionLogManager` | `BaseRecordManager` |
| `Livewire/MenteeViewer.php`                         | `MenteeViewer`          | `Component`         |

---

## Authorization Policies

| File                                               | Policy                 |
| -------------------------------------------------- | ---------------------- | ------------ |
| `SupervisionLog/Policies/SupervisionLogPolicy.php` | `SupervisionLogPolicy` | `BasePolicy` |

---

## File Organization

```
app/Guidance/
├── SupervisionLog/          ← Submodule root
│   ├── Actions/
│   ├── Models/
│   ├── Policies/
│   └── Livewire/
├── Actions/                  ← Cross-submodule relations actions
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `SupervisionLog`
- **Business Logic (`app/`)**: Located in
  [app/Guidance/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Guidance/)
- **Routing (`routes/`)**:
  [routes/web/guidance.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/guidance.php)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/guidance/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/guidance/)
- **Testing (`tests/`)**: Feature `tests/Feature/Guidance/`, Unit `tests/Unit/Guidance/`

_For overview and business context, see [guidance.md](guidance.md)_
