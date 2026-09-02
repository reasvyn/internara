# Project Vision — Where Internara Is Going

## Description

Long-term direction for Internara — why it exists, where it is going in 3–5 years, and how every decision is evaluated. This document is the north star for contributors, maintainers, and schools adopting the platform. A condensed product overview lives in `README.md`; day-to-day principles live in `philosophy.md`; this file answers *what the future looks like* and *how we will know we got there*.

## Vision Statement

> **Every Indonesian vocational school runs its compulsory fieldwork (PKL) with the same confidence as a well-funded university — on infrastructure it fully controls, with audit-ready records, without paperwork, and without asking teachers to become administrators.**

Internara is not a school management system. It is a *single-purpose, deep* system for one legally-mandated workflow (PKL) that today consumes 60–80% of teacher time on admin and 2–3 weeks per cohort on grade-card compilation. When PKL is solved, schools reclaim those weeks for mentoring.

## Mission

1. **Make PKL operable at scale** — 500–1,000 students × 90 days × 150–300 companies per period without Excel version conflicts, lost attendance sheets, or late discovery of missing logbooks.
2. **Make compliance effortless** — Permen Pendidikan requires auditable PKL records (signed attendance, reflective logbooks, standardized rubrics, verifiable certificates). Internara produces them as a by-product of normal use, not as extra work.
3. **Make data sovereign** — Self-hosted, single-tenant, zero recurring cost. No telemetry, no cloud dependency, no vendor lock-in. A school in a major city with IT staff and a school in a remote district with one shared computer both run it on a $5/month shared host (SQLite, file cache, sync queue) and grow into MySQL/Redis/workers only when they need to.

## Strategic Pillars

| Pillar | What it means in practice | How we measure it |
|--------|---------------------------|-------------------|
| **P1 — Workflow Fidelity** | Mirror real school operations (DUDI MoU quotas, dual mentors, placement change requests, absence approvals) rather than forcing schools to adapt to software | Persona walkthroughs pass without “workaround” steps; spec QLHDO UC coverage = 100% |
| **P2 — Zero-Admin Operations** | Teacher and supervisor actions are one-tap, offline-resilient, and self-explanatory; coordinator dashboards show placement rate, slot capacity, and anomalies in real time | Grade-card compilation 2–3 weeks → instant; logbook review <2 min per entry |
| **P3 — Security & Trust First (S1)** | Authorization at every layer, PII masking, immutable activity logs, GPS-tagged attendance, QR-verifiable certificates | `scan_security.py` 0 high, `scan_violations.py` C1–C8 clean; external audit 0 critical |
| **P4 — Sustained Maintainability (S2)** | Module colocation (18 modules own their stack), Action Triad (one `execute()`), Entity/DTO boundaries — a new contributor ships a feature after reading `architecture.md` + `conventions.md` | `scan_class_contracts.py` 0 high, bus factor >2 per module |
| **P5 — Pragmatic Scalability (S3)** | Single-tenant (no tenant-ID overhead), CQRS-inspired Action triad keeps read/write paths decoupled — system “does not collapse as features are added” | Handles 1,000 active students / 300 companies on VPS 1 vCPU / 1.9G RAM (current prod) without queue saturation (`pm.max_children=6`) |

## 3–5 Year Horizon (2026 → 2030)

| Horizon | Outcome for schools | Technical enabler |
|---------|---------------------|-------------------|
| **2026 — Stabilization (v0.15.9)** | 18 modules full-stack, P0 runtime crashes fixed, test suite ~98% pass, Docker deploy on `internara.web.id` (product demo) stable | 4-layer Action MVC, `fe4096b9c` production hardening, version-tagged deploys (`v*.*.*`) |
| **2027 — Scale & Trust** | 50+ schools in production, Dapodik CSV export adopted for accreditation, certificate QR verification used by companies for hiring | `docs` ⇄ `specs` ⇄ `code` sync (`sync-docs` skill), `module-health.md` green tiers, `scan_violations.py` 0 high |
| **2028 — Ecosystem** | Regional Dinas PKL forks with custom certificate templates and Dapodik extensions; contributions flow upstream | MIT license, `CONTRIBUTING.md` contributor POV, `project-vision.md` as decision gate for forks |
| **2030 — Reference Implementation** | PKL is cited as example of “infrastructure sovereignty for public education” — schools self-host, data stays on-prem, program fully auditable end-to-end | No multi-tenant SaaS, no telemetry, no upsell — intentional non-goals below |

## Personas — Future State

| Persona | Today (paper/Excel/WhatsApp) | With Internara (vision) |
|---------|------------------------------|-------------------------|
| **Interns (16–18 y.o., mobile-first)** | Register via paper, lose WhatsApp logbooks, wait weeks for grades | Register via wizard, GPS attendance, reflective logbook with mentor feedback <48h, grades/certificates instantly verifiable |
| **Schools (Admin 2–5, Teachers 10–30)** | 60–80% time on admin, 2–3 weeks compiling grade cards | Real-time dashboards, batch CSV, automated MoU expiry alerts, cross-role proxy when supervisors disengage, grade-card sign-off in one session |
| **Companies (Supervisors, weekly access)** | Disengage, paper evaluations, phone-call certificate checks | One-tap verification, guided competency rubrics, QR scan replaces phone call |

