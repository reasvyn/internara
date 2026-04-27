# Support Module

The `Support` module provides the **Infrastructure & Operational Bridge** for Internara. It
centralizes technical system installation, environment auditing, help documentation, and developer
automation.

---

## 1. Domain Domains

### 1.1 Technical Installation Domain

Handles the technical initialization of the Internara system.

- **`SystemInstaller`**: Handles low-level technical installation tasks (env creation, app key
  generation, migration execution, and storage symlinking).
- **`InstallationAuditor`**: Performs pre-flight environment checks (PHP extensions, directory
  permissions, and database connectivity).
- **`SystemInstallCommand`**: Automated technical initialization via CLI
  (`php artisan system:install`).

### 1.2 Help & Documentation Domain

Provides the knowledge base and support infrastructure for application users.

- **Help Articles**: Managed FAQ and documentation.
- **Support Center**: UI for user guidance.

### 1.3 Developer Scaffolding Domain

Provides custom Artisan generators that enforce Internara's architectural standards.

- `module:make-class`: Generates a final PHP class with direct namespacing.
- `module:make-interface`: Generates a contract in the appropriate domain folder.
- `module:make-trait`: Generates a reusable concern.

---

## 2. Engineering Standards

- **Infrastructure Sovereignty**: The `Support` module is authorized to interact with low-level
  system resources (Filesystem, Shell, PHP Configuration) to facilitate installation and auditing.
- **Domain Separation**: Technical installation concerns are strictly separated from the business
  configuration (handled by the `Setup` module).
- **Wizard Concerns**: Utilizes the shared `HandlesWizardSteps` trait for unified UI logic in the
  installation welcome and requirement check screens.

---

## 3. Verification & Validation (V&V)

- **Unit Tests**: Validates the audit logic and installation engine.

- **Command**: `php artisan test modules/Support`

---

_The Support module shields business domains from operational complexity through automation._
