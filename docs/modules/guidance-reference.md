# Guidance — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Guidance** module.

---

## Overview

Manages mentoring relationships, student guidance, and supervision logs

### Module Statistics
- **Actions**: 15 business logic operations
- **Models**: 5 data entities
- **Livewire Components**: 9 UI components
- **Policies**: 4 authorization rules
- **Submodules**: 5 module submodules

### Submodules
- `Handbook`
- `HandbookAcknowledgement`
- `Mentee`
- `Mentor`
- `SupervisionLog`

---

## Dependency Graph

This module depends on:
- **Core**
- **Enrollment**
- **Evaluation**
- **Reports**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `HandbookAcknowledgement/Actions/AcknowledgeHandbookAction.php` | `AcknowledgeHandbookAction` | `BaseAction` |
| `Handbook/Actions/CreateHandbookAction.php` | `CreateHandbookAction` | `BaseAction` |
| `Mentee/Actions/CreateMenteeAction.php` | `CreateMenteeAction` | `BaseAction` |
| `Mentor/Actions/CreateMentorAction.php` | `CreateMentorAction` | `BaseAction` |
| `Mentor/Actions/CreateMentorProfileAction.php` | `CreateMentorProfileAction` | `BaseAction` |
| `SupervisionLog/Actions/CreateSupervisionLogAction.php` | `CreateSupervisionLogAction` | `BaseAction` |
| `Handbook/Actions/DeleteHandbookAction.php` | `DeleteHandbookAction` | `BaseAction` |
| `Mentee/Actions/DeleteMenteeAction.php` | `DeleteMenteeAction` | `BaseAction` |
| `Mentor/Actions/DeleteMentorAction.php` | `DeleteMentorAction` | `BaseAction` |
| `Mentor/Actions/ToggleMentorActiveAction.php` | `ToggleMentorActiveAction` | `BaseAction` |
| `Handbook/Actions/UpdateHandbookAction.php` | `UpdateHandbookAction` | `BaseAction` |
| `Mentee/Actions/UpdateMenteeAction.php` | `UpdateMenteeAction` | `BaseAction` |
| `Mentor/Actions/UpdateMentorAction.php` | `UpdateMentorAction` | `BaseAction` |
| `Mentor/Actions/UpdateMentorProfileAction.php` | `UpdateMentorProfileAction` | `BaseAction` |
| `SupervisionLog/Actions/VerifySupervisionLogAction.php` | `VerifySupervisionLogAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Handbook/Models/Handbook.php` | `Handbook` |
| `HandbookAcknowledgement/Models/HandbookAcknowledgement.php` | `HandbookAcknowledgement` |
| `Mentee/Models/Mentee.php` | `Mentee` |
| `Mentor/Models/Mentor.php` | `Mentor` |
| `SupervisionLog/Models/SupervisionLog.php` | `SupervisionLog` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Mentor/Livewire/AssessInternship.php` | `AssessInternship` | `Component` |
| `Mentor/Livewire/EvaluateMentor.php` | `EvaluateMentor` | `Component` |
| `Handbook/Livewire/HandbookIndex.php` | `HandbookIndex` | `Component` |
| `Handbook/Livewire/HandbookManager.php` | `HandbookManager` | `BaseRecordManager` |
| `Mentor/Livewire/MentorProfileManager.php` | `MentorProfileManager` | `Component` |
| `Mentor/Livewire/ReportNotes.php` | `ReportNotes` | `Component` |
| `Mentor/Livewire/ReportReview.php` | `ReportReview` | `BaseRecordManager` |
| `SupervisionLog/Livewire/SupervisionManager.php` | `SupervisionManager` | `Component` |
| `SupervisionLog/Livewire/SupervisorLogManager.php` | `SupervisorLogManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Handbook/Policies/HandbookPolicy.php` | `HandbookPolicy` |
| `Mentee/Policies/MenteePolicy.php` | `MenteePolicy` |
| `Mentor/Policies/MentorPolicy.php` | `MentorPolicy` |
| `SupervisionLog/Policies/SupervisionLogPolicy.php` | `SupervisionLogPolicy` |

---

## File Organization

```
app/Guidance/
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

*For overview and business context, see [guidance.md](guidance.md)*
