# Conceptual Best Practices

This guide provides a high-level overview of the core conceptual best practices and foundational
principles governing development on the Internara project. These are the overarching ideas that
developers should always keep in mind.

---

## 1. Core Architecture: Modular Monolith

Internara is built as a **Modular Monolith**, encapsulating distinct business domains into
self-contained **Modules**. This architecture promotes organization, maintainability, and
scalability by enforcing strict separation of concerns and clear communication patterns.

For a comprehensive understanding of Internara's modular structure, layered architecture (UI,
Services, Models), inter-module communication, and more, refer to the
**[Architecture Guide](architecture-guide.md)**. For specific details on module roles and
portability standards, refer to the
**[Foundational Module Philosophy](foundational-module-philosophy.md)**.

---

## 2. Development & Coding Principles

Internara development adheres to a set of core principles that guide all coding practices, ensuring
consistency, maintainability, and quality across the codebase. For detailed, actionable rules and
specific conventions, refer to the **[Development Conventions Guide](development-conventions.md)**.

Key principles include:

- **English Only:** All code, comments, and documentation must be in English.
- **Descriptive Naming:** Use clear and descriptive names for all code elements.
- **DRY Principle:** Promote code reuse and avoid unnecessary duplication.
- **Strict Adherence to Structure:** Follow existing directory structures and conventions; avoid
  creating new base folders without approval.
- **Comprehensive PHPDoc:** Document all classes and methods thoroughly, explaining _why_ complex
  logic exists.
- **PHP & Laravel Standards:** Follow established PHP and Laravel best practices, as detailed in the
  Development Conventions.

---

## 3. Testing Philosophy

Internara adopts a robust testing philosophy to ensure code quality and application stability. For a
comprehensive guide on testing strategies, framework usage, and conventions, refer to the
**[Testing Guide](testing-guide.md)**.

Key testing principles include:

- **Test First (When Practical):** Prioritize writing tests with new features.
- **Comprehensive Coverage:** All new features/bug fixes require relevant tests.
- **Maintain Test Suites:** Existing tests are critical and must not be removed; update them to
  reflect changes.
- **Pest Framework:** All tests **must** be written using the Pest testing framework.

---

## 4. UI/UX Design Principles

Internara's user interface is designed with a strong focus on aesthetics and usability. For detailed
guidelines on UI/UX principles, design standards, and technical specifications for frontend
implementation, refer to the **[UI/UX Development Guide](ui-ux-development-guide.md)**.

Key UI/UX design principles include:

- **Minimalist & Functional:** Prioritize a clean, uncluttered aesthetic focused on task completion.
- **User-Friendly & Professional:** Intuitive interfaces conveying trust and competence.
- **Consistent Design:** Adhere to defined typography, layout, spacing, and iconography guidelines.
- **DaisyUI Framework:** All new UI development **must** leverage DaisyUI components for
  consistency.

---

## 5. Vision & Technical Goals

- **Primary Goal:** Rapidly build a **minimalist, structured, and secure** Full-Cycle Internship
  MVP.
- **Technology Stack:** Built on the **TALL Stack** (Tailwind CSS v4, Alpine.js, Laravel v12,
  Livewire v3).
- **Performance Target:** Core page load time of **less than 1.5 seconds**.
- **Stability Target:** Critical bugs in the full internship cycle must be **100% free** before
  internal testing.
- **Default Localization Language:** Indonesian (`id`).

---

**Navigation**

[← Previous: Service Binding & Auto-Discovery](service-binding-auto-discovery.md) |
[Next: Development Conventions →](development-conventions.md)
