# Spatie Honeypot: Automated Bot Protection

This document formalizes the integration of the `spatie/laravel-honeypot` package, which powers the
**Bot & Spam Protection** baseline for the Internara project. It establishes the technical protocols
required to silently trap and reject non-human interactions at system boundaries.

---

## 1. Rationale: Systemic Perimeter Defense

Internara prioritizes the integrity of its administrative and authentication routes.

- **Objective**: Prevent automated form submissions from bots that bypass standard client-side
  validation.
- **Protocol**: Implementation of a hidden "trap" field that, if filled, identifies the submitter as
  a bot.

---

## 2. Implementation Invariants

### 2.1 UI Layer Integration

The honeypot must be present on every public-facing form and administrative wizard.

- **Component**: Utilize the `<x-honeypot />` Blade component.
- **Location**: Foundational layouts (`Auth`, `Setup`).

### 2.2 Global Protection

Protection is enforced at the middleware layer to ensure that any request triggering the honeypot is
immediately discarded without technical feedback.

- **Middleware**: `Spatie\Honeypot\ProtectAgainstSpam`.
- **Behavior**: Responses to bot submissions must be silent to prevent bots from identifying the
  entrapment mechanism.

---

## 3. Configuration & Customization

The configuration is managed within the `Shared` module to ensure system-wide consistency.

- **Randomization**: The name of the honeypot field is randomized on every request to prevent bots
  from building static avoidance patterns.

---

_By strictly governing the honeypot engine, Internara ensures a high-fidelity perimeter defense that
protects system resources and administrative integrity._
