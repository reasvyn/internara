# Setup — Technical Reference

> Last updated: 2026-06-06  
> Changes: Refactored the Setup module into two submodules: `Installation` and `SetupWizard`. Replaced old namespaces, directories, and registered command paths.

Detailed structural and implementation reference for the **Setup** module.

---

## Overview

Handles technical installation, environment check, database provisioning, and one-time initialization wizard.

### Module Statistics
- **Actions**: 6 business logic operations (split across submodules)
- **Models**: 0 data entities (persists wizard progress and tokens in the `settings` table)
- **Livewire Components**: 1 UI wizard (with 4 Form Objects)
- **Submodules**: 2 submodules (`Installation`, `SetupWizard`)

---

## Directory Structure

```
app/Setup/
├── Installation/           ← Submodule handling CLI commands & setup tokens
│   ├── Actions/
│   │   ├── GenerateSetupTokenAction.php
│   │   ├── InstallSystemAction.php
│   │   └── ValidateSetupTokenAction.php
│   ├── Console/            ← Artisan commands (setup:install, setup:reset-token)
│   │   └── Commands/
│   │       ├── SetupInstallCommand.php
│   │       ├── SetupResetTokenCommand.php
│   │       └── Traits/
│   │           └── InteractsWithInstallerCli.php
│   ├── Http/
│   │   └── Middleware/
│   │       ├── ProtectSetupRouteMiddleware.php
│   │       └── RequireSetupAccessMiddleware.php
│   └── Support/
│       └── SystemProvisioner.php
├── SetupWizard/            ← Submodule handling the multi-step web wizard UI
    ├── Actions/
    │   ├── SetupSchoolAction.php
    │   ├── SetupDepartmentAction.php
    │   └── FinalizeSetupAction.php
    ├── Entities/
    │   └── SetupState.php
    ├── Events/
    │   └── SetupFinalized.php
    ├── Listeners/
    │   └── LogSetupFinalized.php
    └── Livewire/
        ├── Forms/
        │   ├── SuperAdminForm.php
        │   ├── InternshipForm.php
        │   ├── DepartmentForm.php
        │   └── SchoolForm.php
        └── SetupWizard.php
```

---

## Actions

### Installation Submodule
| File | Class | Extends |
|---|---|---|
| `Installation/Actions/GenerateSetupTokenAction.php` | `GenerateSetupTokenAction` | `BaseAction` |
| `Installation/Actions/InstallSystemAction.php` | `InstallSystemAction` | `BaseAction` |
| `Installation/Actions/ValidateSetupTokenAction.php` | `ValidateSetupTokenAction` | `BaseAction` |

### SetupWizard Submodule
| File | Class | Extends |
|---|---|---|
| `SetupWizard/Actions/SetupSchoolAction.php` | `SetupSchoolAction` | `BaseAction` |
| `SetupWizard/Actions/SetupDepartmentAction.php` | `SetupDepartmentAction` | `BaseAction` |
| `SetupWizard/Actions/SetupSuperAdminAction.php` | `SetupSuperAdminAction` | `BaseAction` |
| `SetupWizard/Actions/SetupInternshipAction.php` | `SetupInternshipAction` | `BaseAction` |
| `SetupWizard/Actions/FinalizeSetupAction.php` | `FinalizeSetupAction` | `BaseAction` |

---

## Models

Setup does not own any separate Eloquent model. The wizard status, Single-Use Token block, and configuration payloads are saved under the `setup.*` namespace inside the global `Setting` model (Settings module).

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `SetupWizard/Livewire/SetupWizard.php` | `SetupWizard` | `Component` |

---

## Middleware

- **`ProtectSetupRouteMiddleware`**: Restricts access to setup routes depending on installation state and valid token.
- **`RequireSetupAccessMiddleware`**: Ensures that clients without setup access are handled appropriately based on whether the system is installed.

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Installation`, `SetupWizard`
- **Business Logic (`app/`)**: Located in [app/Setup/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Setup/)
- **Routing (`routes/`)**: [routes/web/setup.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/setup.php)
- **Views (`views/`)**: Blade templates and layouts are in [resources/views/setup/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/setup/)
- **Testing (`tests/`)**: Feature `tests/Feature/Setup/`, Unit `tests/Unit/Setup/`


*For overview and business context, see [setup.md](setup.md)*
