# Setup Module

The `Setup` module provides the administrative infrastructure for business-level configuration and
onboarding of the Internara application. It orchestrates the transition from a technically installed
system to a fully operational academic ecosystem.

---

## 1. Wizard Flow

The setup wizard consists of **6 sequential steps**:

| #   | Step              | Component         | Purpose                              |
| --- | ----------------- | ----------------- | ------------------------------------ |
| 1   | **Welcome**       | `SetupWelcome`    | Introduction and prerequisites check |
| 2   | **School**        | `SchoolSetup`     | Configure institution details        |
| 3   | **Administrator** | `AccountSetup`    | Create super admin account           |
| 4   | **Department**    | `DepartmentSetup` | Define organizational departments    |
| 5   | **Internship**    | `InternshipSetup` | Configure internship period          |
| 6   | **Complete**      | `SetupComplete`   | Finalization and system activation   |

Each step must be completed sequentially. Access is controlled via `setup_token` middleware.

---

## 2. Core Components

### 2.1 Service Layer

- **`AppSetupService`**: Orchestrates the business configuration wizard and state invariants.
    - Manages step completion tracking via `setup_step_*` settings
    - Enforces sequential integrity via `SetupProcess` aggregate
    - _Contract_: `Modules\Setup\Services\Contracts\AppSetupService`
- **`SetupRequirementRegistry`**: Centralized registry for external setup requirement providers
    - Allows modules to register their setup completion conditions
    - _Contract_: `Modules\Setup\Services\Contracts\SetupRequirementProvider`
- **`OnboardingService`**: Provides administrative orchestration for batch onboarding stakeholders
  through CSV data processing.
    - _Contract_: `Modules\Setup\Onboarding\Services\Contracts\OnboardingService`

### 2.2 Security Infrastructure

- **`RequireSetupAccess` Middleware**: Restricts access to setup routes using a one-time
  `setup_token` and ensures routes are disabled once the application is marked as installed.
- **`ProtectSetupRoute` Middleware**: Prevents re-entry into the setup process once completed,
  returning a 404 status.

### 2.3 Domain Model

- **`SetupProcess`**: An aggregate root managing the state and completion invariants of the setup
  wizard.

### 2.4 Livewire Components

Each wizard step is implemented as a standalone Livewire component:

- `SetupWelcome`, `SchoolSetup`, `AccountSetup`, `DepartmentSetup`, `InternshipSetup`,
  `SetupComplete`
- Shared behavior via `HandlesWizardSteps` trait

---

## 3. Engineering Standards

- **Atomic Step Invariants**: All setup steps are managed via constants in the `AppSetupService`
  contract (`STEP_WELCOME`, `STEP_SCHOOL`, `STEP_ACCOUNT`, `STEP_DEPARTMENT`, `STEP_INTERNSHIP`,
  `STEP_COMPLETE`) and enforced by the `SetupProcess` domain model to ensure sequential integrity.
- **Cross-Module Orchestration**: Interacts with `School`, `User`, `Department`, and `Internship`
  modules via their respective Service Contracts to establish the initial business state.
- **Wizard Concerns**: Utilizes the shared `HandlesWizardSteps` trait for unified UI logic.
- **Requirement Registry**: Modules register their setup completion conditions via
  `SetupRequirementRegistry` for test isolation and extensibility.

---

## 4. Verification & Validation (V&V)

- **Unit Tests**: Validates state management in `SetupProcess` and service logic.
- **Feature Tests**: Verifies the end-to-end onboarding flow and security middleware.
    - `SetupCompleteTest`: 3 tests
    - `SchoolSetupTest`: 3 tests
    - `AccountSetupTest`: 3 tests
    - `DepartmentSetupTest`: 3 tests
    - `InternshipSetupTest`: 3 tests
    - `SetupWelcomeTest`: 2 tests
- **Command**: `php artisan test modules/Setup`

---

## 5. Module Registration

Modules wishing to participate in the setup flow must:

1. Implement `SetupRequirementProvider` contract
2. Register with `SetupRequirementRegistry` during setup wizard tests
3. Add their step to `SetupProcess::STEP_RECORDS` mapping if record-dependent

---

_The Setup module ensures Internara is configured for operational success._
