# Program — Technical Reference

> Last updated: 2026-06-06  
> Changes: Removed references to the separate document requirements and internship phase tables.

Detailed structural and implementation reference for the **Program** module.

---

## Overview

Manages internship programs and cohort student groupings.

### Module Statistics

- **Actions**: 13 business logic operations
- **Models**: 4 data entities (`Internship`, `InternshipGroup`, `InternshipGroupMember`, `InternshipPhase`)
- **Livewire Components**: 3 UI components
- **Policies**: 3 authorization rules
- **Submodules**: 3 module submodules

### Submodules

- `Internship`
- `InternshipGroup`
- `InternshipPhase`

---

## Dependency Graph

This module depends on:

- **Academics** (calendar years)
- **Core** (base classes)
- **Enrollment** (student placements)
- **User** (students and mentors)

---

## Actions

| File                                                       | Class                               | Extends      |
| ---------------------------------------------------------- | ----------------------------------- | ------------ |
| `Internship/Actions/CreateInternshipAction.php`            | `CreateInternshipAction`            | `BaseAction` |
| `Internship/Actions/UpdateInternshipAction.php`            | `UpdateInternshipAction`            | `BaseAction` |
| `Internship/Actions/DeleteInternshipAction.php`            | `DeleteInternshipAction`            | `BaseAction` |
| `Internship/Actions/BatchUpdateInternshipStatusAction.php` | `BatchUpdateInternshipStatusAction` | `BaseAction` |
| `Internship/Actions/CheckCloseReadinessAction.php`         | `CheckCloseReadinessAction`         | `BaseAction` |
| `InternshipGroup/Actions/CreateInternshipGroupAction.php`  | `CreateInternshipGroupAction`       | `BaseAction` |
| `InternshipGroup/Actions/UpdateInternshipGroupAction.php`  | `UpdateInternshipGroupAction`       | `BaseAction` |
| `InternshipGroup/Actions/DeleteInternshipGroupAction.php`  | `DeleteInternshipGroupAction`       | `BaseAction` |
| `InternshipGroup/Actions/AddMemberToGroupAction.php`       | `AddMemberToGroupAction`            | `BaseAction` |
| `InternshipGroup/Actions/RemoveMemberFromGroupAction.php`  | `RemoveMemberFromGroupAction`  | `BaseAction` |
| `InternshipPhase/Actions/CreateInternshipPhaseAction.php`  | `CreateInternshipPhaseAction`  | `BaseAction` |
| `InternshipPhase/Actions/UpdateInternshipPhaseAction.php`  | `UpdateInternshipPhaseAction`  | `BaseAction` |
| `InternshipPhase/Actions/DeleteInternshipPhaseAction.php`  | `DeleteInternshipPhaseAction`  | `BaseAction` |

---

## Models

| File                                               | Class                   |
| -------------------------------------------------- | ----------------------- |
| `Internship/Models/Internship.php`                 | `Internship`            |
| `InternshipGroup/Models/InternshipGroup.php`       | `InternshipGroup`       |
| `InternshipGroup/Models/InternshipGroupMember.php` | `InternshipGroupMember` |
| `InternshipPhase/Models/InternshipPhase.php`       | `InternshipPhase`       |

---

## Livewire Components

| File                                                  | Component                | Extends             |
| ----------------------------------------------------- | ------------------------ | ------------------- |
| `Internship/Livewire/InternshipManager.php`           | `InternshipManager`      | `BaseRecordManager` |
| `InternshipGroup/Livewire/InternshipGroupManager.php` | `InternshipGroupManager` | `BaseRecordManager` |
| `InternshipPhase/Livewire/InternshipPhaseManager.php` | `InternshipPhaseManager` | `BaseRecordManager` |

---

## Authorization Policies

| File                                                 | Policy                  |
| ---------------------------------------------------- | ----------------------- | ------------ |
| `Internship/Policies/InternshipPolicy.php`           | `InternshipPolicy`      | `BasePolicy` |
| `InternshipGroup/Policies/InternshipGroupPolicy.php` | `InternshipGroupPolicy` | `BasePolicy` |
| `InternshipPhase/Policies/InternshipPhasePolicy.php` | `InternshipPhasePolicy` | `BasePolicy` |

---

## File Organization

```
app/Program/
├──            ← Submodule roots
│   ├── Internship/
│   │   ├── Actions/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Livewire/
│   ├── InternshipGroup/
│   │   ├── Actions/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Livewire/
│   └── InternshipPhase/
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

- **Submodules**: `Internship`, `InternshipGroup`, `InternshipPhase`
- **Business Logic (`app/`)**: Located in
  [app/Program/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Program/)
- **Routing (`routes/`)**:
  [routes/web/program.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/program.php)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/program/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/program/)
- **Testing (`tests/`)**: Feature `tests/Feature/Program/`, Unit `tests/Unit/Program/`

_For overview and business context, see [program.md](program.md)_
