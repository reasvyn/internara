# Program — Technical Reference

> Last updated: 2026-06-06  
> Changes: Removed references to the separate document requirements and internship phase tables.

Detailed structural and implementation reference for the **Program** module.

---

## Overview

Manages internship programs and cohort student groupings.

### Module Statistics
- **Actions**: 10 business logic operations
- **Models**: 3 data entities (`Internship`, `InternshipGroup`, `InternshipGroupMember`)
- **Livewire Components**: 2 UI components
- **Policies**: 2 authorization rules
- **Submodules**: 2 module submodules

### Submodules
- `Internship`
- `InternshipGroup`

---

## Dependency Graph

This module depends on:
- **Academics** (calendar years)
- **Core** (base classes)
- **Enrollment** (student placements)
- **User** (students and mentors)

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Internship/Actions/CreateInternshipAction.php` | `CreateInternshipAction` | `BaseAction` |
| `Internship/Actions/UpdateInternshipAction.php` | `UpdateInternshipAction` | `BaseAction` |
| `Internship/Actions/DeleteInternshipAction.php` | `DeleteInternshipAction` | `BaseAction` |
| `Internship/Actions/BatchUpdateInternshipStatusAction.php` | `BatchUpdateInternshipStatusAction` | `BaseAction` |
| `Internship/Actions/CheckCloseReadinessAction.php` | `CheckCloseReadinessAction` | `BaseAction` |
| `InternshipGroup/Actions/CreateInternshipGroupAction.php` | `CreateInternshipGroupAction` | `BaseAction` |
| `InternshipGroup/Actions/UpdateInternshipGroupAction.php` | `UpdateInternshipGroupAction` | `BaseAction` |
| `InternshipGroup/Actions/DeleteInternshipGroupAction.php` | `DeleteInternshipGroupAction` | `BaseAction` |
| `InternshipGroup/Actions/AddMemberToGroupAction.php` | `AddMemberToGroupAction` | `BaseAction` |
| `InternshipGroup/Actions/RemoveMemberFromGroupAction.php` | `RemoveMemberFromGroupAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Internship/Models/Internship.php` | `Internship` |
| `InternshipGroup/Models/InternshipGroup.php` | `InternshipGroup` |
| `InternshipGroup/Models/InternshipGroupMember.php` | `InternshipGroupMember` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Internship/Livewire/InternshipManager.php` | `InternshipManager` | `BaseRecordManager` |
| `InternshipGroup/Livewire/InternshipGroupManager.php` | `InternshipGroupManager` | `BaseRecordManager` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Internship/Policies/InternshipPolicy.php` | `InternshipPolicy` | `BasePolicy` |
| `InternshipGroup/Policies/InternshipGroupPolicy.php` | `InternshipGroupPolicy` | `BasePolicy` |

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
│   └── InternshipGroup/
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
