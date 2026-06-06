# Evaluation — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Evaluation** module.

---

## Overview

Manages supervisor and teacher evaluations of students

### Module Statistics
- **Actions**: 3 business logic operations
- **Models**: 1 data entity
- **Livewire Components**: 1 UI component
- **Policies**: 1 authorization rule
- **Submodules**: None (flat)

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
| `Actions/DeleteEvaluationAction.php` | `DeleteEvaluationAction` | `BaseAction` |
| `Actions/EvaluateMentorAction.php` | `EvaluateMentorAction` | `BaseAction` |
| `Actions/SubmitEvaluationAction.php` | `SubmitEvaluationAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Models/Evaluation.php` | `Evaluation` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Livewire/MentorEvaluationManager.php` | `MentorEvaluationManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Policies/EvaluationPolicy.php` | `EvaluationPolicy` |

---

## File Organization

```
app/Evaluation/
├── Actions/
├── Entities/
├── Enums/
├── Livewire/
├── Models/
├── Policies/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Evaluation`
- **Business Logic (`app/`)**: Located in [app/Evaluation/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Evaluation/)
- **Routing (`routes/`)**: [routes/web/evaluation.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/evaluation.php)
- **Views (`views/`)**: Blade templates and layouts are in [resources/views/evaluation/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/evaluation/)
- **Testing (`tests/`)**: Feature `tests/Feature/Evaluation/`, Unit `tests/Unit/Evaluation/`


*For overview and business context, see [evaluation.md](evaluation.md)*
