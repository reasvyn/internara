# Setup — Technical Reference

> Last updated: 2026-06-16
> Changes: sync — add SetupData DTO to Data section

Detailed structural and implementation reference for the **Setup** module.

---

## Overview

Handles one-time technical installation, environment checks, database provisioning, setup token lifecycle, and the interactive setup wizard for initial configuration.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Installation/Actions/GenerateSetupTokenAction.php` | `GenerateSetupTokenAction` | `BaseAction` |
| `Installation/Actions/ValidateSetupTokenAction.php` | `ValidateSetupTokenAction` | `BaseAction` |
| `Installation/Actions/InstallSystemAction.php` | `InstallSystemAction` | Process `BaseAction` |
| `SetupWizard/Actions/SetupSuperAdminAction.php` | `SetupSuperAdminAction` | `BaseAction` |
| `SetupWizard/Actions/SetupSchoolAction.php` | `SetupSchoolAction` | `BaseAction` |
| `SetupWizard/Actions/SetupDepartmentAction.php` | `SetupDepartmentAction` | `BaseAction` |
| `SetupWizard/Actions/FinalizeSetupAction.php` | `FinalizeSetupAction` | Process `BaseAction` |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Entities/SetupEntity.php` | `SetupEntity` | `BaseEntity` |

## Data / DTOs

| File | Class | Extends |
| ---- | ----- | ------- |
| `Data/SetupData.php` | `SetupData` | `BaseData` |
| `Data/AdminData.php` | `AdminData` | `BaseData` |
| `Data/SchoolData.php` | `SchoolData` | `BaseData` |
| `Installation/Data/SetupTokenData.php` | `SetupTokenData` | `BaseData` |

---

## Events

| File | Event | Extends |
| ---- | ----- | ------- |
| `SetupWizard/Events/SetupFinalized.php` | `SetupFinalized` | `BaseEvent` |

## Listeners

| File | Listener |
| ---- | -------- |
| `SetupWizard/Listeners/LogSetupFinalized.php` | `LogSetupFinalized` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `SetupWizard/Livewire/SetupWizard.php` | `SetupWizard` | `Component` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `SetupWizard/Livewire/Forms/SuperAdminForm.php` | `SuperAdminForm` |
| `SetupWizard/Livewire/Forms/SchoolForm.php` | `SchoolForm` |
| `SetupWizard/Livewire/Forms/DepartmentForm.php` | `DepartmentForm` |

## Middleware

| File | Middleware | Purpose |
| ---- | ---------- | ------- |
| `Installation/Http/Middleware/ProtectSetupRouteMiddleware.php` | `ProtectSetupRouteMiddleware` | Protects setup routes from unauthorized access |
| `Installation/Http/Middleware/RequireSetupAccessMiddleware.php` | `RequireSetupAccessMiddleware` | Ensures setup access requirements |

## HTTP Controllers

| File | Controller | Extends |
| ---- | ---------- | ------- |
| `Http/Controllers/SetupController.php` | `SetupController` | `BaseController` |

## Support

| File | Class | Purpose |
| ---- | ----- | ------- |
| `Installation/Support/SystemProvisioner.php` | `SystemProvisioner` | System provisioning orchestration |

## Console Commands

| Command Signature | Class | Description |
| ----------------- | ----- | ----------- |
| `setup:install` | `SetupInstallCommand` | One-time system installation |
| `setup:reset-token` | `SetupResetTokenCommand` | Resets setup installation token |

### Traits

| File | Trait | Purpose |
| ---- | ----- | ------- |
| `Installation/Console/Commands/Traits/InteractsWithInstallerCli.php` | `InteractsWithInstallerCli` | CLI interaction helpers for installer commands |

---

## Routes

File: `routes/web/setup.php`
Naming pattern: `setup.{resource}.{action}`

## Views

Views are located in `resources/views/setup/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Setup/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

None.

## Migrations

None.

---


---

## Architectural Integration

- **Submodules**: `Installation`, `SetupWizard`
- **Business Logic**: `app/Setup/`
- **Routing**: `routes/web/setup.php`
- **Views**: `resources/views/setup/`
- **Testing**: `tests/Feature/Setup/`, `tests/Unit/Setup/`
- **Dependencies**: Core, Academics

*For overview and business context, see [setup.md](setup.md).*
