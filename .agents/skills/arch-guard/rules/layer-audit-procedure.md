# Layer Audit Procedure — Four-Layer Checks & Severity

> **Last updated:** 2026-08-25 **Changes:** sync — toast rule maryUI/flasher → TallstackUI x-ts-toast (FB792 0.15.0)

Beyond the invariant/enum/security tables, the arch-guard audit walks the codebase **layer by layer**,
bottom-up (Layer 4 presentation → Layer 1 infrastructure). Each layer has its own check items that
matter beyond the class-contract and naming tables. This rule defines the audit procedure, the
per-layer checks, and how to classify severity so findings are actionable.

---

## Audit Flow

```
1. Scope the scan (module or full; L-size → split by module into sessions)
2. Run the invariant scans first (C1-C8, D1-D6) — the highest-priority rules
3. Walk each of the four layers in order, applying the layer checks below
4. Classify each finding by severity (table below)
5. Emit findings into the standard JSON report (see output-and-integration.md)
6. File issues per issue-writing conventions (CRITICAL/HIGH → issue; see delegation model)
```

**Why layer-order matters:** walking Presentation → Business → Data → Infrastructure prevents
double-counting (a C1 violation caught at Layer 4 is not re-reported at Layer 3) and steps from the
cheapest reviews to the deepest. The order mirrors the rule reference hierarchy: invariants first,
then structural contract, then layer-specific convention.

---

## Layer 4 — Presentation / UI

Surface: `app/*/Livewire/`, `resources/views/`, `app/*/Policies/`, `routes/`.

**Checks and why they exist:**

- **No `Model::create/update/delete/save` in Livewire components (C1)** — mutation must flow through
  Command Actions; direct calls bypass business rules, transactions, audit, and events.
- **No `DB::transaction()` / `DB::beginTransaction()` in Livewire** — transaction ownership belongs to
  Actions; a component holding a transaction open across render paths leaks locks.
- **No `app()->make()`, `resolve()`, or `new Action()` — method injection only (C2)** — container
  resolution hides dependencies and breaks testability.
- **`RejectedException` caught from Action calls (before generic `Throwable`)** — a rejection is an
  expected outcome, not an error; catch it first or the UI 500s instead of showing a toast.
- **No unescaped `{!! !!}` for user content without inline justification (S1)** — escaped output or a
  documented trusted invariant, never silent raw output.
- **Policy methods return boolean — no inline authorization in Livewire** — authz belongs in Policies
  (S6); inline `if (auth()->user()->id === ...)` duplicates domain decisions in the UI.
- **Routes in correct `routes/web/{module}.php` (or `{submodule}.php`)** — misrouted entries confuse
  module colocation and handoff auditing.
- **Toast via TallstackUI Interactions only (`$this->toast()->success()->send()`, `$this->toast()->error()->send()`)** — legacy `flash()->` / PHPFlasher and maryUI `$this->success()` / `$this->error()` are removed (FB792 FR-TS5); `x-ts-toast` in `core::layouts.base` is the sole container.
- **No N+1 queries in Blade loops (P1)** — eager load relationships read inside `@foreach`.

**Failure mode if skipped:** a component that mutates directly (C1), draws its own authorization, and
shows toasts outside the standard Interactions path — three independent drift sources in one screen.

---

## Layer 3 — Business / Domain Ops

Surface: `app/*/Actions/`, `app/*/Events/`, `app/*/Listeners/`.

**Checks and why they exist:**

- **Action extends the correct base class (Command/Read/Process)** — the base class defines the
  transaction/log/dispatch guarantees (see `class-contracts.md`).
- **Exactly one public `execute()` method** — a second public method is a de-facto bypass entry point.
- **Command/Process uses `$this->transaction()` for DB writes** — atomic commit or rollback.
- **`$this->log()` called after mutation** — every mutation is audited (SmartLogger); a missing log is
  silent drift in the audit trail.
- **`$this->dispatchEvent()` only if a listener exists (check `config/event.php`)** — dispatching to
  no listener is dead work and implied intent.
- **Business rules delegate to Entity — throw `RejectedException`, not `RuntimeException` (C8)** —
  domain rejections surface as flash messages, not 500s.
- **DTO for 3+ params; raw `array` not accepted in `execute()` (C7)** — in-method DTO construction
  from validated UI input keeps signatures self-documenting.
