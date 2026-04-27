# Setup Module

The `Setup` module provides the administrative infrastructure for business-level configuration and
onboarding of the Internara application. It orchestrates the transition from a technically installed
system to a fully operational academic ecosystem.

---

## 1. Architectural Role

As an **Administrative Lifecycle Module**, the `Setup` module coordinates domain services to perform
application-level initialization. It focuses on the business configuration required after the
technical installation (handled by the `Support` module) is complete.

---

## 2. Core Components

### 2.1 Service Layer

- **`AppSetupService`**: Orchestrates the business configuration wizard and state invariants.
- _Contract_: `Modules\Setup\Services\Contracts\AppSetupService`.
- **`OnboardingService`**: Provides administrative orchestration for batch onboarding stakeholders
  through CSV data processing.
- _Contract_: `Modules\Setup\Onboarding\Services\Contracts\OnboardingService`.

### 2.2 Security Infrastructure

- **`RequireSetupAccess` Middleware**: Restricts access to setup routes using a one-time
  `setup_token` and ensures routes are disabled once the application is marked as installed.
- **`ProtectSetupRoute` Middleware**: Prevents re-entry into the setup process once completed,
  returning a 404 status.

### 2.3 Domain Model

- **`SetupProcess`**: An aggregate root managing the state and completion invariants of the setup
  wizard.

---

## 3. Engineering Standards

- **Atomic Step Invariants**: All setup steps are managed via constants in the `AppSetupService`
  contract and enforced by the `SetupProcess` domain model to ensure sequential integrity.
- **Cross-Module Orchestration**: Interacts with `School`, `User`, `Department`, and `Internship`
  modules via their respective Service Contracts to establish the initial business state.
- **Wizard Concerns**: Utilizes the shared `HandlesWizardSteps` trait for unified UI logic.

---

## 4. Verification & Validation (V&V)

- **Unit Tests**: Validates state management in `SetupProcess` and service logic.
- **Feature Tests**: Verifies the end-to-end onboarding flow and security middleware.
- **Command**: `php artisan test modules/Setup`

---

_The Setup module ensures Internara is configured for operational success._
