# Architecture Description: The Internara Modular Monolith

This document provides a formal **Architecture Description (AD)** for the Internara system,
standardized according to **ISO/IEC 42010** and **ISO/IEC 12207**. It defines the structural
framework, architectural patterns, and design principles required to ensure system maintainability,
scalability, and modular integrity.

> **SSoT Alignment:** This architecture implements the technical requirements mandated by the
> authoritative **[Internara Specs](../internal/internara-specs.md)**. All architectural decisions
> are mapped to the product goals and constraints defined therein.

---

## 1. Architectural Philosophy & Rationale

Internara utilizes a **Modular Monolith** pattern to balance the benefits of domain isolation with
the operational simplicity of a unified deployment artifact.

### 1.1 Pragmatic Modularity (Component Decomposition)

The system is decomposed into self-contained business domains (Modules) to manage cognitive
complexity.
- **Rationale**: Reduces coupling and facilitates independent evolution of business rules.
- **Decomposition Criteria**: A module is defined by a distinct domain lifecycle, specialized data
  requirements, and clear functional boundaries (e.g., `Internship`, `Assessment`).

### 1.2 Service-Oriented Business Logic (The Brain)

Business logic is centralized in the **Service Layer**, adhering to the **Single Responsibility
Principle (SRP)**.
- **Presentation Logic**: Limited to request handling and UI state management (Controllers and
  Livewire).
- **Domain Logic**: Encapsulated within Services to ensure a single source of truth.
- **Persistence Logic**: Encapsulated within Eloquent Models for data mapping and relationships.

### 1.3 Principle of Explicit Dependency

To ensure system predictability, Internara prioritizes explicit dependency management.
- **Action**: Prefer **Constructor-based Dependency Injection** over Facades or global helpers.
- **Contract-First Design**: Utilize **Service Contracts** (Interfaces) to define boundaries and
  facilitate testing via substitution (Liskov Substitution Principle).

---

## 2. Logical View: Layered Architecture

Each module adheres to a strict 3-tier internal structure to preserve the separation of concerns.

### 2.1 Presentation Layer (UI & Interaction)

- **Technology**: Livewire Components (TALL Stack).
- **Rule**: No business logic implementation; all operations are delegated to the Service Layer.
- **Dependency**: Injected via `boot()` or `mount()` lifecycle methods.

### 2.2 Domain Layer (Services & Contracts)

- **Location**: `src/Services/`.
- **Base Pattern**: Extends `Shared` base classes (e.g., `EloquentQuery`) for standardized data
  orchestration.
- **I/O Contract**: Services accept primitive types or DTOs and return models or collections.

### 2.3 Persistence Layer (Data Mapping)

- **Location**: `src/Models/`.
- **Relationship Constraint**: Cross-module relationships are handled via Services or Events;
  Models only define internal domain relationships.

---

## 3. Development View: Modular Infrastructure

### 3.1 The Foundational Framework

Technical cross-cutting concerns are managed through a hierarchical layer of foundational modules:
- **Shared**: Universal, project-agnostic utilities and base concerns (e.g., `HasUuid`).
- **Core**: Business-specific building blocks (e.g., RBAC, Academic Years).
- **Support**: Operational and infrastructure utilities (e.g., Generators).
- **UI**: Standardized design system and layouts (Tailwind v4).

### 3.2 Dependency Injection & Auto-Discovery

Internara utilizes an automated mapping system to resolve **Service Contracts** to their
concrete implementations based on directory structure.
- **Contract Location**: `Services/Contracts/` (e.g., `UserService.php`).
- **Implementation Location**: `Services/` (e.g., `UserService.php`).
- **Auto-Discovery**: Handled via the `Shared` module's bootstrapping trait, ensuring zero-config
  binding for standardized layouts.

---

## 4. Process View: System Communication

### 4.1 Synchronous Communication (Service-to-Service)

Cross-module data requests must be performed via **Service Contracts**.
- **Constraint**: Direct instantiation of concrete classes from external modules is prohibited to
  maintain domain isolation.

### 4.2 Asynchronous Communication (Event-Driven)

Side-effects across domain boundaries are handled via Laravel's **Event/Listener** system.
- **Rationale**: Decouples the primary action from its secondary consequences (e.g.,
  `InternshipCompleted` triggering a notification).

---

## 5. Security & Isolation Protocols

### 5.1 Strict Domain Isolation

- **No Direct Access**: Modules must never interact with the database tables or models of another
  module.
- **Contractual Boundaries**: All inter-module communication is restricted to the Services/Utilities
  layer using designated Contracts.

### 5.2 Database Integrity

- **No Physical Foreign Keys**: To ensure modular portability, referential integrity across modules
  is managed at the **Service Layer** using indexed UUID columns.
- **UUID Identity**: All entities utilize UUIDs for globally unique identification.

---

_Adherence to this Architecture Description ensures systemic integrity and compliance with the
Internara Specs. Every technical modification must be validated against these principles to prevent
architectural decay._