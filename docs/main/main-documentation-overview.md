# Main Documentation Overview

This document serves as the central entry point for the primary developer documentation within the
Internara project.

## Core Engineering Guides

- **[Architecture Guide](architecture-guide.md)**: A deep-dive into Internara's Modular Monolith
  architecture, explaining layers (UI, Services, Data) and the **Isolation Principle**.
- **[Feature Implementation Workflow](development-workflow.md)**: The standard operating procedure
  for building features, from planning to artifact synchronization.
- **[EloquentQuery Base Service](eloquent-query-service.md)**: Technical reference for our
  standardized service layer that reduces CRUD boilerplate.
- **[Shared Model Concerns](shared-concerns.md)**: Documentation for reusable Eloquent behaviors
  like **UUID** identity and **Status** management.
- **[Service Binding & Auto-Discovery](service-binding-auto-discovery.md)**: How the application
  automatically maps **Contracts** to concrete implementations.
- **[Module Provider Concerns](module-provider-concerns.md)**: Detailed guide on how modular service
  providers bootstrap their respective domains.

## Standards & Conventions

- **[Development Conventions](development-conventions.md)**: The authoritative guide on naming
  conventions, namespace rules (omitting `src`), and directory structures.
- **[Best Practices Guide](conceptual-best-practices.md)**: A conceptual overview of our engineering
  philosophy (Interface-First, Separation of Concerns).
- **[UI/UX Development Guide](ui-ux-development-guide.md)**: Global design principles and technical
  specifications for building interfaces with MaryUI/DaisyUI.
- **[Exception Handling Guide](exception-handling-guidelines.md)**: How we manage errors and user
  feedback consistently across the system.

## Authorization & Security

- **[Role & Permission Management](role-permission-management.md)**: The primary guide for
  implementing RBAC using Policies and the modular Permission system.
- **[Policy Patterns](policy-patterns.md)**: Standardized logic for protecting resources.
- **[Permission Seeders](permission-seeders.md)**: How foundational roles are defined and persisted.
- **[Permission UI Components](permission-ui-components.md)**: Shared interface elements for
  managing access.

## Core Project Artifacts

- **[Main README](../../README.md)**: The authoritative high-level entry point and status dashboard.
- **[Changelog](../../CHANGELOG.md)**: A comprehensive and human-readable record of all notable
  changes.
- **[App Info](../../app_info.json)**: The machine-readable identity of the application.

## Operations & Setup

- **[Installation Guide](installation-guide.md)**: Step-by-step instructions for local setup.
- **[Artisan Commands Reference](artisan-commands-reference.md)**: A catalog of custom modular
  generators and utility commands.
- **[Software Lifecycle](software-lifecycle.md)**: Our internal SDLC phases from concept to Released
  status.

---

_New to the project? Start with the **[Architecture Guide](architecture-guide.md)** and then follow
the **[Development Workflow](development-workflow.md)** to build your first feature._
