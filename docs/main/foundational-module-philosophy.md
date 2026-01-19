# Foundational Module Philosophy: Structural Hierarchy

To maintain a resilient and scalable Modular Monolith, Internara categorizes its foundational
modules based on their relationship to business logic and their level of **Portability**. This
philosophy ensures that we don't accidentally "tangle" universal utilities with specific business
rules.

---

## 1. Portability & Hierarchy Matrix

We classify modules into four distinct roles. This classification dictates what a module can depend
on and whether it can be easily reused in another project.

| Category    | Role                  | Portability                   | Example                     |
| :---------- | :-------------------- | :---------------------------- | :-------------------------- |
| **Shared**  | Universal Toolbox     | **High (Mandatory Portable)** | `HasUuid`, `EloquentQuery`  |
| **Core**    | Business Blueprint    | **Low (Business-Specific)**   | `AcademicYear`, `BaseRoles` |
| **Support** | Infrastructure Bridge | **Low (Project-Specific)**    | `ModuleGenerators`          |
| **UI**      | Design System         | **Low (Brand-Specific)**      | `DashboardLayout`, `Toasts` |

---

## 2. Shared Module: The Portable Foundation

The `Shared` module is the "Engine Room." It contains components that are **completely agnostic** of
Internara's domain.

- **Requirement**: Components here must be project-independent. If you move `Shared` to a new
  Laravel project, it should work out of the box.
- **Contents**:
    - **Concerns**: `HasUuid`, `HasStatuses`, `HasAuditLog`.
    - **Base Classes**: `EloquentQuery`, `BaseServiceContract`.
    - **Utilities**: String formatters, geometric calculators, etc.

## 3. Core Module: The Business Blueprint

The `Core` module is the "Glue." it encapsulates the architectural building blocks that define
**what Internara is**.

- **Purpose**: It provides the foundational data (e.g., Roles, Settings) that domain modules need to
  function.
- **Contents**:
    - Global Permission definitions.
    - Academic Year scoping logic.
    - System-wide constants.

## 4. Support Module: The Infrastructure Bridge

The `Support` module handles **Development & Operational** utilities.

- **Purpose**: To provide tools that keep domain modules clean from infrastructure "noise."
- **Contents**:
    - Custom Artisan Generators.
    - Vite module loaders.
    - Deployment scripts and environment auditors.

## 5. UI Module: The Visual Identity

The `UI` module is the "Skin." It is the single source of truth for the Internara design system.

- **Purpose**: Encapsulates styling (Tailwind), interactivity (Alpine/Livewire), and accessibility.
- **Contents**:
    - Standardized Layouts (`Auth`, `Dashboard`).
    - Design System Components (`Button`, `Card`, `Modal`).
    - Branding assets (Logos, Icons).

---

## 6. Domain Modules: The Heart of the App

Modules like `User`, `Internship`, or `Attendance` represent the actual business functionality.

- **Best Practice**: Domain modules should strive for **High Portability**.
- **Dependency Rule**: They should primarily depend on `Shared`. If they need `Core` data, they must
  access it through **Contracts** or framework-level **Policies/Gates** to avoid tight coupling with
  the concrete `Core` implementation.

---

_Understanding this hierarchy prevents "Spaghetti Modularity." By respecting these boundaries, we
ensure that Internara remains clean, testable, and future-proof._
