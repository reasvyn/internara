# Setup Module

The `Setup` module provides the automated infrastructure for the initial installation, environment verification, and system configuration of the Internara application. It orchestrates the transition from a clean repository to a production-ready baseline.

> **Governance Mandate:** This module implements the Deployment and System Initialization standards required by the authoritative **[System Requirements Specification](../../docs/internal/system-requirements-specification.md)**. It ensures environment integrity through automated pre-flight audits.

---

## 1. Architectural Role

As an **Administrative Utility Module**, the `Setup` module coordinates multiple domain services to perform first-time initialization. It is the only module authorized to perform destructive operations (like `migrate:fresh`) during the installation lifecycle.

---

## 2. Core Components

### 2.1 Service Layer
- **`SetupService`**: Orchestrates the multi-step installation wizard.
    - *Features*: Step completion tracking, record existence verification, and finalization logic.
    - *Contract*: `Modules\Setup\Services\Contracts\SetupService`.
- **`InstallerService`**: Handles low-level technical installation tasks.
    - *Features*: Migration execution, database seeding, symlink creation, and application key generation.
- **`SystemAuditor`**: Performs pre-flight environment checks.
    - *Features*: PHP version/extension verification, directory permission auditing, and database connectivity tests.

### 2.2 Security Infrastructure
- **`RequireSetupAccess` Middleware**: A critical security gate that restricts access to setup routes using a one-time `setup_token` and ensures routes are disabled once the application is marked as installed.

---

## 3. Engineering Standards

- **Zero Magic Values**: All setup steps, record types, and crucial settings are managed via constants in the `SetupService` contract.
- **Transactional Integrity**: Installation steps are designed to be idempotent or atomic to prevent partial system initialization.
- **Cross-Module Orchestration**: Interacts with `School`, `User`, `Department`, and `Setting` modules via their respective Service Contracts to establish the initial system state.

---

## 4. Verification & Validation (V&V)

Robustness is verified through a comprehensive suite of **Pest v4** tests:
- **Unit Tests**: Validates individual logic for auditing, installation, and setup management.
- **Feature Tests**: Verifies the end-to-end installation flow, security middleware behavior, and Artisan command execution.
- **Command**: `php artisan test modules/Setup`

---

_The Setup module ensures that Internara is deployed with technical precision and architectural consistency._