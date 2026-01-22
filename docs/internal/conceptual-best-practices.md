# Conceptual Best Practices: The Internara Way

This document outlines the philosophical guiderails for development. These are not syntax rules (see
**Development Conventions**) but *thinking* rules.

> **Governance Mandate:** All architectural decisions must align with the **[Internara Specs](../internal/internara-specs.md)**.
> When in doubt, "Pragmatism" and "Clarity" override "Cleverness."

---

## 1. Pragmatic Modularity

We build modules to **manage complexity**, not to create extra work.

- **Do:** Create a new module when a domain concept has its own lifecycle, distinct data, and
  clear boundaries (e.g., `Internship`, `Assessment`).
- **Don't:** Create a module for every single database table or helper function.
- **Test:** "If I delete this module folder, does the rest of the app still compile (mostly)?" If
  yes, you have good isolation.

## 2. Service-Oriented "Brain"

The **Service Layer** is the single source of truth for business logic.

- **Controllers are dumb:** They validate input and call a service.
- **Models are dumb:** They hold data and relationships.
- **Services are smart:** They know *how* to register a student, calculate a grade, or generate a report.

## 3. Explicit over Implicit

Magic is fun, but clarity is maintainable.

- **Prefer:** Explicit dependency injection over Facades (where possible/reasonable).
- **Prefer:** Named contracts (`StudentServiceInterface`) over generic containers.
- **Avoid:** "Magic" string parsing or hidden side-effects.

## 4. Mobile-First & Multi-Language Mindset

As per the specs, every feature is built for:

1.  **Mobile Devices:** Can a user do this on a phone? If not, the design is failed.
2.  **Global Audience:** Is this string hardcoded in English? If yes, the code is failed.

## 5. The "No-Surprise" Database

- **UUIDs everywhere:** Primary keys are always UUIDs.
- **No Physical Cross-Module FKs:** We never use `foreignId()->constrained()` pointing to a table in another module. We use `foreignId()->index()`.
- **Soft Deletes:** Most critical data should be soft-deleted, not destroyed.

---

_Code is read 10x more than it is written. Write for the reader._