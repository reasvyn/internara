# Architecture Decision Records

Index of all Architecture Decision Records (ADRs) documenting key architectural decisions behind
Internara.

## Foundation

Decisions that establish the core structural principles of the codebase.

- **[UUID Primary Keys (v7)](adr-uuid-primary-keys.md)** — Why UUID v7 was chosen over
  auto-increment or UUID v4 for database primary keys, balancing distributed identity with index
  performance
- **[Action-based MVC Architecture](adr-action-based-mvc-architecture.md)** — Why the project uses
  Action-based MVC over traditional Laravel MVC, organizing code by business module rather than
  technical layer
- **[Action Pattern over Services](adr-action-pattern-over-services.md)** — Why Actions with a
  single `execute()` method replace traditional Service classes for business logic
- **[Entity-Model Separation](adr-entity-model-separation.md)** — Why business rules are extracted
  from Eloquent Models into separate immutable Entity classes

## Observability

Decisions that govern runtime behaviour, logging, and observability.

- **[SmartLogger Dual-Channel Logging](adr-smartlogger-dual-channel.md)** — Why logging uses both
  system log and activity log simultaneously, with PII masking and translation resolution

## Quality

Decisions that enforce consistency, maintainability, and security.

- **[Base Class Mandate](adr-base-class-mandate.md)** — Why every class type (Action, Model, Entity,
  Policy, Enum) must extend or implement a specific base class or contract
- **[Exception Hierarchy](adr-exception-hierarchy.md)** — Why a dual exception tree (AppException
  for infrastructure, ModuleException for business rules) was chosen over Laravel's default
- **[Flat RBAC with Functional Roles](adr-flat-rbac-with-functional-roles.md)** — Why roles are flat
  without inheritance, with functional roles (mentor/mentee) resolved at runtime

## Proxy

Decisions governing cross-role delegation and supervisory override mechanisms.

- **[Cross-Role Proxy](adr-cross-role-proxy.md)** — Why teachers can proxy for inactive supervisors
  at the application layer, with inactivity windows and transparent compliance stamping

## Strategy

Broad architectural strategies that span the entire system.

- **[Performance & Optimization Strategy](adr-performance-optimization.md)** — What performance
  optimization strategy guides the codebase, balancing correctness with speed
- **[Self-Hosted Single-Tenant Architecture](adr-self-hosted-single-tenant.md)** — Why self-hosted
  single-tenant was chosen over multi-tenant SaaS for data sovereignty and offline robustness
- **[Cross-Module Communication Discipline](adr-cross-module-communication.md)** — How modules
  communicate without circular dependencies, preferring direct imports over events for simple cases
- **[Gradual Migration / Optional Complexity](adr-gradual-migration.md)** — How the project supports
  gradual adoption, optional complexity, and migration paths
- **[Program Closure & Archival](adr-program-closure-archival.md)** — How internship programs are
  closed and archived, with read-only snapshots and data retention

## References

- `docs/architecture.md` — high-level architecture overview and 4-layer model
- `docs/conventions.md` — coding conventions derived from these ADRs
- [ADR-014: Cross-Role Proxy](adr-cross-role-proxy.md) — Application-layer role delegation
