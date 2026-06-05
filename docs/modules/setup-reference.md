# Setup — Technical Reference

> Last updated: 2026-06-05
> Changes: Created top-level Setup technical reference detailing directory tree, actions, and models.

Detailed structural and implementation reference for the **Setup** module.

---

## Overview

Handles technical installation, environment check, database provisioning, and one-time initialization wizard.

### Module Statistics
- **Actions**: 6 business logic operations
- **Models**: 1 data entity
- **Livewire Components**: 1 UI wizard
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
├── Console/
│   └── Commands/
│       ├── SetupInstallCommand.php
│       ├── SetupResetTokenCommand.php
│       └── Traits/
│           └── InteractsWithInstallerCli.php
├── Entities/
│   └── SetupState.php
├── Livewire/
│   ├── Forms/
│   │   ├── AdminForm.php
│   │   ├── InternshipForm.php
│   │   ├── SetupDepartmentForm.php
│   │   └── SetupSchoolForm.php
│   └── SetupWizard.php
├── Models/
│   └── Setup.php
├── Policies/
│   └── SetupPolicy.php
├── Services/
│   └── EnvironmentAuditor.php
└── Support/
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

| File | Class | Extends |
|---|---|---|
| `Models/Setup.php` | `Setup` | `BaseModel` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Livewire/SetupWizard.php` | `SetupWizard` | `Component` |

---

## Authorization

- **`SetupPolicy`**: Asserts permissions for installation, setup tokens, and system provisioning.
