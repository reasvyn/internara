# Internal Engineering - Table of Contents

This index lists all technical guides intended for internal developers and code contributors. It
defines the standards, patterns, and workflows used to build Internara.

---

## 1. Architecture & Core Concepts

- **[Internara Specs](internara-specs.md)**: **Single Source of Truth** - The authoritative
  specification for the entire project.
- **[Architecture Guide](architecture-guide.md)**: Deep-dive into Modular Monolith layers,
  bootstrapping, and auto-discovery.
- **[Foundational Module Philosophy](foundational-module-philosophy.md)**: Categorization of modules
  (Core, Shared, UI, etc.).
- **[Version Management Guide](version-management.md)**: Standards for SemVer, Maturity Stages, and
  Support Policies.

## 2. Standards & Conventions

- **[Development Conventions](development-conventions.md)**: Authorities on naming, namespaces,
  service layer patterns (EloquentQuery), and model concerns.
- **[UI/UX Development Guide](ui-ux-development-guide.md)**: Building interfaces with
  MaryUI/DaisyUI.
- **[Exception Handling Guidelines](exception-handling-guidelines.md)**: Standardized error
  reporting.

## 3. Engineering Workflows & Blueprints

- **[Development Workflow](development-workflow.md)**: Feature engineering lifecycle and
  documentation standards.
- **[Blueprints Guidelines](blueprints-guidelines.md)**: Standards for managing system evolution via
  architectural blueprints.
- **[Application Blueprints Index](blueprints/table-of-contents.md)**: Pre-development architectural
  blueprints.
- **[Software Lifecycle](software-lifecycle.md)**: SDLC phases from concept to release.
- **[Release Guidelines](release-guidelines.md)**: Versioning standards and deployment protocols.
- **[GitHub Protocols](github-protocols.md)**: Standards for collaboration, branching, and issue
  management.
- **[Artisan Commands Reference](artisan-commands-reference.md)**: Catalog of modular generators.

## 4. Security & Authorization

- **[Role & Permission Management](role-permission-management.md)**: Implementing modular RBAC,
  seeders, and UI components.
- **[Policy Patterns](policy-patterns.md)**: Logic for protecting resources.

## 5. Quality Assurance

- **[Testing Guide](testing-guide.md)**: Standards for Unit and Feature testing with Pest.

---

_This directory is for engineering use only. Public-facing guides can be found in the
**[Main Hub](../main/main-documentation-overview.md)**._
