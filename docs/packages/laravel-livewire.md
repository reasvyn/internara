# Laravel Livewire: Presentation Layer Orchestration

This document formalizes the integration of **Laravel Livewire**, which serves as the primary engine
for high-fidelity, reactive user interfaces within the Internara project. It defines the technical
protocols required to maintain the **Thin Component** invariant and architectural purity.

---

## 1. Presentation View (Structural Invariants)

Livewire components are restricted to the orchestration of UI state and user interaction, as defined
in the **[Architecture Description](../architecture.md)**.

### 1.1 The Thin Component Mandate

- **Responsibility**: Components must restrict operations to input capture, local state management
  (e.g., modal visibility), and event orchestration.
- **Logic Delegation**: Implementation of domain rules or persistence logic within Livewire is
  strictly prohibited. All operations must be delegated to the **Service Layer**.

### 1.2 Dependency Injection Protocol (Life Cycle Rule)

To ensure compatibility with the Livewire hydration baseline, constructor-based injection is
forbidden.

- **Protocol**: Inject **Service Contracts** exclusively via the `boot()` or `mount()` life cycle
  methods.
- **Rationale**: Prevents hydration failures and ensures that resolved dependencies are persistent
  across state-altering requests.

---

## 2. Technical Integration Baselines

### 2.1 Modular Component Discovery

Internara utilize semantic namespaces for cross-module component invocation.

- **Convention**: `@livewire('module::component-name')`.
- **Bridge**: Facilitated by the `mhmiton/laravel-modules-livewire` orchestrator.

### 2.2 Cross-Module UI Orchestration (Events)

To maintain domain isolation, inter-component communication across module boundaries is restricted
to asynchronous browser events.

- **Dispatcher**: `$this->dispatch('domain-event')`.
- **Listener**: Utilizes the `#[On('domain-event')]` attribute to trigger localized state updates.

---

## 3. Human-Centered Design Standards

Livewire implementation must demonstrate compliance with **ISO 9241-210** and the **Mobile-First**
mandate defined in the **[UI/UX Development Guide](../user-interface-design.md)**.

- **Responsiveness**: Utilization of Tailwind v4 utility classes for progressive enhancement.
- **Localization**: Native support for translation keys; zero hard-coding of user-facing text.
- **V&V Mandatory**: Component behavior must be verified via **`composer test`** using the Livewire
  testing utilities.

---

_By strictly governing the presentation layer engine, Internara ensures a high-performance,
maintainable, and architecturally resilient user experience._
