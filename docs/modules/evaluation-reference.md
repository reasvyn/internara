# Evaluation — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Evaluation** module.

---

## Overview

Manages supervisor and teacher evaluations of students

### Module Statistics
- **Actions**: 3 business logic operations
- **Models**: 1 data entities
- **Livewire Components**: 1 UI components
- **Policies**: 1 authorization rules
- **Submodules**: 1 module submodules

### Submodules
- `Evaluation`

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
| `Evaluation/Actions/DeleteEvaluationAction.php` | `DeleteEvaluationAction` | `BaseAction` |
| `Evaluation/Actions/EvaluateMentorAction.php` | `EvaluateMentorAction` | `BaseAction` |
| `Evaluation/Actions/SubmitEvaluationAction.php` | `SubmitEvaluationAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Evaluation/Models/Evaluation.php` | `Evaluation` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Evaluation/Livewire/MentorEvaluationManager.php` | `MentorEvaluationManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Evaluation/Policies/EvaluationPolicy.php` | `EvaluationPolicy` |

---

## File Organization

```
app/Evaluation/
├──            ← Submodule roots
│   └── {SubModule}/
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

*For overview and business context, see [evaluation.md](evaluation.md)*
