# Architecture Patterns

> **Last updated:** 2026-08-27 **Changes:** rewrite — all 16 arch patterns integrated with global industry standards (CQRS, DDD, SOLID, PoEAA, PSR-3/16, WCAG 2.2, OWASP, NIST) with anti-pattern tables, Quick References with URLs

Design patterns and conventions that govern the Internara codebase. See
[`docs/architecture.md`](../../architecture.md) for the high-level architecture overview.

## Core Patterns

- **[Action Triad](action-pattern.md)** — CQRS (Fowler/Young), Command Pattern (GoF), SOLID SRP, Unit of Work (PoEAA), ActionResponse contract
- **[Entity-Model Separation](entity-pattern.md)** — DDD Entity (Evans), Rich Domain Model vs Anemic (Fowler), Aggregates (Vernon), Persistence Ignorance (Khorikov)
- **[Model (Active Record)](model-pattern.md)** — Active Record (PoEAA), UUID v7 (RFC 9562), Eloquent conventions, Bridge Pattern
- **[Data Transfer Objects](data-pattern.md)** — Data Transfer Object (PoEAA), Value Object (DDD), Immutability, Type Safety, ActionResponse
- **[Enum & State Machine](enum-pattern.md)** — Finite State Machine, Type State Pattern, GoF State Pattern, LabelEnum/StatusEnum/ColorableEnum

## Domain Patterns

- **[Events & Notifications](event-pattern.md)** — Domain Events (Evans DDD), Observer Pattern (GoF), Event-Driven Architecture (Fowler)
- **[Exception Hierarchy](exception-pattern.md)** — Exception Hierarchy design, SOLID Error Handling, Defence in Depth (NIST), Result Type concept
- **[Caching](cache-pattern.md)** — Cache-Aside Pattern, PSR-16, Cache Stampede/Thundering Herd, TTL categories, event-driven invalidation
- **[Logging & PII](logging-pattern.md)** — PSR-3 Logger Interface, Structured Logging, PII/GDPR compliance, SmartLogger dual-channel

## Boundary Patterns

- **[Service Pattern](service-pattern.md)** — Service Layer (PoEAA), SRP (SOLID), God Object anti-pattern, Factory Pattern
- **[Support Utilities](support-pattern.md)** — Utility Pattern, Pure Functions, Static Methods, Immutability
- **[Repository Pattern](repository-pattern.md)** — Repository (PoEAA), Active Record vs Data Mapper, Query Object
- **[Authorization](policy-pattern.md)** — RBAC (NIST), Principle of Least Privilege, Defence in Depth, Gate Pattern

## Presentation Patterns

- **[Livewire Components](livewire-pattern.md)** — Thin Controller (MVC), Presentation Model (PoEAA), MVVM, Separation of Concerns
- **[UI Pattern](ui-pattern.md)** — Visual Hierarchy (F-pattern/Z-pattern), Tailwind v4 CSS-first, responsive, component design, performance
- **[UX Pattern](ux-pattern.md)** — WCAG 2.2 AA, NN/g 10 Heuristics, ISO 9241, W3C DTCG/i18n, user flow

## Infrastructure Patterns

- **[Modular Architecture](modular-pattern.md)** — Modular Monolith, Clean Architecture, Hexagonal Architecture, SOLID, DRY
- **[Testing](testing-pattern.md)** — TDD, Testing Pyramid, AAA, FIRST, Four Phase Test, spec-driven testing
