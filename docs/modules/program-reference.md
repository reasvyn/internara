# Program — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Program** module.

---

## Overview

Manages internship and practicum programs, phases, and requirements

### Module Statistics
- **Actions**: 16 business logic operations
- **Models**: 5 data entities
- **Livewire Components**: 4 UI components
- **Policies**: 3 authorization rules
- **Submodules**: 4 module submodules

### Submodules
- `DocumentRequirement`
- `Internship`
- `InternshipGroup`
- `InternshipPhase`

---

## Dependency Graph

This module depends on:
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
| `InternshipGroup/Actions/AddMemberToGroupAction.php` | `AddMemberToGroupAction` | `BaseAction` |
| `Internship/Actions/BatchUpdateInternshipStatusAction.php` | `BatchUpdateInternshipStatusAction` | `BaseAction` |
| `Internship/Actions/CheckCloseReadinessAction.php` | `CheckCloseReadinessAction` | `BaseAction` |
| `Internship/Actions/CreateInternshipAction.php` | `CreateInternshipAction` | `BaseAction` |
| `InternshipGroup/Actions/CreateInternshipGroupAction.php` | `CreateInternshipGroupAction` | `BaseAction` |
| `InternshipPhase/Actions/CreateInternshipPhaseAction.php` | `CreateInternshipPhaseAction` | `BaseAction` |
| `DocumentRequirement/Actions/CreateRequirementAction.php` | `CreateRequirementAction` | `BaseAction` |
| `Internship/Actions/DeleteInternshipAction.php` | `DeleteInternshipAction` | `BaseAction` |
| `InternshipGroup/Actions/DeleteInternshipGroupAction.php` | `DeleteInternshipGroupAction` | `BaseAction` |
| `InternshipPhase/Actions/DeleteInternshipPhaseAction.php` | `DeleteInternshipPhaseAction` | `BaseAction` |
| `DocumentRequirement/Actions/DeleteRequirementAction.php` | `DeleteRequirementAction` | `BaseAction` |
| `InternshipGroup/Actions/RemoveMemberFromGroupAction.php` | `RemoveMemberFromGroupAction` | `BaseAction` |
| `Internship/Actions/UpdateInternshipAction.php` | `UpdateInternshipAction` | `BaseAction` |
| `InternshipGroup/Actions/UpdateInternshipGroupAction.php` | `UpdateInternshipGroupAction` | `BaseAction` |
| `InternshipPhase/Actions/UpdateInternshipPhaseAction.php` | `UpdateInternshipPhaseAction` | `BaseAction` |
| `DocumentRequirement/Actions/UpdateRequirementAction.php` | `UpdateRequirementAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Internship/Models/Internship.php` | `Internship` |
| `DocumentRequirement/Models/InternshipDocumentRequirement.php` | `InternshipDocumentRequirement` |
| `InternshipGroup/Models/InternshipGroup.php` | `InternshipGroup` |
| `InternshipGroup/Models/InternshipGroupMember.php` | `InternshipGroupMember` |
| `InternshipPhase/Models/InternshipPhase.php` | `InternshipPhase` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `InternshipGroup/Livewire/InternshipGroupManager.php` | `InternshipGroupManager` | `BaseRecordManager` |
| `Internship/Livewire/InternshipManager.php` | `InternshipManager` | `BaseRecordManager` |
| `InternshipPhase/Livewire/InternshipPhaseManager.php` | `InternshipPhaseManager` | `BaseRecordManager` |
| `DocumentRequirement/Livewire/RequirementManager.php` | `RequirementManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `InternshipGroup/Policies/InternshipGroupPolicy.php` | `InternshipGroupPolicy` |
| `InternshipPhase/Policies/InternshipPhasePolicy.php` | `InternshipPhasePolicy` |
| `Internship/Policies/InternshipPolicy.php` | `InternshipPolicy` |

---

## File Organization

```
app/Program/
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

*For overview and business context, see [program.md](program.md)*
