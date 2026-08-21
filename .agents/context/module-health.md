# Module Health — Stabilization Phase v0.14.0

> **Last updated:** 2026-08-21 **Changes:** initial — extracted from merged README (project-overview) + README Module Landscape; SSOT for 18-module health, tech debt priority, and fix order for AI agents

## Description

Internara has **18 modules** in `app/` at **v0.14.0 — Stabilization** (in progress). Architecture is sound (4-layer, Action Triad, Entity/DTO), but health is uneven. **Read this before touching any domain module** — it tells you which modules are safe to extend vs. which need P0 fixes first. This is the agent-facing version of `README.md#Project Status`; `README.md` is human-facing SSOT, this file is the agent's operational checklist.

---

## Health Tiers

| Tier | Modules | What it means for the agent |
|------|---------|-----------------------------|
| **Production-Ready** | `Core`, `Auth`, `User`, `Settings`, `Setup`, `SysAdmin`, `Academics` | Stable, well-tested, fully documented. Safe to use as pattern reference. Extend freely. |
| **Stable — Needs Attention** | `Program`, `Partners`, `Enrollment`, `Journals`, `Incident`, `Assignment`, `Reports` | Works but known issues: dead DTOs, `event()` inside transactions (should be `$this->dispatchEvent()`), broken Blade, wrong `user_id` in attendance, ActionResponse gaps (`Model`/`void` instead of `ActionResponse`), empty `Livewire/` dirs. Tracked in GitHub Issues. Fix opportunistically; don't assume clean. |
| **Needs Work (P0)** | `Assessment`, `Certification`, `Document` | Structurally complete but **runtime crashes**: Blade array / multiple-root errors, broken relationships, schema mismatches (migration columns missing/non-existent columns referenced), no Entity layer in `Document`. **Fix P0 Issues first before adding features.** |
| **Skeleton** | `Evaluation` | Models only — zero Actions/Entities/Livewire/Routes/Events. Needs full scaffold per `docs/specs/AXKZW-evaluation.md`. |
| **Infrastructure** | `Jobs`, `Providers` | No business logic — queued jobs & service providers. Only touch for queue/infra changes. |

---

## Technical Debt Priority (fix in this order)

1. **Schema mismatches** — `Document`, `Certification` migrations ≠ code refs → SQL errors. Check `database/migrations/` vs `app/{Module}/Models/` before any query.
2. **ActionResponse gaps** — Many Actions return `Model`/`void` instead of `ActionResponse` (violates C7). Wrap with `ActionResponse::success()` / `ActionResponse::failure()`.
3. **Hardcoded strings** — User-facing text without `__()` → blocks localization. Add keys to both `lang/en/` + `lang/id/`.
4. **Missing Entity layer** — `Document` has no `Entities/`; business rules in Actions/Models. Extract to `final readonly` Entity with `fromModel()` + `bool` rule methods.
5. **Event dispatch violations** — `event(new X)` inside DB transaction → race condition. Use `$this->dispatchEvent(new X())` (queued, fires after commit).
6. **Dead code** — Unused DTOs, unregistered observers, events without listeners. Verify with `python3 scripts/scan_dead_code.py --module {Module}`.

---

## Fix Order for AI Agents

If the task touches a **Needs Work** module, do this first (L-size protocol may apply):

1. `git log --oneline -p` for that module's recent fixes — see what P0s are already filed
2. `python3 scripts/scan_violations.py --module {Module}` + `scan_class_contracts.py --module {Module}` — baseline the debt
3. Fix P0 runtime errors (schema, Blade crash, missing relation) before adding new logic
4. Add spec-traceable tests (`FR-*`/`NFR-*` from `docs/specs/{ID}-{feature}.md`) — no orphan tests

If the task is **new feature in a healthy module**, use that module as template; if in a sick module, budget extra time for debt.

---

## AI Agent Guides

| If you need to... | Do this |
| ----------------- | ------- |
| Add a feature to `Evaluation` (skeleton) | Scaffold full 4-layer stack per `spec-writing` + `code-writing` skills: Models → Entities → DTOs → Actions (Command/Read/Process) → Livewire → Policies → Routes → `lang/` → tests |
| Touch `Assessment`/`Certification`/`Document` | Read this file first; run `scan_violations --module {Name}`; fix schema/Blade P0s; don't assume `ActionResponse` contract holds |
| Extend `Reports` (being purified) | Check `docs/modules/reports.md` §Boundary — grade card only, no thesis content (thesis belongs to `Assignment`) |
| Diagnose unknown failure | Check `README.md#Project Status` for tier, then `python3 scripts/scan_dead_code.py` + `git blame` for that module |

---

## Quick References

- `README.md#Project Status` — human SSOT for this file
- `docs/modules/index.md` — module docs (overview + reference per module)
- `docs/specs/index.md` — spec registry (18 modules × specs)
- `scripts/scan_violations.py`, `scan_class_contracts.py`, `scan_dead_code.py` — health scanners
- `CONTRIBUTING.md` — branch/commit conventions for fixes
