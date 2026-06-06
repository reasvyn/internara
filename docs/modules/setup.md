# Setup — Documentation Overview

> Last updated: 2026-06-06
> Changes: Refactored Setup module to divide technical components into Installation and SetupWizard submodules.

Handles one-time technical installation, system environment auditing, initial database seed provisioning (Roles, Academic Years), and the setup token lifecycle.

For complete technical reference including API, models, actions, and components, see [setup-reference.md](setup-reference.md).

---

## Key Principles

- **Single Execution**: The setup wizard and installation command run exactly once per system lifetime.
- **Secure Tokenization**: Exposes a cryptographically secured setup token block to restrict setup access before system finalization.
- **Transactional Seeding**: Seeds the database with critical initial records (Academic Years, Roles, Admin placeholders) in single atomic transactions.
- **Modularity & Decoupling**: Fully isolated from runtime system administration to prevent security bypasses.

---

## Context Boundary

Provides one-time system initialization. Works with Core for base logging/exception handling, and seeds base roles and structural records consumed by other modules.

---

## Module Rules

- **Execution Prevention**: Running setup actions or installer console commands after the system is flagged as `installed` is strictly prohibited.
- **Setup Token Lifecycle**:
  - Tokens expire after a configurable timeframe (default 60 minutes).
  - Tokens are stored encrypted in the database.
  - Resetting tokens is allowed only if installation is not yet finalized.
- **Finalization Window**: Finalizing setup requires a valid token and must occur within a brief time window of environment setup.

---

## Technical Elements

- **Wizard**: A multi-step setup wizard facilitating environment audit, school creation, department creation, admin generation, and finalization.
- **Environment Auditor**: Performs system readiness checks (database, PHP version, file permissions).
- **System Provisioner**: Seeds default roles and configurations.

---

## CLI Commands

| Command | Purpose |
|---|---|
| `php artisan setup:install` | Provisions the system, seeds base roles and AcademicYear, and generates a setup token |
| `php artisan setup:reset-token` | Generates a new setup token (usable only if installation is incomplete) |

---

## Error Handling & Failure Modes

- **Setup re-execution**: Any action execution when `is_installed` is true throws a `ModuleException`.
- **Invalid token**: Attempts to finalize setup or proceed without a valid token throw a `ModuleException` or redirect to token entry.

---

For complete technical reference, see [setup-reference.md](setup-reference.md).
