# Auth Module

The `Auth` module provides the secure entry point and session management infrastructure for the
Internara ecosystem. It handles user authentication, registration, password recovery, and email
verification while ensuring that users are intelligently routed to their respective domain-specific
environments.

> **Governance Mandate:** This module strictly adheres to the **3S Doctrine** (Secure, Sustain,
> Scalable) and the **Modular Domain-Driven Design (DDD)** architecture. All implementations must
> preserve its Bounded Context isolation and maintain Documentation Parity (Sync or Sink).

---

## 1. Architectural Role

As a **Foundational Public Module**, the `Auth` module acts as the system's "gatekeeper." It
verifies identities and coordinates with the `User` and `Permission` modules to establish the
initial security context for every session.

---

## 2. Core Components

### 2.1 Service Layer

- **`AuthService`**: Manages the technical lifecycle of an authentication session.
- _Features_: Multi-role entry (Student, Teacher, Mentor, Admin), session hardening, secure
  registration, and hashed verification tokens.
- _Contract_: `Modules\Auth\Services\Contracts\AuthService`.
- _API_: `login()`, `logout()`, `verifyEmail(id, hash)`.
- **`RedirectService`**: Determines the appropriate destination URL after authentication based on
  the user's active roles and verification status.
- _Contract_: `Modules\Auth\Services\Contracts\RedirectService`.

### 2.2 Global Concerns

- **`RedirectsUsers`**: A reusable concern for controllers and Livewire components that leverages
  the `RedirectService` to ensure consistent routing logic. Resides in `src/Concerns/`.

---

## 3. Engineering Standards

- **Zero Magic Values**: Utilizes standardized `Symfony\Component\HttpFoundation\Response` constants
  for all authentication-related HTTP status codes.
- **Privacy Masking**: Automatically masks identifiers (emails/usernames) in technical logs to
  prevent information leakage during failed login attempts via the `Masker` utility.
- **Context-Aware Redirection**: Redirection logic is centralized, ensuring that Instructors,
  Students, and Administrators are always funneled into their authorized workspaces.
- **DDD-First**: All authentication flows are verified against role-based domain requirements.

---

## 4. Verification & Validation (V&V)

Security is verified through **Pest v4**:

- **Domain Tests**: Verifies core authentication logic and session security in isolation.
- **Application Tests**: Validates registration workflows, role assignments, and email verification
  redirects.
- **Security Audit**: Ensures that unverified users are strictly blocked from protected dashboards.
- **Command**: `php artisan test modules/Auth`

---

_The Auth module ensures that every interaction in Internara begins with a verified and secure
identity._
