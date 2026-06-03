# Guidance — Technical Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Complete technical reference for the Guidance domain.

Detailed structural and implementation reference for the **Guidance** domain.

---

## Overview

Manages mentoring relationships, student guidance, and supervision logs

### Domain Statistics
- **Actions**: 15 business logic operations
- **Models**: 5 data entities
- **Livewire Components**: 9 UI components
- **Policies**: 4 authorization rules
- **Aggregates**: 5 domain aggregates

### Aggregates
- `Handbook`
- `HandbookAcknowledgement`
- `Mentee`
- `Mentor`
- `SupervisionLog`

---

## Dependency Graph

This domain depends on:
- **Core**
- **Enrollment**
- **Evaluation**
- **Reports**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/HandbookAcknowledgement/Actions/AcknowledgeHandbookAction.php` | `AcknowledgeHandbookAction` | `BaseAction` |
| `Aggregates/Handbook/Actions/CreateHandbookAction.php` | `CreateHandbookAction` | `BaseAction` |
| `Aggregates/Mentee/Actions/CreateMenteeAction.php` | `CreateMenteeAction` | `BaseAction` |
| `Aggregates/Mentor/Actions/CreateMentorAction.php` | `CreateMentorAction` | `BaseAction` |
| `Aggregates/Mentor/Actions/CreateMentorProfileAction.php` | `CreateMentorProfileAction` | `BaseAction` |
| `Aggregates/SupervisionLog/Actions/CreateSupervisionLogAction.php` | `CreateSupervisionLogAction` | `BaseAction` |
| `Aggregates/Handbook/Actions/DeleteHandbookAction.php` | `DeleteHandbookAction` | `BaseAction` |
| `Aggregates/Mentee/Actions/DeleteMenteeAction.php` | `DeleteMenteeAction` | `BaseAction` |
| `Aggregates/Mentor/Actions/DeleteMentorAction.php` | `DeleteMentorAction` | `BaseAction` |
| `Aggregates/Mentor/Actions/ToggleMentorActiveAction.php` | `ToggleMentorActiveAction` | `BaseAction` |
| `Aggregates/Handbook/Actions/UpdateHandbookAction.php` | `UpdateHandbookAction` | `BaseAction` |
| `Aggregates/Mentee/Actions/UpdateMenteeAction.php` | `UpdateMenteeAction` | `BaseAction` |
| `Aggregates/Mentor/Actions/UpdateMentorAction.php` | `UpdateMentorAction` | `BaseAction` |
| `Aggregates/Mentor/Actions/UpdateMentorProfileAction.php` | `UpdateMentorProfileAction` | `BaseAction` |
| `Aggregates/SupervisionLog/Actions/VerifySupervisionLogAction.php` | `VerifySupervisionLogAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Handbook/Models/Handbook.php` | `Handbook` |
| `Aggregates/HandbookAcknowledgement/Models/HandbookAcknowledgement.php` | `HandbookAcknowledgement` |
| `Aggregates/Mentee/Models/Mentee.php` | `Mentee` |
| `Aggregates/Mentor/Models/Mentor.php` | `Mentor` |
| `Aggregates/SupervisionLog/Models/SupervisionLog.php` | `SupervisionLog` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Mentor/Livewire/AssessInternship.php` | `AssessInternship` | `Component` |
| `Aggregates/Mentor/Livewire/EvaluateMentor.php` | `EvaluateMentor` | `Component` |
| `Aggregates/Handbook/Livewire/HandbookIndex.php` | `HandbookIndex` | `Component` |
| `Aggregates/Handbook/Livewire/HandbookManager.php` | `HandbookManager` | `BaseRecordManager` |
| `Aggregates/Mentor/Livewire/MentorProfileManager.php` | `MentorProfileManager` | `Component` |
| `Aggregates/Mentor/Livewire/ReportNotes.php` | `ReportNotes` | `Component` |
| `Aggregates/Mentor/Livewire/ReportReview.php` | `ReportReview` | `BaseRecordManager` |
| `Aggregates/SupervisionLog/Livewire/SupervisionManager.php` | `SupervisionManager` | `Component` |
| `Aggregates/SupervisionLog/Livewire/SupervisorLogManager.php` | `SupervisorLogManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Handbook/Policies/HandbookPolicy.php` | `HandbookPolicy` |
| `Aggregates/Mentee/Policies/MenteePolicy.php` | `MenteePolicy` |
| `Aggregates/Mentor/Policies/MentorPolicy.php` | `MentorPolicy` |
| `Aggregates/SupervisionLog/Policies/SupervisionLogPolicy.php` | `SupervisionLogPolicy` |

---

## File Organization

```
app/Domain/Guidance/
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

*For overview and business context, see [guidance.md](guidance.md)*