- **`ActionResponse` returned for structured feedback** — the UI reads success/data/errors uniformly.

**Failure mode if skipped:** an Action that writes outside `$this->transaction()` commits partial state
on failure and records no audit entry — hard to detect in production and worse to roll back.

---

## Layer 2 — Data / Persistent

Surface: `app/*/Models/`, `app/*/Entities/`, `app/*/Enums/`, `database/migrations/`.

**Checks and why they exist:**

- **Entities:** `final readonly`, `fromModel()`, zero I/O, no Action/Service/Controller imports (C5)
  — pure, deterministic, testable domain snapshots.
- **DTOs:** `final readonly`, scalars/enums/Carbon only, no Model/Entity/Action imports (C6) —
  transfer boundary stays data-only.
- **Models:** `#[Fillable]` attribute (not `$fillable`/`$guarded`) (D4) — modern, auditable
  mass-assignment allow-list.
- **Foreign keys:** `foreignUuid()->constrained()` + explicit `onDelete()`/`onUpdate()` (D6) — no
  silently-cascading or orphan-leaving FKs.
- **Enums:** `implements LabelEnum` (all), `StatusEnum` for state machines — labeled, translated
  (`__()`), transition-aware.
- **Cache keys:** registered in `config/cache-keys.php` — no inline strings (C4).
- **No raw SQL without parameterized binding (C3)** — parameterized or nothing.

**Failure mode if skipped:** an Entity that hits the DB inside a business question makes the Model-
Entity contract meaningless; a `final readonly` DTO carrying a Model reintroduces the layer coupling
the DTO exists to prevent.

---

## Layer 1 — Framework / Infrastructure

Surface: `app/Core/`, `app/*/Services/`, `app/*/Support/`, `config/`.

**Checks and why they exist:**

- **Services: infrastructure logic only (not domain business rules)** — a Service that encodes domain
  decisions duplicates the Entity layer and creates two sources of truth.
- **Support: static-only, zero side effects** — static helpers must be pure functions; a static that
  writes to the DB or filesystem is untestable global state.
- **Config files follow documented schema** — a config key added outside the documented schema is
  undiscoverable by docs and scanners.
- **Module discovery config includes all business modules** — a module missing from module-discovery
  is unreachable by the app's module registrar and drops its routes/actions silently.

**Failure mode if skipped:** a domain rule embedded in a Service bypasses Entity testing and
reappears as a "phantom behavior" finding in the next spec-audit.

---

## Severity Classification

| Severity | Definition | Example |
|----------|-----------|---------|
| **CRITICAL** | Security vulnerability, data loss risk | SQL injection, mass assignment |
| **HIGH** | Architecture violation, breaks invariants | C1-C8, D1-D6 violations |
| **MEDIUM** | Convention violation, maintainability impact | Naming errors, missing type hints |
| **LOW** | Style issue, minor improvement | Comment style, formatting |

**Why this scale exists:** CRITICAL and HIGH findings block a commit gate; MEDIUM and LOW are
scheduled debt. Misclassifying a HIGH invariant as MEDIUM naming lets an architecture break slide
through review.

**How to apply severity:**

- Map the finding to the category, then check the *reachable impact*: a C3 raw-SQL on an unreachable
  code path is still HIGH (invariant), but a confirmed exploitable path is **CRITICAL**.
- Layer-4 violations are HIGH when security/authz-related (S1/S6), MEDIUM otherwise (toast/naming).
- Contract and invariant violations are never LOW.

---

## Verification

```bash
python3 scripts/scan_violations.py            # C1-C8, D1-D6, security, performance
python3 scripts/scan_class_contracts.py       # class contracts
python3 scripts/scan_security.py              # S1-S10
python3 scripts/scan_naming.py                # naming
python3 scripts/scan_architecture.py          # component counts, submodule structure
python3 scripts/scan_conventions.py           # strict_types, Fillable, debug, hardcoded strings
python3 scripts/scan_dead_code.py             # unregistered observers, unused DTOs, orphan events
python3 scripts/scan_doc_links.py             # doc link integrity
python3 scripts/scan_tests.py                 # per-module test results
python3 scripts/scan_issues.py                # spec↔code gap analysis
```

Each script writes `scripts/outputs/{timestamp}-{description}.json`; use `--module {Name}` to scope,
`--strict` to fail the run on any finding (CI/CD use). See `scripts/README.md` for full usage.