# Auth Module

The `Auth` module manages user authentication, providing secure access to the platform.

> **Spec Alignment:** This module implements the security standards (Login via email/password)
> mandated in the **[Internara Specs](../../docs/internal/internara-specs.md)** (Section 10.6).

## Purpose

- **Access Control:** Verifies user identity before granting access to role-based workspaces.
- **Onboarding:** Handles registration and role assignment.
- **Security:** Enforces sensitive data protection and session management.

## Key Features

- **Reactive Auth:** Livewire-powered login and registration.
- **Mobile-First:** Auth pages are optimized for mobile devices.
- **i18n Support:** All auth-related messages and validation errors are fully localized (ID/EN).
- **Role Redirection:** Intelligently routes users (Instructor, Student, etc.) to their specific
  environments.

---

_The Auth module is the gatekeeper of Internara, ensuring a secure and localized entry point._