All personas share *one* real-time source of truth — admin, teacher, supervisor, and student see the same placement, attendance, and completion rate.

## Boundaries — What We Will Not Build

Clarity on non-goals prevents scope creep and keeps the codebase maintainable.

| Will Not Build | Why (and what to use instead) |
|----------------|-------------------------------|
| Multi-tenant SaaS, billing, tenant routing | Single-tenant is a *feature* — sovereignty, no tenant-ID overhead, simple mental model |
| General HR/ATS, payroll, post-graduation hiring pipeline | Scope ends at PKL completion + certificate; integrate via CSV, not via platform |
| Real-time chat / WebSocket | Structured notifications + email only — avoids infra bloat and moderation burden |
| Native mobile apps | Responsive web (Livewire + Alpine) — no app-store fragmentation |
| Direct Dapodik/NSP government DB sync | CSV import/export only — minimal integration surface, maximum stability |
| Telemetry, analytics pings, upsells, tiered features | No dark patterns — MIT, no subscription, full DB access |

Any proposal that violates these boundaries is closed with a reference to this section.

## Success Metrics (how we know we got there)

- **Operational:** Grade-card compilation median <5 minutes (from 2–3 weeks); MoU expiry alerts 30/14/7 days before; attendance anomalies flagged <24h
- **Adoption:** 50 production schools by end-2027 without multi-tenant; 0 critical `scan_security.py` findings on every release
- **Quality:** `vendor/bin/pest` ~98% pass *and* `specs/*` FR coverage = 100% (no spec gaps, no orphan tests)
- **Trust:** 100% of certificates QR-verifiable; every mutation has `activity_log` entry (SmartLogger, PII-masked, `systemOnly`); PII retention 5 years then GDPR delete
- **Sustainability:** New contributor ships `feat(enrollment):` in <1 week after reading `architecture.md` + `conventions.md` + `modules/index.md`; bus factor ≥2 per healthy module

Metrics are reviewed quarterly; this file is updated when a metric changes, not when code changes.

## Decision Compass

When a feature request, refactor, or trade-off is ambiguous, apply in order:

1. **Does it help a school run PKL better without adding admin?** If no, close.
2. **Does it preserve self-hosting on $5 shared hosting by default?** If it requires Redis/S3/MySQL to function, make it progressive enhancement, not a hard dep.
3. **Does it keep the module colocated and the Action single-purpose?** If it introduces a cross-cutting God class or a second way to do the same thing, extract or reuse instead.
4. **Does it have a spec requirement ID (FR/NFR/UC) and a test tracing to it?** If not, write the spec first (spec-first doctrine).
5. **Does it keep data on school infra with audit trail?** If it leaks PII or bypasses logging, it does not ship.

For ambiguous cases, open a Discussion, link this file, and record the decision as an ADR in `docs/adr/`.

## Roadmap — Now vs Next (link, not duplicate)

- **Now (`v0.15.9 Stabilization`):** Fix P0 runtime crashes in `Assessment`, `Certification`, `Document` (see `README.md#Project Status` + module-health tiers), improve domain test coverage, docs sync. Tracked in GitHub Issues labeled `critical`/`high`.
- **Next:** Reports purification (grade-card only, thesis stays in `Assignment`), partnership slot-quota hardening, journal supervision-log deduplication — each with spec IDs in `docs/specs/index.md`.
- **Later:** Dapodik export hardening, Dinas PKL regional forks, evaluation module scaffold (`Evaluation` is `Skeleton` today).

Detailed build order lives in `docs/specs/index.md` (spec implementation matrix) and `README.md#What's Next`; this file does not duplicate them.

## Where to Find It

| Need | Doc |
|------|-----|
| Product scope, personas, boundary, deployment, module landscape | `README.md` |
| Guiding principles, values, “what we do not do” in depth | `philosophy.md` |
| Requirements (FR/NFR/UC) that vision traces to | `docs/specs/index.md` → `docs/specs/QLHDO-internara-project.md` |
| Architecture that makes vision buildable | `architecture.md` (4-layer, Action Triad) |
| How we build & ship (quality gates, version tags) | `AGENTS.md` (workflow 5-step), `.github/workflows/release.yml` (tag-driven 4-stage release pipeline), `infrastructure/deployment.md` |
| Current health & where to contribute | Module-health tiers (agent memory) + `CONTRIBUTING.md` |

## Where to Find It

- `README.md` — product scope, 3S doctrine, system boundary, project status
- `philosophy.md` — values, principles, pragmatic trade-offs
- `docs/specs/QLHDO-internara-project.md` — umbrella spec (functional/non-functional/UI-UX)
- `architecture.md` — 4-layer model and Action Triad that make the vision executable
