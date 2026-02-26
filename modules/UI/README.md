# UI Module

The `UI` module serves as the authoritative, "headless" source of truth for Internara's visual
language and shared frontend components. it encapsulates the design system, interactive elements,
and presentation layouts without containing business logic or data persistence.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/dev/specs.md)**. All implementation must
> adhere to the **[Coding Conventions](../../docs/dev/conventions.md)**.

---

## 1. Architectural Role

As a **Public Module**, the `UI` module provides the shared presentation layer for all domain
modules. It relies on the **TALL Stack** (Tailwind, AlpineJS, Livewire, Laravel) with **Volt** for
reactive components, transitioning towards a **Native TALL** implementation using **DaisyUI** while
minimizing third-party library dependency.

---

## 2. Key Features

### 2.1 Design System

- **Centralized Styling**: Configuration for Tailwind v4 and DaisyUI themes.
- **Typography**: Enforces the use of **Instrument Sans** as the primary font (**[SYRS-NF-402]**).
- **Theming**: Native support for Light/Dark modes and responsive layouts.
- **Evolutionary UI**: Adherence to the **UI library minimization** strategy to ensure absolute
  design control.

### 2.2 Component Library

- **Livewire & Volt Components**: Shared interactive elements like the `LanguageSwitcher`.
- **`ManagesRecords` Concern**: A standardized trait for CRUD-heavy Livewire components, providing
  automated pagination, searching, and sorting logic aligned with the `EloquentQuery` pattern.

### 2.3 Cross-Module View Orchestration (Slot Injection)

The UI module provides the infrastructure for zero-coupling UI integration:

- **`SlotRegistry`**: A central registry where modules can register their own UI fragments (e.g.,
  sidebar links, dashboard widgets).
- **`SlotManager`**: Handles the secure rendering of registered components into named slots within
  the layout.
- **`SlotRender`**: A Blade component (`<x-ui::slot-render name="..." />`) used to mark injection
  points in global layouts.

---

## 3. Engineering Standards

- **Zero Coupling**: Strictly presentation-focused. D Domain logic is prohibited.
- **Mobile-First**: All layouts and components must default to mobile-responsive design
  (**[SYRS-NF-401]**).
- **i18n Infrastructure**: Facilitates the **Language Switcher** and ensures all presentation text
  is localized.
- **Zero Magic Values**: UI configurations (like supported locales and icons) are managed via
  `config/ui.php`.

---

## 4. Verification & Validation (V&V)

Presentation integrity is verified through **Pest v4**:

- **Unit Tests**: Verifies the `SlotRegistry` logic and injection accuracy.
- **Feature Tests**: Validates interactive components like the `LanguageSwitcher`.
- **Visual Audit**: Manual verification of mobile responsiveness and theme consistency.
- **Command**: `php artisan test modules/UI`

---

_The UI module ensures that Internara provides a professional, accessible, and seamless interface
for all stakeholders._
