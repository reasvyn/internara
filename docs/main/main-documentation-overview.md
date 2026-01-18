# Main Documentation Overview

This document serves as the central entry point for the primary developer documentation within the
Internara project.

## Core Documentation

- **[Architecture Guide](architecture-guide.md)**: Provides a high-level, developer-friendly guide
  to Internara's modular architecture, explaining the core concepts, layers (UI, Services,
  Repositories, Entities), and module communication best practices.
- **[EloquentQuery Base Service](eloquent-query-service.md)**: Technical reference for the
  standardized service layer implementation used for model interactions.
- **[Module Inventory](modules/table-of-contents.md)**: Comprehensive list and technical guides for
  all modules, now located within their respective directories.
- **[ManagesModuleProvider Trait](module-provider-concerns.md)**: Guide on the standardized
  bootstrapping process for modular service providers.
- **[Shared Model Traits](shared-traits.md)**: Reference for reusable Eloquent traits like UUID and
  Status management.
- **[Service Binding & Auto-Discovery](service-binding-auto-discovery.md)**: Detailed documentation
  on the automated dependency injection system, configuration, caching, and manual overrides.
- **[Module Structure Overview](foundational-module-philosophy.md)**: Provides an overview of the
  Core, Shared, Support, and Domain modules, detailing their purpose and contents.
- **[Best Practices Guide](conceptual-best-practices.md)**: A conceptual overview of core
  architectural principles, development conventions, testing philosophy.
- **[UI Module TOC](ui/table-of-contents.md)**: Comprehensive guide detailing UI/UX principles,
  shared components, and technical specifications for frontend implementation.
- **[Development Conventions](development-conventions.md)**: Outlines the coding and development
  conventions for the project, ensuring consistency and maintainability, including important
  namespace conventions for modular development.
- **[Role & Permission Management Guide](role-permission-management.md)**: The primary guide for
  creating, managing, and using Roles and Permissions, detailing the conventions and separation of
  concerns between modules.
- **[Permission Seeders](permission-seeders.md)**: Details on the foundational roles and permissions
  seeded during system setup.
- **[Permission UI Components](permission-ui-components.md)**: Documentation for shared UI elements
  related to authorization.
- **[Development Workflow](development-workflow.md)**: A practical, step-by-step guide to
  implementing features within the architecture, from creating modules to writing services and
  tests.
- **[Testing Guide](testing-guide.md)**: A comprehensive guide covering the project's testing
  philosophy, Pest framework usage, test directory structure, writing tests, and running tests.
- **[Artisan Commands Reference](artisan-commands-reference.md)**: A comprehensive list of all
  available Artisan commands within the Internara application, categorized for easy reference.
- **[Installation Guide](installation-guide.md)**: Provides step-by-step instructions to set up the
  Internara application for local development.
- **[Exception Handling Guide](exception-handling-guidelines.md)**: Details the philosophy, key
  classes, and best practices for managing exceptions consistently across the application.

## Sub-Sections

- **[Package Integration Overview](packages/packages-overview.md)**: Configuration and usage of core
  dependencies like Livewire, Laravel Modules, and Spatie packages.
- **[Advanced Guides Overview](advanced/advanced-overview.md)**: Advanced development scenarios and
  customizations.

---

**Navigation**

[← Previous: Project Overview](../project-overview.md) |
[Next: Table of Contents](table-of-contents.md)
