# Main Documentation - Table of Contents

This index lists all primary developer guides, ordered by the logical development flow.

## 1. Getting Started

- **[Main Documentation Overview](main-documentation-overview.md)**: Entry point and summary of core
  guides.
- **[Installation Guide](installation-guide.md)**: Step-by-step setup instructions for local
  development.

## 2. Architecture & Concepts

Understanding the "Why" and "How" of the system structure.

- **[Architecture Guide](architecture-guide.md)**: High-level guide to Internara's modular
  architecture (Layers, Communication).
- **[Module Inventory](modules/table-of-contents.md)**: Purpose and categorization of all
  application modules.
- **[EloquentQuery Base Service](eloquent-query-service.md)**: Technical reference for the
  standardized service layer implementation.
- **[Module Structure Overview](foundational-module-philosophy.md)**: Purpose of Core, Shared,
  Support, and Domain modules.
- **[Best Practices Guide](conceptual-best-practices.md)**: Conceptual overview of principles
  (Interface-First, Separation of Concerns).

## 3. Standards & Conventions

Rules to follow while writing code.

- **[Development Conventions](development-conventions.md)**: Coding standards, namespace rules, and
  directory structures.
- **[Development Lifecycle & Versioning Strategy](../versions/versions-overview.md#development-lifecycle--versioning-strategy)**:
  The authoritative guide on versioning and release management.
- **[Shared Model Traits](shared-traits.md)**: Reusable behaviors for Eloquent models (UUID,
  Status).
- **[UI/UX Development Guide](ui-ux-development-guide.md)**: Global design principles and standards.
- **[UI Module TOC](ui/table-of-contents.md)**: Index of shared UI components and design system.
- **[Exception Handling Guide](exception-handling-guidelines.md)**: Standardized error handling and
  user feedback.

## 4. Core System Implementation

Deep dives into specific architectural components.

- **[ManagesModuleProvider Trait](module-provider-concerns.md)**: Standardized module bootstrapping
  process.
- **[Service Binding & Auto-Discovery](service-binding-auto-discovery.md)**: How dependency
  injection works automatically.
- **[Role & Permission Management Guide](role-permission-management.md)**: Implementing RBAC using
  Policies and Permissions.
- **[Permission Seeders](permission-seeders.md)**: Foundational roles and permissions.
- **[Permission UI Components](permission-ui-components.md)**: Shared components for roles and
  permissions.
- **[Policy Patterns](policy-patterns.md)**: Standardized authorization logic patterns.

## 5. Daily Development Workflow

Practical guides for building features.

- **[Software Development Life Cycle](software-lifecycle.md)**: Standard SDLC phases tailored for
  Internara.
- **[Development Workflow](development-workflow.md)**: Standard operating procedures for planning,
  coding, and verifying features.
- **[Artisan Commands Reference](artisan-commands-reference.md)**: Reference for generating modules
  and components.

## 6. Quality Assurance

- **[Testing Guide](testing-guide.md)**: Comprehensive guide on writing Unit and Feature tests with
  Pest.

## 7. Extensions & Deep Dives

- **[Package Integration TOC](packages/table-of-contents.md)**: Configuration of third-party
  packages.
- **[Advanced Guides TOC](advanced/table-of-contents.md)**: Custom module generation and advanced
  topics.

---

[← Back to Root TOC](../table-of-contents.md)
