# Troubleshooting & FAQ: Engineering Resilience

This document formalizes the **Troubleshooting & FAQ** protocols for the Internara project,
standardized according to **ISO/IEC 12207** (Maintenance Process) and **ISO/IEC 25010**
(Maintainability). It provides a technical record of common issues, their resolution, and
architectural justifications.

---

## 1. Automated Verification (Testing)

Common issues encountered during the **Verification & Validation** phase.

### 1.1 `Pest\Exceptions\ArchViolationException` (Modular Isolation)

- **Issue**: A test fails because it uses a Model or concrete class from a foreign module.
- **Resolution**: Refactor the code to interact with the foreign module exclusively via its
  **Service Contract**.
- **Rationale**: Strict modular isolation prevents "Spaghetti Modularity" and ensures system
  analyzability.

### 1.2 `Illuminate\Database\QueryException: SQLSTATE[HY000]: General error: 1 no such table`

- **Issue**: Tests fail because a database table is missing in the in-memory SQLite database.
- **Resolution**: Ensure the module's migration path is correctly registered in its
  `ServiceProvider` using `$this->loadMigrationsFrom(...)`.
- **Verification**: Run `php artisan module:migrate-status` to check the status of modular
  migrations.

### 1.3 `Fatal error: Allowed memory size of ... bytes exhausted`

- **Issue**: Test execution crashes due to memory heap exhaustion.
- **Resolution**: Utilize the `php artisan app:test` orchestrator, which executes tests
  sequentially per module and resets the memory heap between runs.
- **Maintenance**: Review the `TestCase.php` to ensure the `tearDown()` method is correctly flushing
  the service container and triggering garbage collection.

---

## 2. Construction & Scaffolding

Issues related to the generation and implementation of system artifacts.

### 2.1 `ReflectionException: Class ... does not exist` (Auto-Binding)

- **Issue**: The system cannot resolve a Service Contract (Interface) even though the concrete
  Service exists.
- **Resolution**: Verify that the naming follows the project conventions (e.g., `UserService` for
  `UserServiceContract`).
- **Discovery**: Ensure the Contract is located in a directory named `Contracts/` within the
  module's `src` path.

### 2.2 `Vite manifest not found` (Asset Loading)

- **Issue**: The application fails to load CSS/JS assets in the development or production
  environment.
- **Resolution**: Run `npm run build` to generate the Vite manifest.
- **Development**: Ensure the `npm run dev` server is active during construction.

---

## 3. Deployment & Setup

Issues encountered during system initialization.

### 3.1 `The setup state is locked.`

- **Issue**: The **Setup Wizard** is inaccessible because the system is already installed.
- **Resolution**: If an emergency reset is required, use the `php artisan app:setup-reset` command
  to bypass the lockdown.
- **Warning**: This action should only be performed in a secure, non-production environment.

---

_Proactive troubleshooting and knowledge distribution reduce operational friction and ensure the
continued stability of the Internara ecosystem._
