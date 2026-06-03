# Evaluation — Technical Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Complete technical reference for the Evaluation domain.

Detailed structural and implementation reference for the **Evaluation** domain.

---

## Overview

Manages supervisor and teacher evaluations of students

### Domain Statistics
- **Actions**: 3 business logic operations
- **Models**: 1 data entities
- **Livewire Components**: 1 UI components
- **Policies**: 1 authorization rules
- **Aggregates**: 1 domain aggregates

### Aggregates
- `Evaluation`

---

## Dependency Graph

This domain depends on:
- **Core**
- **Enrollment**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/Evaluation/Actions/DeleteEvaluationAction.php` | `DeleteEvaluationAction` | `BaseAction` |
| `Aggregates/Evaluation/Actions/EvaluateMentorAction.php` | `EvaluateMentorAction` | `BaseAction` |
| `Aggregates/Evaluation/Actions/SubmitEvaluationAction.php` | `SubmitEvaluationAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Evaluation/Models/Evaluation.php` | `Evaluation` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Evaluation/Livewire/MentorEvaluationManager.php` | `MentorEvaluationManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Evaluation/Policies/EvaluationPolicy.php` | `EvaluationPolicy` |

---

## File Organization

```
app/Domain/Evaluation/
├── Aggregates/           ← Aggregate roots
│   └── {Aggregate}/
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
