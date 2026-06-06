# Architecture Decision Records
> Last updated: 2026-06-01
> Changes: Converted Status metadata to Changes format


> 13 records documenting the key architectural decisions behind Internara. (14 files including performance optimization ADR.)
> Each ADR follows the format: Context → Decision → Consequences → References.

## Foundation

| Record | Status |
|---|---|
| [UUID Primary Keys](adr-uuid-primary-keys.md) | ✅ Accepted |
| [Action-based MVC Architecture](adr-action-based-mvc-architecture.md) | ✅ Accepted |
| [Action Pattern over Services](adr-action-pattern-over-services.md) | ✅ Accepted |
| [Entity-Model Separation](adr-entity-model-separation.md) | ✅ Accepted |

## Behavior

| Record | Status |
|---|---|
| [SmartLogger Dual-Channel Logging](adr-smartlogger-dual-channel.md) | ✅ Accepted |

## Quality

| Record | Status |
|---|---|
| [Base Class Mandate](adr-base-class-mandate.md) | ✅ Accepted |
| [Exception Hierarchy](adr-exception-hierarchy.md) | ✅ Accepted |
| [Flat RBAC with Functional Roles](adr-flat-rbac-with-functional-roles.md) | ✅ Accepted |

## Strategy

| Record | Status |
|---|---|
| [Performance & Optimization Strategy](adr-performance-optimization.md) | ✅ Accepted |

## Governing Decisions

| Record | Status |
|---|---|
| [Self-Hosted Single-Tenant Architecture](adr-self-hosted-single-tenant.md) | ✅ Accepted |
| [Cross-Module Communication Discipline](adr-cross-module-communication.md) | ✅ Accepted |
| [Gradual Migration / Optional Complexity](adr-gradual-migration.md) | ✅ Accepted |
| [Program Closure & Archival](adr-program-closure-archival.md) | ✅ Accepted |

## References

- `docs/architecture.md` — high-level architecture overview
- `docs/conventions.md` — coding conventions derived from these ADRs
