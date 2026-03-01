# Detailed Design (DD): Modular Internal Mechanics

This document provides the **Detailed Design (DD)** for the Internara system, standardized according to **ISO/IEC/IEEE 12207** (Software life cycle processes). It elaborates on the internal mechanisms, design patterns, and module-level orchestration identified in the **[System Architecture](system-architecture.md)**.

---

## 1. Design Philosophy: Domain Sovereignty

Internara’s detailed design is governed by the principle of **Domain Sovereignty**. Each module is a self-contained unit of logic, persistence, and presentation, interacting with other modules only through hardened interfaces.

### 1.1 The Aggregate Pattern (DDD)
Domain models are treated as **Aggregates**. Any state modification to an aggregate must occur through its designated **Orchestration Service**. This ensures that domain invariants (e.g., "A student cannot have more than one active internship") are enforced centrally.

---

## 2. Module-Level Orchestration Patterns

### 2.1 The Dual-Service Model (CQRS-Lite)
Internara distinguishes between **Read** and **Write** concerns at the service level:

1.  **Query Services (`EloquentQuery`)**: Optimized for data retrieval, filtering, and searching. They leverage the `EloquentQuery` base class to provide a standardized, type-safe API for the presentation layer.
2.  **Orchestration Services (`BaseService`)**: Responsible for complex business workflows, multi-entity transactions, and cross-module event dispatching.

### 2.2 Data Transfer Protocols (DTOs)
Complex inputs entering the Service Layer must be encapsulated in **Read-only DTOs** (PHP 8.4+). This prevents "Primitive Obsession" and ensures that data is validated and typed before reaching the domain logic.

---

## 3. Inter-Module Interaction Protocols

### 3.1 Service Contracts (The Interface Registry)
Every domain module exposes its capabilities through a **Contract** (Interface) located in `src/Services/Contracts/`.
- **Naming**: The interface name matches the service name (e.g., `RegistrationService`).
- **Discovery**: Automatically bound by the `BindServiceProvider`.

### 3.2 Event-Driven Side-Effects
Cross-module side-effects (e.g., notifying a mentor when a journal is submitted) are handled asynchronously via **Domain Events**.
- **Payload**: Must be a "Lightweight Payload" (UUID only).
- **Consistency**: Follows **Eventual Consistency** principles.

---

## 4. Presentation Layer Mechanics (Livewire)

Livewire components act as **Boundary Controllers**.
- **State Management**: Components use `Forms` (Livewire v3) to encapsulate validation and property management.
- **Communication**: Components communicate with the Service Layer exclusively through Contracts.
- **UI Composition**: Cross-module UI integration is achieved through the **Slot Injection** pattern, preventing hard-coded dependencies between views.

---

## 5. Persistence & Identity Invariants

- **Identity**: Universal **UUID v4** mapping via the `HasUuid` concern.
- **Auditing**: Automatic capture of state changes via `InteractsWithActivityLog`.
- **Soft Deletes**: Mandatory for all high-value domain entities to ensure forensic auditability.
