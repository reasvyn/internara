# Setup — API Reference
> Last updated: 2026-05-28
> Changes: docs: add ideal setup domain design


Total: 27 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Setup/Actions/FinalizeSetupAction.php` | `FinalizeSetupAction` | `BaseAction` | Finalizes setup: creates school, department, admin, internship, saves recovery key |
| `Setup/Actions/GenerateSetupTokenAction.php` | `GenerateSetupTokenAction` | `BaseAction` | Generates an encrypted setup token |
| `Setup/Actions/InitializeSuperAdminAction.php` | `InitializeSuperAdminAction` | `BaseAction` | Creates initial super admin user |
| `Setup/Actions/InstallSystemAction.php` | `InstallSystemAction` | `BaseAction` | Runs initial system installation (env check + provisioning) |
| `Setup/Actions/RecoverSuperAdminAction.php` | `RecoverSuperAdminAction` | `BaseAction` | Recovers super admin account with notification |
| `Setup/Actions/SetupDepartmentAction.php` | `SetupDepartmentAction` | `BaseAction` | Creates department during setup |
| `Setup/Actions/SetupSchoolAction.php` | `SetupSchoolAction` | `BaseAction` | Creates school during setup |
| `Setup/Actions/SetupSuperAdminAction.php` | `SetupSuperAdminAction` | `BaseAction` | Creates super admin during setup (name and username from config defaults) |
| `Setup/Actions/ValidateSetupTokenAction.php` | `ValidateSetupTokenAction` | `BaseAction` | Validates a setup access token |

## Console Commands

| File | Class | Extends | Description |
|---|---|---|---|
| `Setup/Console/Commands/SetupInstallCommand.php` | `SetupInstallCommand` | `Command` | CLI command for headless installation |
| `Setup/Console/Commands/SetupResetCommand.php` | `SetupResetCommand` | `Command` | CLI command to reset setup state |

### Console Traits

| File | Class | Description |
|---|---|---|
| `Setup/Console/Commands/Traits/InteractsWithInstallerCli.php` | `InteractsWithInstallerCli` | Trait providing shared CLI I/O helpers for installation commands |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Setup/Entities/SetupState.php` | `SetupState` | `BaseEntity` | Read-only DTO for setup state |

## Events / Listeners

| File | Class | Description |
|---|---|---|
| `Setup/Events/SetupFinalized.php` | `SetupFinalized` | Event dispatched when setup is finalized |
| `Setup/Listeners/LogSetupFinalized.php` | `LogSetupFinalized` | Listener that logs setup finalization |

## Middleware

| File | Class | Scope | Description |
|---|---|---|---|
| `Setup/Http/Middleware/RequireSetupAccessMiddleware.php` | `RequireSetupAccessMiddleware` | Global (web group) | Redirects all traffic to `/setup` when system is not installed. Bypasses Livewire subrequests. |
| `Setup/Http/Middleware/ProtectSetupRouteMiddleware.php` | `ProtectSetupRouteMiddleware` | Route `/setup` (alias `setup.protected`) | Three-layer security: rate limiting (20/min/IP), token validation (query string or POST), session authorization. Self-destructs with 404 after installation. |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Setup/Livewire/SetupWizard.php` | `SetupWizard` | `Component` | Multi-step setup wizard (school, dept, admin, internship) |

### Livewire Form Objects

| File | Class | Extends | Description |
|---|---|---|---|
| `Setup/Livewire/Forms/SchoolForm.php` | `SchoolForm` | `Form` | School details form with validation |
| `Setup/Livewire/Forms/DepartmentForm.php` | `DepartmentForm` | `Form` | Department form with validation |
| `Setup/Livewire/Forms/AdminForm.php` | `AdminForm` | `Form` | Super admin credentials form with validation |
| `Setup/Livewire/Forms/InternshipForm.php` | `InternshipForm` | `Form` | Internship period form with optional detection |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Setup/Models/Setup.php` | `Setup` | `BaseModel` | Eloquent model for installation state (belongsTo School and Department) |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Setup/Policies/SetupPolicy.php` | `SetupPolicy` | `BasePolicy` | Authorization for setup operations |

## Services

| File | Class | Description |
|---|---|---|
| `Setup/Services/EnvironmentAuditor.php` | `EnvironmentAuditor` | Audits environment requirements (PHP extensions, permissions, etc.) |

## Support

| File | Class | Description |
|---|---|---|
| `Setup/Support/SystemProvisioner.php` | `SystemProvisioner` | Provisions system (runs migrations, creates storage link, etc.) |

## Where to Find It

- `app/Domain/Setup/Actions/FinalizeSetupAction.php` — orchestrated finalization
- `app/Domain/Setup/Actions/SetupSuperAdminAction.php` — super admin creation
- `app/Domain/Setup/Actions/GenerateSetupTokenAction.php` — token generation
- `app/Domain/Setup/Actions/ValidateSetupTokenAction.php` — token validation
- `app/Domain/Setup/Actions/RecoverSuperAdminAction.php` — emergency recovery
- `app/Domain/Setup/Entities/SetupState.php` — immutable installation state
- `app/Domain/Setup/Services/EnvironmentAuditor.php` — pre-install audit
- `app/Domain/Setup/Support/SystemProvisioner.php` — CLI provisioning
- `app/Domain/Setup/Models/Setup.php` — installation state model
- `app/Domain/Setup/Livewire/SetupWizard.php` — 7-step wizard
- `app/Domain/Setup/Http/Middleware/ProtectSetupRouteMiddleware.php` — token gate
- `app/Domain/Setup/Http/Middleware/RequireSetupAccessMiddleware.php` — install redirect
- `config/setup.php` — requirements, defaults, security thresholds
- `routes/web/setup.php` — setup route definitions

## Dependency Graph

```
Setup Domain
├── Core            → BaseAction, BaseEntity, BaseState, SmartLogger,
│                      AuditReport, AuditCheck, CacheKeys
├── Auth            → Role enum, AccountStatus enum
├── School          → School, Department models (school/dept creation)
├── User            → User model (admin account creation)
├── Internship      → CreateInternshipAction (optional program creation)
├── Admin           → SendNotificationAction (system notification)
└── Settings        → AppInfo (version display in wizard)
```

Setup has the widest dependency graph of any domain — it touches almost every other
domain to create initial records. This is intentional and unavoidable: installation
must bootstrap the entire system.
