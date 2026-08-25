# ADR ↔ Spec Requirement Gap Audit — 2026-08-25

> **Scope:** All 15 ADRs (MADR-lite, de-numbered) vs all 58 specs in `docs/specs/` — requirement-level (FR/NFR) presence, not implementation.
> **Method:** Keyword + FR table scan per ADR's Decision Drivers / Considered Options. Severity per impact-to-effort (business importance × reach).

## Summary

| Verdict | Count | ADRs |
|---------|-------|------|
| **Covered** — explicit FR/NFR exists | 9 | UUID, Action-based MVC, Action Pattern, Entity-Model, SmartLogger, Base Class, Exception Hierarchy, Flat RBAC, Cross-Role Proxy |
| **Partial** — mentioned but no explicit FR for the decision's core promise | 4 | Self-Hosted Single-Tenant, Cross-Module Communication, Eloquent Observers, Program Closure |
| **Gap** — no FR at all | 2 | Performance & Optimization (Tier 0-3), Gradual Migration |

## Detailed Findings

### Covered

| ADR | Spec FR Evidence |
|-----|------------------|
| **UUID Primary Keys** | `SE5Q9` FR-M1/M2 (`HasUuids`, `BaseModel`), `ZT6VS` FR-DB4, `J68GZ` FR-DB5 — all models UUID v7 |
| **Action-based MVC** | `D2FT3` FR-ARC1-4 (module colocation, vertical slice, Core holds shared) |
| **Action Pattern over Services** | `D2FT3` FR-ARC11-17 (Action Triad, single `execute()`, transaction/logging per type) |
| **Entity-Model Separation** | `D2FT3` FR-ARC15/18/19 + `SE5Q9` FR-M3/M4 (`final readonly`, `fromModel`) |
| **SmartLogger Dual-Channel** | `89SRA` FR-SL1-6, FR-DC1-6, FR-EI1-4, FR-TR1-4 — dual-channel, PII masking, graceful degradation fully spec'd |
| **Base Class Mandate** | `SE5Q9` FR-A1-7, FR-M1-7, FR-L1-7, FR-C1-5, FR-E1-3 — all 12 base classes mapped |
| **Exception Hierarchy** | `SE5Q9` FR-E1-3 (dual hierarchy, RejectedException C8) |
| **Flat RBAC with Functional Roles** | `T4B26` extensive FR for flat roles + `Role::resolvesTo()` + 3 functional roles |
| **Cross-Role Proxy** | `T4B26` MentorEntity bridge + `T657Z` NFR-S4 + `QLHDO` §7.1 |

### Partial — Recommendation: Add FR to close gap

| ADR | Gap | Affected Spec | Recommendation |
|-----|-----|---------------|----------------|
| **Self-Hosted Single-Tenant** | No FR states "single-tenant per instance, SQLite dev / MySQL prod, sync default, no external service" as a requirement — only trade-off prose in `ZT6VS`/`YB22J`/`QLHDO` | `06IB6` Deployment or `D2FT3` Architecture | Add NFR: `NFR-DEP1` single-tenant deployment matrix (DB/cache/queue/session/storage defaults) |
| **Cross-Module Communication Discipline** | `D2FT3` FR-ARC10 says "MAY import directly; prefer Action calls" but the 4-pattern ranked guidance (Core Contracts → Events → Action delegation → Direct import) from the ADR is not an FR | `D2FT3` Architecture | Add FR-ARC21: ranked communication hierarchy with enforcement scope |
| **Eloquent Observers** | `YB22J` FR-S11 FR-S12 FR-C3 cover `SettingObserver` specifically; generic decision framework (same-module only, synchronous required, single-model scope) has no spec-level FR | `NUCY3` Event System or new `D2FT3` FR | Add FR: observer qualification criteria + decision table |
| **Program Closure & Archival** | `9YUUK` FR-AR1-9 covers `ArchiveRecord` + `ArchiveCohortProcess` but the 7-step `CloseProgramProcess` and snapshot lifecycle (`DRAFT→…→ARCHIVED` terminal) from ADR is only in `7C5WM` G3/G6 (goal) without FR | `7C5WM` Internship Lifecycle | Add FR: `CloseProgramProcess` 7 steps + `ARCHIVED` terminal + read-only + retention |

### Gap — No FR

| ADR | Gap | Affected Spec | Severity | Priority |
|-----|-----|---------------|----------|----------|
| **Performance & Optimization Strategy** | Tier 0 (no-regret: composite indexes, cache registry, eager loading, Read Actions) through Tier 3 (replica, S3, PHP-FPM tuning, Redis cluster) has no spec. `06IB6` Deployment lists tiers as deployment, not performance strategy; no FR for "Tier transitions are `.env` swaps, zero code" or explicit deferral list (Octane/sharding). | `D2FT3` or new `PERF` spec (`J68GZ` System Requirements) | Medium | P2 |
| **Gradual Migration** | Three-phase migration (Start `array` → Stabilize `Data\|array` → Final `Data`; inline→event→registry for cache; rules in Form → Entity::rules()) has no spec. Currently only in ADR and conventions — no FR governs when to migrate or the union-type intermediate. | `D2FT3` or `SE5Q9` | Low | P3 |

## Recommended Next Steps (impact-to-effort ordered)

1. **Performance Tier FR** (2effort/4impact) — add NFR+FR to `D2FT3` or `J68GZ` defining Tier 0 no-regret + Tier triggers + deferral list. Unblocks self-hosted scaling claims.
2. **Cross-Module Communication FR** (1effort/3impact) — quick win; add FR-ARC21 to D2FT3 with the 4-pattern table from ADR.
3. **Gradual Migration FR** (1effort/2impact) — add to `SE5Q9` or `D2FT3` appendix; low urgency but removes dev friction ambiguity.
4. Partial fills for Self-Hosted, Observers, Program Closure — batch as docs-only FR additions, no code change.

## Verification

- `scan_doc_links.py` not run (spec-only audit, no link changes)
- `rg` coverage checks per ADR core phrase — see audit log in shell history
