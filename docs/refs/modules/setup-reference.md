# Setup — Technical Reference

## Description

Detailed structural and implementation reference for the **Setup** module.

---

## Overview

Handles one-time technical installation, environment checks, database provisioning, setup token
lifecycle, and the interactive setup wizard for initial configuration.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/Installation/Actions/GenerateSetupTokenAction.php` | `GenerateSetupTokenAction` | `BaseCommandAction` |
| `Domain/Installation/Actions/InstallSystemAction.php` | `InstallSystemAction` | `BaseCommandAction` |
| `Domain/Installation/Actions/SeedDummyDataAction.php` | `SeedDummyDataAction` | `BaseCommandAction` |
| `Domain/Installation/Actions/ValidateSetupTokenAction.php` | `ValidateSetupTokenAction` | `BaseCommandAction` |
| `Domain/SetupWizard/Actions/FinalizeSetupAction.php` | `FinalizeSetupAction` | `BaseCommandAction` |
| `Domain/SetupWizard/Actions/SetupDepartmentAction.php` | `SetupDepartmentAction` | `BaseCommandAction` |
| `Domain/SetupWizard/Actions/SetupSchoolAction.php` | `SetupSchoolAction` | `BaseCommandAction` |
| `Domain/SetupWizard/Actions/SetupSuperAdminAction.php` | `SetupSuperAdminAction` | `BaseCommandAction` |

## Entities

| File | Class | Extends |
|---|---|---|
| `Entities/SetupEntity.php` | `SetupEntity` | `BaseEntity` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/Installation/Data/SetupTokenData.php` | `SetupTokenData` | `BaseData` |
| `Domain/SetupWizard/Data/FinalizeSetupData.php` | `FinalizeSetupData` | `BaseData` |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/SetupWizard/Events/SetupFinalized.php` | `SetupFinalized` | `BaseEvent` |

## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Domain/SetupWizard/Listeners/LogSetupFinalized.php` | `LogSetupFinalized` | — |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/SetupWizard/Livewire/Forms/DepartmentForm.php` | `DepartmentForm` | `BaseFormView` |
| `Domain/SetupWizard/Livewire/Forms/SchoolForm.php` | `SchoolForm` | `BaseFormView` |
| `Domain/SetupWizard/Livewire/Forms/SuperAdminForm.php` | `SuperAdminForm` | `BaseFormView` |
| `Domain/SetupWizard/Livewire/SetupWizard.php` | `SetupWizard` | `Component` |

## Middleware

| File                                                            | Middleware                     | Purpose                                        |
| --------------------------------------------------------------- | ------------------------------ | ---------------------------------------------- |
| `Installation/Http/Middleware/ProtectSetupRouteMiddleware.php`  | `ProtectSetupRouteMiddleware`  | Protects setup routes from unauthorized access |
| `Installation/Http/Middleware/RequireSetupAccessMiddleware.php` | `RequireSetupAccessMiddleware` | Ensures setup access requirements              |

## HTTP Controllers

| File | Controller | Extends |
|---|---|---|
| `Http/Controllers/SetupController.php` | `SetupController` | `BaseController` |

## Support

| File                                          | Class               | Purpose                           |
| --------------------------------------------- | ------------------- | --------------------------------- |
| `Installation/Services/SystemProvisioner.php` | `SystemProvisioner` | System provisioning orchestration |

## Console Commands

| Command Signature   | Class                    | Description                     |
| ------------------- | ------------------------ | ------------------------------- |
| `setup:install`     | `SetupInstallCommand`    | One-time system installation    |
| `setup:reset-token` | `SetupResetTokenCommand` | Resets setup installation token |

### Traits

| File                                                                   | Trait                       | Purpose                                        |
| ---------------------------------------------------------------------- | --------------------------- | ---------------------------------------------- |
| `Installation/Console/Commands/Concerns/InteractsWithInstallerCli.php` | `InteractsWithInstallerCli` | CLI interaction helpers for installer commands |

---

## Routes

File: `routes/web/setup.php` Named routes: `setup`, `setup.cleanup` (setup-token protected)

## Views

Views are located in `resources/views/setup/`. See [UI/UX](../../guides/ui-ux/design-system.md) for the design
system.

## Tests

Tests are located in `tests/Setup/`. See [Testing](../../guides/infra/testing.md) for the testing
conventions.

## Factories

None.

## Migrations

None.

---

## Architectural Integration

- **Submodules**: `Installation`, `SetupWizard`
- **Business Logic**: `app/Modules/Setup/`
- **Routing**: `routes/web/setup.php`
- **Views**: `resources/views/setup/`
- **Testing**: `tests/Setup/`
- **Dependencies**: Core, Academics

_For overview and business context, see [setup.md](setup.md)._
