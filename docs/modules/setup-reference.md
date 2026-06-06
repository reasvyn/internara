# Setup — Technical Reference

> Last updated: 2026-06-06  
> Changes: Removed the separate Setup model and setups table. The installation wizard progress and setup tokens are now stored inside the `settings` table.

Detailed structural and implementation reference for the **Setup** module.

---

## Overview

Handles technical installation, environment check, database provisioning, and one-time initialization wizard.

### Module Statistics
- **Actions**: 6 business logic operations
- **Models**: 0 data entities (persists wizard progress and tokens in the `settings` table)
- **Livewire Components**: 1 UI wizard (with 4 Form Objects)
- **Policies**: 1 authorization rule

---

## Directory Structure

```
app/Setup/
├── Actions/
│   ├── FinalizeSetupAction.php
│   ├── GenerateSetupTokenAction.php
│   ├── InstallSystemAction.php
│   ├── SetupDepartmentAction.php
│   ├── SetupSchoolAction.php
│   └── ValidateSetupTokenAction.php
├── Console/              ← (belongs to SysAdmin module)
├── Entities/
│   └── SetupState.php
├── Livewire/
│   ├── Forms/
│   │   ├── AdminForm.php
│   │   ├── InternshipForm.php
│   │   ├── SetupDepartmentForm.php
│   │   └── SetupSchoolForm.php
│   └── SetupWizard.php
├── Policies/
│   └── SetupPolicy.php
├── Support/
    └── SystemProvisioner.php
```

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/GenerateSetupTokenAction.php` | `GenerateSetupTokenAction` | `BaseAction` |
| `Actions/InstallSystemAction.php` | `InstallSystemAction` | `BaseAction` |
| `Actions/SetupSchoolAction.php` | `SetupSchoolAction` | `BaseAction` |
| `Actions/SetupDepartmentAction.php` | `SetupDepartmentAction` | `BaseAction` |
| `Actions/FinalizeSetupAction.php` | `FinalizeSetupAction` | `BaseAction` |
| `Actions/ValidateSetupTokenAction.php` | `ValidateSetupTokenAction` | `BaseAction` |

---

## Models

Setup does not own any separate Eloquent model. The wizard status, Single-Use Token block, and configuration payloads are saved under the `setup.*` namespace inside the global `Setting` model (SysAdmin module).

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Livewire/SetupWizard.php` | `SetupWizard` | `Component` |

---

## Authorization

- **`SetupPolicy`**: Asserts permissions for installation, setup tokens, and system provisioning.

---

*For overview and business context, see [setup.md](setup.md)*
