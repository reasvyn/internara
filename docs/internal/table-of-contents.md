# Internal Engineering - Table of Contents

This index lists all technical guides intended for internal developers and code contributors. It defines the standards, patterns, and workflows used to build Internara.

---

## 1. Architecture & Core Concepts

- **[Architecture Guide](architecture-guide.md)**: Deep-dive into Modular Monolith layers and isolation rules.
- **[Foundational Module Philosophy](foundational-module-philosophy.md)**: Categorization of modules (Core, Shared, UI, etc.).
- **[EloquentQuery Service](eloquent-query-service.md)**: Standardized service layer implementation.
- **[Shared Model Concerns](shared-concerns.md)**: Reusable behaviors (UUID, Status, Scoping).
- **[Module Provider Concerns](module-provider-concerns.md)**: Standardized module bootstrapping logic.
- **[Service Binding & Auto-Discovery](service-binding-auto-discovery.md)**: Automatic dependency injection mechanics.

## 2. Standards & Conventions

- **[Development Conventions](development-conventions.md)**: Authorities on naming, namespaces, and directory structures.
- **[Conceptual Best Practices](conceptual-best-practices.md)**: Philosophical foundations of the project.
- **[UI/UX Development Guide](ui-ux-development-guide.md)**: Building interfaces with MaryUI/DaisyUI.
- **[Exception Handling Guidelines](exception-handling-guidelines.md)**: Standardized error reporting.
- **[Writing Guide](writing-guide.md)**: Standards for technical documentation.

## 3. Engineering Workflows

- **[Development Workflow](development-workflow.md)**: Feature engineering lifecycle (Plan -> Build -> Sync).
- **[Software Lifecycle](software-lifecycle.md)**: SDLC phases from concept to release.
- **[Release Guidelines](release-guidelines.md)**: Versioning standards and deployment protocols.
- **[Artisan Commands Reference](artisan-commands-reference.md)**: Catalog of modular generators.

## 4. Security & Authorization

- **[Role & Permission Management](role-permission-management.md)**: Implementing modular RBAC.
- **[Policy Patterns](policy-patterns.md)**: Logic for protecting resources.
- **[Permission Seeders](permission-seeders.md)**: Bootstrapping foundational access.
- **[Permission UI Components](permission-ui-components.md)**: Shared elements for access control.

## 5. Quality Assurance

- **[Testing Guide](testing-guide.md)**: Standards for Unit and Feature testing with Pest.

---

_This directory is for engineering use only. Public-facing guides can be found in the **[Main Hub](../main/main-documentation-overview.md)**._
