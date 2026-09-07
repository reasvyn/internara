# MVP Spec Trim — Removing Over-Engineered Requirements

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-09-07 |
| Technical Story | Spec-driven testing refactor → MVP requirement triage across all 62 feature specs |

## Context and Problem Statement

Testing is spec-driven: every `FR-*`/`NFR-*`/`UC-*` in a spec must have a tracing test, and every
test must trace to a requirement. The full spec corpus carries **2,004 FR + 778 NFR + 259 UC**
requirement rows across 62 spec files. A meaningful portion describes operational depth that is
over-engineered relative to what a *minimum viable product* needs — multi-driver database dumps,
deep compliance subsystems, retention/archival pipelines, recovery-key ecosystems, notification
fanout matrices, CLI/scheduling surface area, and micro-benchmark performance targets.

Writing spec-traceable tests for every row would budget enormous effort against behaviors that do
not serve an MVP deployment. The MVP test contract should be defined by *trimming the spec corpus*
so that remaining requirements are exactly the MVP scope — then adding explicit test-layer
requirements (Arch / Unit / Feature / Browser) to each spec that remain so tests can trace.

**Decision Drivers:**

* Spec-driven minimalism — tests verify the spec, nothing more; coverage = requirements covered
* MVP focus — the first production release serves the core PKL lifecycle, not operational depth
* SSoT discipline — specs are authoritative; test effort must follow spec scope, not code reality

## Considered Options

* **Keep all requirements, write tests for everything** — *Pros:* complete coverage. *Cons:*
  enormous test budget for post-MVP behaviors; violates MVP velocity.
* **Keep all requirements, mark non-MVP rows non-testable** — *Pros:* non-destructive. *Cons:*
  pollutes the corpus with rows the MVP will never verify; scanner-bookkeeping complexity.
* **Remove over-engineered requirements from specs (chosen)** — *Pros:* spec corpus == MVP
  contract; test-writing and coverage scoring reflect reality; removed scope is recorded in the
  ADR and re-addable when a post-MVP phase starts. *Cons:* destructive to SSoT (recorded here);
  code implementing removed rows becomes unconstrained (acceptable for MVP until a post-MVP phase
  re-governs it).

## Decision Outcome

**Chosen option: Remove over-engineered requirements from specs.** Each spec is evaluated against
the MVP trim rubric below; rows judged unambiguously post-MVP operational depth are deleted from
the FR/NFR/UC tables (and any now-empty subsystem subsection headers). After trimming, every spec
gains a **Test Requirements** section mapping its retained behavior to the four test layers.

> **Guardrail (non-negotiable):** trim is **scope discipline, not feature stripping.** The vast
> majority of specs are `Shipped` — their FR/NFR rows describe implemented, verified behavior that
> an MVP deployment does ship. **Default is KEEP.** A row is removed only when it is unambiguously
> post-MVP operational depth falling into one of the rubric categories below and removing it does
> not vacate a subsection that corresponds to an implemented feature. When in doubt, keep. Typical
> outcome: a small handful of removed rows per spec, not the majority.

**MVP Trim Rubric — rows are candidates for removal ONLY when they are:**

| Category | Examples |
|----------|----------|
| Portability scaffolding | Multi-driver conditional dispatch (MySQL/PostgreSQL/SQLite dump variants) when a fixed deployment driver suffices |
| Deep infrastructure orchestration | Infra management UIs beyond a single status view, retention schedulers, cleanup daemons, worker supervision |
| Compliance subsystems | GDPR erasure engines, archiving & retention policy engines, deletion-log matrices |
| Security extensions | Recovery slips, recovery-key ecosystems, 2FA/OTP/passwordless on top of core auth |
| Notification/preference fanout | Per-recipient preference matrices, multi-channel fanout when a simple notify covers MVP |
| Export/import multipliers | Multi-format (CSV/XLSX/PDF) matrices when a single format satisfies MVP |
| Scheduling/daemon automation | Scheduled jobs beyond the MVP cadence, cron surface, multi-pipeline queue orchestration |
| Micro-benchmark performance | p95-ms budgets, load-test targets requiring dedicated load-testing infra |

**NEVER remove (MVP core):** schema/model/enum/entity/DTO contracts, base-class mandates, CRUD
Actions, Policy authorization, validation, localization (`__()`), RBAC, core user journeys
(auth, setup, dashboard, departments/years, companies/partnerships, registration/placement,
logbook/attendance/incident, assessment/evaluation/assignment, certification/docs/reports), and
reliability basics (logging with PII masking, error handling, base event/notification infra, cache
registry, basic queue for mail/PDF). If removing rows would empty a subsection that maps to an
implemented feature, keep those rows. **When in doubt, KEEP.**

### Positive Consequences

* MVP spec scope == MVP test scope; `scan_spec_tests` coverage scoring reflects only what MVP ships
* Test-writing budget focuses on the core PKL lifecycle
* Trimmed rows remain traceable via this ADR and re-enter via a spec amendment (spec-first) when a
  post-MVP phase (Maintenance depth, GDPR, archiving) starts

### Negative Consequences

* Spec↔code drift for implemented-but-trimmed rows (code keeps behavior, spec no longer governs it)
  — accepted for MVP; revisit when the corresponding post-MVP phase begins
* Removal is destructive; req IDs deleted here are superseded by re-added rows in future phases

## Links

* [Spec Index](../specs/index.md) — 62 feature specs affected by this decision
* [Testing Pattern](../guides/arch/testing-pattern.md) — layer patterns referenced by Test Requirements sections
* `tools/scan_spec_tests.py` — spec↔test coverage guard (uses FR/NFR/UC prefixes; `TR-*` IDs are exempt)