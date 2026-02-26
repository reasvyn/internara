# User Module

The `User` module serves as the authoritative source of truth for stakeholder identity and account
management within the Internara ecosystem. It provides the necessary infrastructure for managing
Instructors, Students, Administrators, and Industry Mentors.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/dev/specs.md)**. All implementation must
> adhere to the **[Coding Conventions](../../docs/dev/conventions.md)**.

---

## 1. Architectural Role

As a **Core Domain Module**, the `User` module manages the lifecycle of credentials and profiles. It
acts as a primary provider for the `Auth` and `Permission` modules while maintaining zero physical
coupling with other domain modules.

---

## 2. Core Components

### 2.1 Service Layer

- **`UserService`**: Orchestrates user account lifecycles, including creation, status toggling, and
  role-based initialization.
    - _Features_: Automated email verification for admins, welcome notification dispatching, and
      secure deletion guards.
    - **Composite Pattern**: Provides atomic creation of both `User` and `Profile` entities via the
      `createWithProfile()` method, delegating profile-specific logic to the `ProfileService`
      contract while maintaining a single transactional boundary for DX.
    - _Contract_: `Modules\User\Services\Contracts\UserService`.
- **`SuperAdminService`**: Specialized service for managing the highest-privileged system accounts.

### 2.2 Persistence Layer

- **`User` Model**: The central identity entity.
    - _Identities_: Supports configurable **UUID v4** primary keys (**[SYRS-NF-504]**).
    - _Traits_: Implements `HasRoles`, `HasStatus`, `HasUuid`, and `InteractsWithMedia`.
    - _Collections_: Manages the `COLLECTION_AVATAR` for profile pictures.

### 2.3 Notifications

- **`WelcomeUserNotification`**: A localized onboarding message that securely delivers initial
  credentials to new stakeholders.

---

## 3. Engineering Standards

- **Context-Aware Naming**: Prioritizes semantic clarity (e.g., `UserService`) while utilizing
  constants for roles and statuses to avoid magic values.
- **Privacy First**: Integrates with the `Log` module to ensure that identity changes are audited
  while sensitive fields remain masked.
- **i18n Compliance**: All administrative feedback and notification templates are fully localized in
  **ID** and **EN**.

---

## 4. Verification & Validation (V&V)

Quality is ensured through **Pest v4**:

- **Unit Tests**: Verifies model logic, such as initials generation and UUID configuration.
- **Feature Tests**: Validates business rules, including SuperAdmin protection and automatic role
  assignment.
- **Command**: `php artisan test modules/User`

---

_The User module provides the secure and localized identity foundation required for institutional
legitimacy._
