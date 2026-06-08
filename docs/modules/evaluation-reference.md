# Evaluation — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **Evaluation** module.

---

## Overview

Manages supervisor and teacher evaluations of students, mentor evaluations, and feedback collection.

### Submodules

None — all components are directly under `app/Evaluation/`.

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Actions/SubmitEvaluationAction.php` | `SubmitEvaluationAction` | `BaseAction` |
| `Actions/EvaluateMentorAction.php` | `EvaluateMentorAction` | `BaseAction` |
| `Actions/DeleteEvaluationAction.php` | `DeleteEvaluationAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Models/Evaluation.php` | `Evaluation` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Enums/EvaluationCategory.php` | `EvaluationCategory` | `LabelEnum` | performance, attitude, skill, attendance |
| `Enums/EvaluatorRole.php` | `EvaluatorRole` | `LabelEnum` | supervisor, teacher, mentor |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Entities/EvaluationResult.php` | `EvaluationResult` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Policies/EvaluationPolicy.php` | `EvaluationPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Livewire/MentorEvaluationManager.php` | `MentorEvaluationManager` | `Component` |

---

## Routes

File: `routes/web/evaluation.php`
Naming pattern: `evaluation.{resource}.{action}`

---

## File Organization

```
app/Evaluation/
├── Actions/
│   ├── DeleteEvaluationAction.php
│   ├── EvaluateMentorAction.php
│   └── SubmitEvaluationAction.php
├── Entities/EvaluationResult.php
├── Enums/
│   ├── EvaluationCategory.php
│   └── EvaluatorRole.php
├── Livewire/MentorEvaluationManager.php
├── Models/Evaluation.php
└── Policies/EvaluationPolicy.php
```

---

## Architectural Integration

- **Submodules**: None
- **Business Logic**: `app/Evaluation/`
- **Routing**: `routes/web/evaluation.php`
- **Views**: `resources/views/evaluation/`
- **Testing**: `tests/Feature/Evaluation/`, `tests/Unit/Evaluation/`
- **Dependencies**: User, Assessment, Program, Core
- **Used By**: Certification

*For overview and business context, see [evaluation.md](evaluation.md).*
