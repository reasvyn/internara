# Settings — Technical Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Complete technical reference for the Settings domain.

Detailed structural and implementation reference for the **Settings** domain.

---

## Overview

Manages system configuration and global preferences

### Domain Statistics
- **Actions**: 6 business logic operations
- **Models**: 1 data entities
- **Livewire Components**: 1 UI components
- **Policies**: 1 authorization rules
- **Aggregates**: 1 domain aggregates

### Aggregates
- `Setting`

---

## Dependency Graph

This domain depends on:
- **Academics**
- **Core**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/Setting/Actions/BatchSetSettingAction.php` | `BatchSetSettingAction` | `BaseAction` |
| `Aggregates/Setting/Actions/GetAcademicYearsAction.php` | `GetAcademicYearsAction` | `Base` |
| `Aggregates/Setting/Actions/SaveSystemSettingsAction.php` | `SaveSystemSettingsAction` | `BaseAction` |
| `Aggregates/Setting/Actions/SetSettingAction.php` | `SetSettingAction` | `BaseAction` |
| `Aggregates/Setting/Actions/TestMailSettingsAction.php` | `TestMailSettingsAction` | `BaseAction` |
| `Aggregates/Setting/Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Setting/Models/Setting.php` | `Setting` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Setting/Livewire/SystemSetting.php` | `SystemSetting` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Setting/Policies/SettingPolicy.php` | `SettingPolicy` |

---

## File Organization

```
app/Domain/Settings/
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

*For overview and business context, see [settings.md](settings.md)*
