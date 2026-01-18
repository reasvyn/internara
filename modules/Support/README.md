# Support Module

The `Support` module acts as a bridge between the Laravel framework, third-party packages, and the
Internara application. It is used for infrastructure integrations that are specific to the project's
requirements but don't belong in the core business logic.

## Purpose

- **Integration:** Wraps and configures third-party libraries for modular use.
- **Abstraction:** Provides a clean interface for infrastructure-heavy tasks.
- **Decoupling:** Prevents domain modules from depending directly on complex external
  implementations.

## Key Features

- **Infrastructure Providers:** Registers project-wide service providers for external packages.
- **Third-Party Wrappers:** Contains specific configurations or classes that adapt external tools to
  Internara's standards.

---

**Navigation** [← Back to Module TOC](../../docs/main/modules/table-of-contents.md)
