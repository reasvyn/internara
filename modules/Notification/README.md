# Notification Module

The `Notification` module provides a unified infrastructure for dispatching alerts, messages, and
real-time UI feedback within the Internara ecosystem. It centralizes multi-channel communication to
ensure Instructors, Students, and Supervisors remain informed about critical system events.

> **Governance Mandate:** This module implements the Communication and Information Flow standards
> required by the authoritative
> **[System Requirements Specification](../../docs/internal/system-requirements-specification.md)**.
> It facilitates proactive system interaction as defined in the architecturalPROCESS VIEW.

---

## 1. Architectural Role

As a **Public Module**, the `Notification` module provides a standardized **Notifier** service that
can be consumed by any domain module to trigger UI alerts or background notifications without domain
coupling.

---

## 2. Core Components

### 2.1 Service Layer

- **`Notifier`**: The primary service for dispatching UI-level notifications. It leverages
  Livewire's event bus to power real-time feedback (e.g., `mary-toast`).
    - _Features_: Support for Success, Error, Warning, and Info alerts.
    - _Contract_: `Modules\Notification\Contracts\Notifier`.

### 2.2 Global Protocols

- **Livewire Integration**: Automatically detects active Livewire sessions to dispatch browser-level
  events.
- **i18n Compliance**: All notification messages must be resolved via translation keys before being
  passed to the Notifier.

---

## 3. Engineering Standards

- **Zero Magic Values**: Utilizes `TYPE_*` constants for all notification categories.
- **Brevity & Context**: Services and contracts follow the refined naming rules (e.g., `Notifier`
  instead of `NotificationService`).
- **Decoupled Orchestration**: Provides the bridge between business logic side-effects and frontend
  visualization.

---

## 4. Verification & Validation (V&V)

Quality is ensured through **Pest v4**:

- **Unit Tests**: Verifies event dispatching logic and default type handling.
- **Integration**: Validates seamless communication with the Livewire event bus.
- **Command**: `php artisan test modules/Notification`

---

_The Notification module ensures that Internara remains a responsive and informative environment for
all stakeholders._
