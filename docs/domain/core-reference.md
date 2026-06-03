# Core — Technical Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Complete technical reference for the Core domain.

Detailed structural and implementation reference for the **Core** domain.

---

## Overview

Provides foundational infrastructure, base classes, and application-wide utilities

### Domain Statistics
- **Actions**: 1 business logic operations
- **Models**: 1 data entities
- **Livewire Components**: 3 UI components
- **Policies**: 1 authorization rules
- **Aggregates**: 0 domain aggregates

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/BaseAction.php` | `BaseAction` | `Base` |

---

## Models

| File | Class |
|---|---|
| `Models/ActivityLog.php` | `ActivityLog` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Livewire/BaseRecordManager.php` | `BaseRecordManager` | `Component` |
| `Livewire/LangSwitcher.php` | `LangSwitcher` | `Component` |
| `Livewire/ThemeSwitcher.php` | `ThemeSwitcher` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Policies/BasePolicy.php` | `for` |

---

## File Organization

```
app/Domain/Core/
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

*For overview and business context, see [core.md](core.md)*
