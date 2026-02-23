# PHP Flasher: Standardized Notification Delivery

This document formalizes the integration of the `php-flasher/flasher-laravel` package, which powers
the **UI Feedback & Notification** baseline for the Internara project. It defines the technical
protocols required to deliver responsive, localized, and consistent notifications across all domain
modules.

---

## 1. Rationale: Standardized Feedback

Internara prioritizes clear and immediate feedback for all user actions.

- **Objective**: Provide a unified, role-aware notification system for transient UI feedback.
- **Service**: Orchestrated through the `Notification` module's **Notifier** service.

---

## 2. Implementation Invariants

### 2.1 UI Layer Integration

Notifications must be rendered via the standardized **maryUI** components to ensure **Mobile-First**
fluidity and visual consistency with the **Instrument Sans** identity.

- **Component**: Dispatched via `flash()->success(...)`, `flash()->error(...)`, etc.
- **Localization**: All notification content must be resolved via translation keys.

### 2.2 Livewire Synchronization

Integrate with the Livewire lifecycle to ensure notifications persist or disappear correctly during
SPA transitions.

- **Event**: Utilize browser events for immediate feedback within reactive components.

---

## 3. Configuration & Customization

The configuration is managed within the `Notification` module to ensure system-wide consistency and
minimalistic delivery.

- **Positioning**: Default to top-right on desktop and top-center on mobile devices.
- **Duration**: Transient feedback should persist for **3000ms** to ensure readability without
  blocking the user flow.

---

_By strictly governing the notification engine, Internara ensures a professional and accessible
experience that keeps stakeholders informed of system state and action outcomes._
