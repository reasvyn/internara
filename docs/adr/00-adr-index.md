# Architecture Decision Records

> 17 records documenting the key architectural decisions behind Internara.
> Each ADR follows the format: Context → Decision → Consequences → References.

## Index

| # | Record | Status |
|---|---|---|
| 001 | [UUID Primary Keys](adr-001-uuid-primary-keys.md) | ✅ Accepted |
| 002 | [Domain-First Architecture](adr-002-domain-first-architecture.md) | ✅ Accepted |
| 003 | [Action Pattern over Services](adr-003-action-pattern-over-services.md) | ✅ Accepted |
| 004 | [Entity-Model Separation](adr-004-entity-model-separation.md) | ✅ Accepted |
| 005 | [State Machine Pattern](adr-005-state-machine-pattern.md) | ✅ Accepted (Revised 2026-05-21) |
| 006 | [SmartLogger Dual-Channel Logging](adr-006-smartlogger-dual-channel.md) | ✅ Accepted |
| 007 | [SQLite as Default Database](adr-007-sqlite-as-default-database.md) | ✅ Accepted |
| 008 | [Base Class Mandate](adr-008-base-class-mandate.md) | ✅ Accepted |
| 009 | [Livewire over SPA](adr-009-livewire-over-spa.md) | ✅ Accepted |
| 010 | [Domain-Split Routes](adr-010-domain-split-routes.md) | ✅ Accepted |
| 011 | [Exception Hierarchy](adr-011-exception-hierarchy.md) | ✅ Accepted |
| 012 | [Flat RBAC with Functional Roles](adr-012-flat-rbac-with-functional-roles.md) | ✅ Accepted |
| 013 | [Three-Tier Configuration](adr-013-three-tier-configuration.md) | ✅ Accepted |
| 014 | [Unified Health Check](adr-014-unified-health-check.md) | ✅ Accepted |
| 015 | [HasAuditTrail Adoption Strategy](adr-015-has-audit-trail-adoption.md) | ✅ Accepted (New 2026-05-21) |
| 016 | [Verification Column Pattern](adr-016-verification-column-pattern.md) | ✅ Accepted (New 2026-05-21) |
| 017 | [Shared Hosting Deployment](adr-017-shared-hosting-deployment.md) | ✅ Accepted |

## Reading Order

ADRs are numbered chronologically. Start from 001 to understand the foundational
decisions, then proceed to later records for domain-specific refinements.

Key clusters:

| Cluster | ADRs | Topic |
|---|---|---|
| **Foundation** | 001–004 | IDs, domain structure, actions, entities |
| **Behavior** | 005–006 | State machines, logging |
| **Infrastructure** | 007–008 | Database, base classes |
| **Interface** | 009–010 | UI framework, routing |
| **Quality** | 011–014 | Exceptions, RBAC, config, health |
| **Refinements** | 015–016 | Audit trails, verification patterns |
| **Deployment** | 017 | Shared hosting deployment |

## References

- `docs/architecture.md` — high-level architecture overview
- `docs/conventions.md` — coding conventions derived from these ADRs
