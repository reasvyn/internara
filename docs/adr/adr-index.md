# Architecture Decision Records

> **Last updated:** 2026-06-08
> **Records:** 13 ADRs documenting the key architectural decisions behind Internara.

Each ADR follows the standard template: **Context** — the forces and trade-offs that drove the decision,
**Decision** — what was chosen and why, **Consequences** — the resulting benefits and drawbacks,
and **References** — pointers to the relevant code and documentation.

---

## Foundation

Decisions that establish the core structural principles of the codebase.

| Record | Status |
| ------ | ------ |
| [UUID Primary Keys (v7)](adr-uuid-primary-keys.md) | ✅ Accepted |
| [Action-based MVC Architecture](adr-action-based-mvc-architecture.md) | ✅ Accepted |
| [Action Pattern over Services](adr-action-pattern-over-services.md) | ✅ Accepted |
| [Entity-Model Separation](adr-entity-model-separation.md) | ✅ Accepted (Revised) |

## Observability

Decisions that govern runtime behaviour, logging, and observability.

| Record | Status |
| ------ | ------ |
| [SmartLogger Dual-Channel Logging](adr-smartlogger-dual-channel.md) | ✅ Accepted |

## Quality

Decisions that enforce consistency, maintainability, and security.

| Record | Status |
| ------ | ------ |
| [Base Class Mandate](adr-base-class-mandate.md) | ✅ Accepted |
| [Exception Hierarchy](adr-exception-hierarchy.md) | ✅ Accepted |
| [Flat RBAC with Functional Roles](adr-flat-rbac-with-functional-roles.md) | ✅ Accepted |

## Strategy

Broad architectural strategies that span the entire system.

| Record | Status |
| ------ | ------ |
| [Performance & Optimization Strategy](adr-performance-optimization.md) | ✅ Accepted |
| [Self-Hosted Single-Tenant Architecture](adr-self-hosted-single-tenant.md) | ✅ Accepted |
| [Cross-Module Communication Discipline](adr-cross-module-communication.md) | ✅ Accepted |
| [Gradual Migration / Optional Complexity](adr-gradual-migration.md) | ✅ Accepted |
| [Program Closure & Archival](adr-program-closure-archival.md) | ✅ Accepted |

## References

- `docs/architecture.md` — high-level architecture overview and 12-layer model
- `docs/conventions.md` — coding conventions derived from these ADRs
