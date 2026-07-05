# Architecture Patterns

Design patterns and conventions that govern the Internara codebase. See
[`docs/architecture.md`](../architecture.md) for the high-level architecture overview.

- **[Action Triad](action-pattern.md)** — Command/Read/Process patterns, transaction safety,
  ActionResponse contract
- **[Entity-Model Separation](entity-pattern.md)** — Entity bridge pattern, immutability, fromModel,
  entity extraction workflow
- **[Model (Active Record)](model-pattern.md)** — Eloquent model patterns, UUID PKs, scopes,
  relationships, casts, factories
- **[Data Transfer Objects](data-pattern.md)** — BaseData DTO patterns, fromArray/toArray,
  ActionResponse, DTO migration path
- **[Enum & State Machine](enum-pattern.md)** — LabelEnum/StatusEnum/ColorableEnum contracts, state
  machine patterns
- **[Events & Notifications](event-pattern.md)** — BaseEvent contract, dispatch patterns, listeners,
  multi-channel notifications
- **[Livewire Components](livewire-pattern.md)** — Thin component rule, Form Objects,
  BaseRecordManager, auto-discovery
- **[Authorization](policy-pattern.md)** — Flat RBAC, three-layer auth, Gate::before bypass
- **[Exception Hierarchy](exception-pattern.md)** — Dual AppException/ModuleException trees,
  HasExceptionContext
- **[Logging & PII](logging-pattern.md)** — SmartLogger dual-channel fluent API, PII masking,
  translation resolution
- **[Caching](cache-pattern.md)** — Centralized key registry, TTL categories, event-driven
  invalidation
- **[Service Pattern](service-pattern.md)** — Services vs Actions vs Support — domain logic vs infra
  logic vs static utilities
- **[Support Utilities](support-pattern.md)** — Module-level helpers, static-only, no constructor
  injection
- **[Repository Pattern](repository-pattern.md)** — Why no Repository layer, Eloquent as Repository,
  query tier patterns
- **[Modular Architecture](modular-pattern.md)** — Complete catalog of all design patterns,
  conventions, and architectural rules
- **[Testing](testing-pattern.md)** — All testing patterns, scope isolation, layer strategies,
  assertions, performance
