# Program — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Program** domain.

---

## Overview

Manages internship and practicum programs, phases, and requirements

### Domain Statistics
- **Actions**: 16 business logic operations
- **Models**: 5 data entities
- **Livewire Components**: 4 UI components
- **Policies**: 3 authorization rules
- **Aggregates**: 4 domain aggregates

### Aggregates
- `DocumentRequirement`
- `Internship`
- `InternshipGroup`
- `InternshipPhase`

---

## Dependency Graph

This domain depends on:
- **Academics**
- **Assessment**
- **Assignment**
- **Certification**
- **Core**
- **Enrollment**
- **Guidance**
- **Journals**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/InternshipGroup/Actions/AddMemberToGroupAction.php` | `AddMemberToGroupAction` | `BaseAction` |
| `Aggregates/Internship/Actions/BatchUpdateInternshipStatusAction.php` | `BatchUpdateInternshipStatusAction` | `BaseAction` |
| `Aggregates/Internship/Actions/CheckCloseReadinessAction.php` | `CheckCloseReadinessAction` | `BaseAction` |
| `Aggregates/Internship/Actions/CreateInternshipAction.php` | `CreateInternshipAction` | `BaseAction` |
| `Aggregates/InternshipGroup/Actions/CreateInternshipGroupAction.php` | `CreateInternshipGroupAction` | `BaseAction` |
| `Aggregates/InternshipPhase/Actions/CreateInternshipPhaseAction.php` | `CreateInternshipPhaseAction` | `BaseAction` |
| `Aggregates/DocumentRequirement/Actions/CreateRequirementAction.php` | `CreateRequirementAction` | `BaseAction` |
| `Aggregates/Internship/Actions/DeleteInternshipAction.php` | `DeleteInternshipAction` | `BaseAction` |
| `Aggregates/InternshipGroup/Actions/DeleteInternshipGroupAction.php` | `DeleteInternshipGroupAction` | `BaseAction` |
| `Aggregates/InternshipPhase/Actions/DeleteInternshipPhaseAction.php` | `DeleteInternshipPhaseAction` | `BaseAction` |
| `Aggregates/DocumentRequirement/Actions/DeleteRequirementAction.php` | `DeleteRequirementAction` | `BaseAction` |
| `Aggregates/InternshipGroup/Actions/RemoveMemberFromGroupAction.php` | `RemoveMemberFromGroupAction` | `BaseAction` |
| `Aggregates/Internship/Actions/UpdateInternshipAction.php` | `UpdateInternshipAction` | `BaseAction` |
| `Aggregates/InternshipGroup/Actions/UpdateInternshipGroupAction.php` | `UpdateInternshipGroupAction` | `BaseAction` |
| `Aggregates/InternshipPhase/Actions/UpdateInternshipPhaseAction.php` | `UpdateInternshipPhaseAction` | `BaseAction` |
| `Aggregates/DocumentRequirement/Actions/UpdateRequirementAction.php` | `UpdateRequirementAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Internship/Models/Internship.php` | `Internship` |
| `Aggregates/DocumentRequirement/Models/InternshipDocumentRequirement.php` | `InternshipDocumentRequirement` |
| `Aggregates/InternshipGroup/Models/InternshipGroup.php` | `InternshipGroup` |
| `Aggregates/InternshipGroup/Models/InternshipGroupMember.php` | `InternshipGroupMember` |
| `Aggregates/InternshipPhase/Models/InternshipPhase.php` | `InternshipPhase` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/InternshipGroup/Livewire/InternshipGroupManager.php` | `InternshipGroupManager` | `BaseRecordManager` |
| `Aggregates/Internship/Livewire/InternshipManager.php` | `InternshipManager` | `BaseRecordManager` |
| `Aggregates/InternshipPhase/Livewire/InternshipPhaseManager.php` | `InternshipPhaseManager` | `BaseRecordManager` |
| `Aggregates/DocumentRequirement/Livewire/RequirementManager.php` | `RequirementManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/InternshipGroup/Policies/InternshipGroupPolicy.php` | `InternshipGroupPolicy` |
| `Aggregates/InternshipPhase/Policies/InternshipPhasePolicy.php` | `InternshipPhasePolicy` |
| `Aggregates/Internship/Policies/InternshipPolicy.php` | `InternshipPolicy` |

---

## File Organization

```
app/Domain/Program/
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

*For overview and business context, see [program.md](program.md)*
