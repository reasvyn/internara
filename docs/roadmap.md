# Development Roadmap — Status Tracker

> **Last updated:** 2026-07-24 **Changes:** refactor — repurpose from feature planning to
> development status tracker

---

## Guiding Principle

**Stabilize before expanding.** The project has 19 modules with most of the stack in place.
The priority is making what exists work correctly and reliably — not adding new features.

New features should only be considered when there is a concrete, verified need from core
stakeholders (students, teachers, school admins). "Nice to have" is not a reason to build.

---

## How This Document Works

This is a **development status tracker**, not a feature plan. It records what has been built,
what is being built, and what comes next — based on the spec lifecycle in `docs/specs/index.md`.

For feature specifications, see `docs/specs/index.md`. For planned work and task breakdown,
use GitHub Issues.

---

## Phase Progress

Specs are ordered by lifecycle phase. See `docs/specs/index.md` for full dependency graph.

| Phase | Name | Specs | Status |
|-------|------|-------|--------|
| 1 | Foundation | #1–#17 | ✅ Complete |
| 2 | Institutional | #18–#19 | 🔲 Not started |
| 3 | Partnerships | #20–#21 | 🔲 Not started |
| 4 | Programs | #22–#23 | 🔲 Not started |
| 5 | Enrollment | #24–#29 | 🔲 Not started |
| 6 | Daily Operations | #30–#32 | 🔲 Not started |
| 7 | Assessment | #33–#35 | 🔲 Not started |
| 8 | Certification | #36–#40 | 🔲 Not started |
| 9 | Reporting | #41 | 🔲 Not started |

---

## Active Work

{Current implementation work goes here. Link to GitHub Issues or specs.}

---

## Blockers

{Known blockers that prevent progress on specific phases or specs.}

---

## What Production Readiness Looks Like

Not a milestone, but a description of the state the project should reach:

- Every module can be used end-to-end without runtime errors
- Every Action returns ActionResponse
- Every mutation path goes through an Action (no direct Model mutations in Livewire)
- Every user-facing string is translatable
- Every module has tests covering its critical paths
- Documentation matches code for every module
- PHPStan passes at level 8
- No hardcoded secrets, no debug calls in production code

---

## Quick References

- `docs/specs/index.md` — Full spec dependency graph with lifecycle phases
- `docs/specs/` — All feature specifications (41 specs)
- `docs/architecture.md` — 4-layer model, Action Triad, conventions
- `docs/conventions.md` — Coding standards, invariants C1-C8, D1-D6
- `docs/modules/index.md` — All 19 modules with links
