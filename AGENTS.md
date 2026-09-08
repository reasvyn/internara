# AGENTS.md — Navigation Hub for AI Agents

Mental model and operating contract for AI agents working on Internara. This file is the
**navigation hub + behavior contract**: it routes to the authoritative documentation under `docs/`
and never restates it. Reference knowledge (patterns, commands, inventories) lives in `docs/` and
`tools/README.md` — load the file it names; do not re-derive the rule from this hub.

**Single Source of Truth (SSoT) priority** when sources conflict: `adr > specs > guides > code > refs`.
Trust unambiguous ground truth directly. Check `git log --follow` / `git blame` only when sources
conflict or the choice would change with history; contradictory history → report, don't dig.

> **Workflow contract:** Every instruction runs the 5-step cycle
> `UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE` — a **funnel, not a checklist**: run only
> the steps the task needs and stop as soon as the path is clear. For S-size or clarity tasks the
> cycle collapses to `grep → act → verify`. No step needs deep reasoning when a governing spec,
> template, validator, or sibling file already dictates the answer. Always surface: ambiguity,
> scope/size changes, an L-size split (see "session" below), one M-size pre-commit checkpoint, and
> the final report.
>
> **Spec-first doctrine:** behavior changes need a requirement ID from a governing spec in
> `docs/specs/`; if none exists, write the spec first — never fix-first. Exemption: mechanical
> fixes (typos, formatting, lint, renamed symbols) skip the spec. If spec and code disagree, fix
> code to spec; if the spec is demonstrably wrong, correct it (one-line `> Decision:` note) before
> aligning code and tests.
>
> **Commit discipline:** commit at the end of **every completed scope** — do not wait to be asked.
> A scope is one self-contained change (stage 5). Run the pre-commit baseline (§4) first, stage
> only intended files, message `type(scope): desc`.

---

## 1. Docs & Code Navigation

### 1.1 Authoritative Docs

| Concern | File | What it owns |
|---|---|---|
| Vision, values, what-we-do-not-do | [`docs/philosophy.md`](docs/philosophy.md) | "Why we exist" — 7 principles + core values |
| Architecture: layers, data flow, dependency rules | [`docs/architecture.md`](docs/architecture.md) | High-level architecture + Action Triad + rules R1–R7 |
| Coding conventions: PHP style, security, naming, perf, testing, i18n, theming | [`docs/conventions.md`](docs/conventions.md) | C1–C8, D1–D6 invariants, pre-commit & review checklists |
| Pattern catalog (Action Triad, Entity/Model/DTO/Enum, Event, Cache, Logging, Livewire, Service, Support, Repository, Policy, UI/UX, Testing) | [`docs/guides/arch/index.md`](docs/guides/arch/index.md) | One link per pattern → deep-dive `*-pattern.md` |
| Modular architecture deep-dive (SRP, base classes, contracts, naming, accessibility, localization) | [`docs/guides/arch/modular-pattern.md`](docs/guides/arch/modular-pattern.md) | §1–§23 pattern catalog |
| Feature / module specs (62 + 2 meta; FR/NFR/UC per spec) | [`docs/specs/index.md`](docs/specs/index.md) | Specs grouped in 12 phases; spec template is `docs/templates/spec-template.md` |
| ADRs — why each decision was made | [`docs/adr/index.md`](docs/adr/index.md) | 16 ADRs in 6 groups |
| Module conceptual + reference docs | [`docs/refs/modules/index.md`](docs/refs/modules/index.md) | One conceptual + one reference per module |
| Tooling — scanners, CLI flags, output schema, inventory | [`tools/README.md`](tools/README.md) | Full scanner reference; AGENTS.md keeps only the batch below (§5) |
| Doc-type templates (skeleton + writing rules) | [`docs/templates/index.md`](docs/templates/index.md) | 10 templates — load the matching one when its concern is touched |

### 1.2 Pattern & Convention Quick Reference — One-Liners

These are **recognition cues** (one row per common pattern). The full rule, why, and exception
list lives in `docs/conventions.md` and `docs/guides/arch/*-pattern.md`.

| You see | It should be | Where the rule lives |
|---|---|---|
| `Model::create()` in a Livewire component | Command Action via DI (C1) | `docs/conventions.md` §3.3 + `docs/guides/arch/livewire-pattern.md` |
| `app()->make(...)` or `new ClassName()` outside provider | Constructor / method injection (C2) | `docs/conventions.md` §10 + `docs/guides/arch/service-pattern.md` |
| `DB::raw("...$user...")` concatenation | Parameterized binding or Eloquent (C3) | `docs/conventions.md` §3.2 |
| Inline `'cache_key'` string | Registered key in `config/cache-keys.php` (C4) | `docs/guides/arch/cache-pattern.md` |
| Entity imports Action/Service/Livewire | Pure entity — only scalars + Model in `fromModel()` (C5) | `docs/guides/arch/entity-pattern.md` |
| DTO imports Model/Entity | DTO carries scalars only (C6) | `docs/guides/arch/data-pattern.md` |
| `execute(array $data)` for 3+ params | `execute(DTO $data)` (C7) | `docs/guides/arch/data-pattern.md` |
| `throw new RuntimeException('business rule')` | `RejectedException` or `$this->fail()` (C8) | `docs/guides/arch/exception-pattern.md` |
| `$fillable = [...]` property | `#[Fillable([...])]` PHP 8.4 attribute (D4) | `docs/guides/arch/model-pattern.md` |
| `$request->all()` to `create()`/`update()` | `$request->only(...)` or DTO `->toArray()` (D5) | `docs/guides/arch/data-pattern.md` |
| FK without `->onDelete()` / `->onUpdate()` | Explicit cascade/set-null/restrict (D6) | `docs/guides/arch/model-pattern.md` |
| Missing `declare(strict_types=1)` | Mandatory except migrations/config (D1) | `docs/conventions.md` §2 |
| `dd()` / `dump()` / `ray()` / `var_dump()` / `die()` in code | Removed (D2) | `docs/conventions.md` §2 |
| Hardcoded English in Blade/notification | `__()` helper, dual `en` + `id` (D3) | `docs/conventions.md` §15 + `docs/guides/arch/modular-pattern.md` §23 |
| `{!! $userContent !!}` for untrusted content | `{{ }}` (auto-escaped); `{!! !!}` only with sanitizer + inline justification | `docs/conventions.md` §3.1 |
| `@php` block with business math in Blade | Computed property on Livewire; Blade binds only | `docs/conventions.md` §14.1 + `docs/guides/arch/livewire-pattern.md` |

### 1.3 Code → Where It Lives

| Need to find | Look here |
|---|---|
| Business logic (Command/Read/Process Actions) | `app/Modules/{Module}/Domain/{Domain}/Actions/` |
| Business rules (Entities) | `app/Modules/{Module}/Domain/{Domain}/Entities/` |
| Persistence (Models) | `app/Modules/{Module}/Domain/{Domain}/Models/` |
| Data transfer (DTOs) | `app/Modules/{Module}/Domain/{Domain}/Data/` |
| State machines (Enums) | `app/Modules/{Module}/Domain/{Domain}/Enums/` |
| UI components (Livewire) | `app/Modules/{Module}/Domain/{Domain}/Livewire/` |
| Authorization (Policies) | `app/Modules/{Module}/Domain/{Domain}/Policies/` |
| Side effects (Events/Listeners) | `app/Modules/{Module}/Domain/{Domain}/Events/`, `Listeners/` |
| Infrastructure logic (Services) | `app/Modules/{Module}/Domain/{Domain}/Services/` |
| Static utilities (Support) | `app/Modules/{Module}/Domain/{Domain}/Support/` or `app/Modules/Core/Support/` |
| Base contracts | `app/Modules/Core/` `Actions/` `Entities/` `Enums/` `Models/` |
| Cross-submodule files (shared Actions, Console, Http) | `app/Modules/{Module}/` (root) |
| Tests | `tests/{Type}/{Module}/{Domain}/{Name}Test.php`, `{Type}` ∈ `Arch`/`Unit`/`Feature`/`Browser` |
| Config | `config/{module}.php` |
| Routes | `routes/web/{module}.php` (subdomain → `{domain}.php`, no module prefix) |
| Translations | `lang/{en,id}/{module}.php` and `lang/{en,id}/{domain}.php` (no subdirs) |
| ADRs / Specs | `docs/adr/adr-{topic}.md` · `docs/specs/{ID}-{feature}.md` |

A module's **primary domain** lives flat at `app/Modules/{Module}/` (`Module/Domain/Module` avoided);
separate domains go under `Domain/{Domain}/`. Split/collapse rules: §1.6 of
`docs/guides/arch/modular-pattern.md`.

### 1.4 Data Flow — The Mutation Path

```
Livewire (validation + RejectedException catch + auth)
  → Command Action::execute(DTO)   (transaction + log + queued event after commit)
    → Entity::fromModel(model)     (business invariants; readonly; throws RejectedException)
    → Model::create/update          (#[Fillable] only; explicit DTO fields)
  → ActionResponse → toast / redirect / re-render
```

Trace this path when debugging: any missing/out-of-order step is a bug or a C1–C8 / D1–D6
violation. Read flow replaces the Action with `BaseReadAction::execute()` — no transaction, no
logging, no event; uses `remember()` / `forget()` against `config/cache-keys.php`. Full diagram:
`docs/architecture.md`.

### 1.5 Module Boundary Awareness

- Each module (and each `Domain/` under it) owns its full stack: Models, Actions, Livewire,
  Events, Policies, Services.
- **Cross-module imports are allowed** — import Models/Actions/Policies from sibling modules
  directly; prefer **events** for fire-and-forget side effects (skip only when no listener exists).
- Shared code (base classes, contracts, exceptions, static utilities) lives in `app/Modules/Core/`.

---

## 2. Workflow — 5-Stage Cycle

Every instruction — question, bug, feature, refactor, docs tweak, audit — runs the cycle,
collapsing steps as the task allows (see workflow contract above). Narrate progress briefly as you
work; final report = changes, verification, caveats, next steps.

| Stage | Question it answers | Output | Verification when done |
|---|---|---|---|
| **1. Understand** | What is asked? Which spec governs? What's affected? How big? | Governing spec + FR/NFR/UC IDs, phase & size (S/M/L), affected modules/layers/files, blockers, reordered instruction list | Spec located (or decision to proceed without), scope bounded, phase & size classified |
| **2. Plan** | Which approach? What contracts? | Read only what the change touches + its governing template/validator (read budget below); choose an approach only when one isn't already dictated; test & doc plan | Approach settled or dictated, read budget respected — no code written yet |
| **3. Implement** | What changes, minimally and cleanly? | Surgical edits (preserve unrelated code), doc/PHPDoc updates, automation/scripts | All planned changes applied, docs/PHPDoc in sync, `git status` shows only intended files |
| **4. Verify** | Is anything broken or lost? | `git status`/`diff` review, style checks, targeted & arch-guard scans; full suite only on-demand | Change-type gates pass; arch-guard clean or deviations noted; pre-existing warnings reported, never chased |
| **5. Summarize** | What was delivered, what remains? | Per-scope commit `type(scope): desc` — **no waiting to be asked** — then report (changes, verification, caveats, next steps) | Clean commit(s), report delivered, repo left cleaner than found |

**Size triage:**

| Size | Files / Scope | Read budget | Workflow |
|---|---|---|---|
| **S** | ≤3 files, single module | ≤5 files | Collapsed cycle (`grep → act → verify`), no checkpoint |
| **M** | 4–10 files, single module or close cluster | ≤10 files | Standard cycle + **one checkpoint before commit** |
| **L** | >10 files, multi-module, cross-cutting | Split | **Split into sessions**, per-session report + `git status`/`diff` review |

**Read budget** = the files being changed + the governing template/validator + targeted greps.
Context files (factories, listeners, observers, configs, seeds, sibling docs) are read only when an
assertion in the change depends on their exact behavior — otherwise **grep first**. Exceeding the
budget while hunting for "context" is a signal to **Split**, not to keep reading.

**Classify before acting** — phase (`docs/specs/index.md`) and size (above) gate the verification
depth and checkpoint cadence. Classify in Stage 1; if already M/L, state it before reading further.

**Checkpoint** (M) = show `git status` + `git diff --stat` + intended-files list before committing.
**Session** (L) = a self-contained sub-task with its own report, defined before starting; an L
task is a list of sessions, never one continuous pass.

**Committing** — after **every completed scope**, without waiting for instruction: run the
pre-commit baseline (§4), stage only intended files (never secrets), commit with
`type(scope): desc`. This applies to every size, S included.

---

## 3. Phase Classification

Phase → spec-ID → module mapping and status live in `docs/specs/index.md` (12 phases, Spec-zero
through Maintenance). Locate the phase, then the spec ID, then the FR/NFR/UC IDs (each spec lists
them in §5 FR, §6 NFR, §4 UC per `docs/templates/spec-template.md`).

**Instruction ordering** when a message batches multiple instructions: follow the user's sequence,
grouping same-area edits; reorder only when a dependency forces it. Never spend effort scoring.

---

## 4. Verification Strategy — Change-Type Matrix

**Core principle:** ask "can I verify this without running tests?" before reaching for the suite
(suite: ~2GB+ RAM, 10+ min — never per-edit).

| Change type | Lightest verification first |
|---|---|
| Translation keys | `vendor/bin/pint --dirty --test` + tinker echo + `LangChecker` |
| Config / docs / pure markdown | Visual inspection + `python3 tools/scan_doc_links.py` |
| Blade / CSS / JS | `npm run build` + `npx prettier --check <file>` |
| Single method refactor | `php artisan test --compact --filter={ClassName}` |
| Cross-module refactor | `vendor/bin/pest --testsuite={Module}` |
| New feature / business logic | Full suite ONCE, after all changes batched (C7, D1, D4, D6 checks) |
| Architecture / base class change | Targeted suites + arch-guard batch (§5) |

**Pre-commit baseline — every commit:**

1. `git status` + `git diff` + `git diff --stat` — only intended files, no drops.
2. `vendor/bin/pint --dirty --test --format agent` (PHP + Blade).
3. `npx prettier --check <file>` for non-PHP (CSS/JS/JSON; `*.php` / `*.blade.php` / `*.md` ignored).
4. `npm run build` for Blade/CSS/JS changes; visual inspection for `*.md`/config.
5. Targeted tests: `php artisan test --compact --filter={Class}` or `--testsuite={Module}`.
6. Arch-guard batch (§5) — every commit, never skipped.

**Full suite on-demand only:** `php artisan test --compact` on merge-day or when the user asks.
Default is targeted checks.

---

## 5. Tooling — Arch-Guard Scanners

**Automation-First:** before manual or repeated work, check `tools/` and run the matching scanner.
Never redo by hand what a script does. If a recurring pattern has no script, add one
(`tools/scan_*.py`). Full CLI flags, output schema, and per-scanner inventory:
`tools/README.md`.

**Standard batched run** (the §4 arch-guard gate):

```bash
python3 tools/scan_violations.py          # C1–C8, D1–D6, P2, P5
python3 tools/scan_class_contracts.py     # Action/Entity/DTO/Model/Enum/Event/Policy/Service/Listener
python3 tools/scan_conventions.py         # D1 strict_types, D4 Fillable, D2 debug calls
python3 tools/scan_naming.py              # file + class naming
python3 tools/scan_security.py            # XSS, SQLi, CSRF, mass assignment, auth, secrets, uploads, rate limiting, dep audit
python3 tools/scan_doc_links.py           # broken file/anchor links
python3 tools/scan_spec_tests.py          # spec↔test traceability (FR/NFR/UC)
```

**Composer shortcuts:**

| Command | What it runs |
|---|---|
| `composer arch` | `scan_doc_links` + `scan_arch_patterns` + `scan_module_boundaries` + `scan_ui_consistency` (summary) |
| `composer arch:strict` | Same with `--strict` (exit 1 on findings) |
| `composer lint` | `npm run lint` + `vendor/bin/pint --test` |
| `composer test` | `php artisan optimize:clear` + `php artisan test` |
| `composer test:suite {Module}` | `php artisan optimize:clear` + `vendor/bin/pest --testsuite={Module}` |
| `composer quality` | lint + arch + test |
| `composer quality:full` | format + test:coverage |

---

## 6. Testing — Spec-Driven Minimalism

**Coverage = spec requirements covered, not lines of code.** A requirement with no test is a
**spec gap** (fill it). A test with no requirement is **orphan noise** (remove it). Legacy
per-layer percentage targets are gone — don't manufacture padding tests.

**Pattern by what you're testing:**

| What | Pattern | Where the rule lives |
|---|---|---|
| Command Action (mutation) | Arrange (factory + DTO) → Act (execute) → Assert (`assertModelExists` + `ActionResponse`) | `docs/guides/arch/testing-pattern.md` |
| Read Action (query) | Arrange (seed) → Act (execute) → Assert (typed return, collection shape) | same |
| Entity | Only the business-rule methods a requirement names; no DB | same |
| DTO | Only the shape the spec's §6 data contract defines; no DB | `docs/guides/arch/data-pattern.md` |
| Enum | Only `label()` / transitions the spec lists | `docs/guides/arch/enum-pattern.md` |
| Livewire | Render, mount, form submission, authorization; `actingAs()` | `docs/guides/arch/livewire-pattern.md` |
| Policy | `allow` / `deny` per role; no DB beyond the model | `docs/guides/arch/policy-pattern.md` |

**Naming:** descriptions use `{SpecID}-{ReqID}: description` grouped under
`describe("{SpecID}: …")`, e.g. `test("7C5WM-FR12: internship cannot start without placement")`.

**Layer strategy (DB or no DB?):**

| Layer | Test type | DB? | Why |
|---|---|---|---|
| Enum, Entity, DTO, Policy, Support | Unit (`tests/Unit/`) | No | Pure logic, no I/O — fast |
| Action, Livewire, Console, Process | Feature (`tests/Feature/`) | Yes | Real DB; `LazilyRefreshDatabase` |
| Module structure / boundaries | Arch (`tests/Arch/`) | No | Static, file-level |
| Browser flows | Browser (`tests/Browser/`) | Yes | Full-stack; use sparingly |

**Health indicators:** passes alone / fails in suite → shared state or ordering (`LazilyRefreshDatabase`);
`Class "X" not found` → `composer dump-autoload`; `SQLSTATE[HY000]` → `php artisan migrate:fresh`;
timeout → infinite loop or undrained queue (`Queue::fake()`); flaky → race or missing refresh —
isolate; was failing before your change → pre-existing, flag it, don't fix unless asked.

**Mocking:** never mock Eloquent or the Query Builder (use real DB); mock only external boundaries
(`Http::fake()`, mail, queue, filesystem, cache, notifications). Full rules: `docs/conventions.md`
§12.1 + `docs/guides/arch/testing-pattern.md`.

**TDD build order** (per workflow pattern §21 of `docs/guides/arch/modular-pattern.md`): Docs →
Migration/Model → Enum → Entity → DTO → Command → Read → Process → Livewire → Policy → Console;
tests follow, spec-traceable from the start.

---

## 7. Documentation Discipline

### 7.1 Two-Tier Model

| Tier | File | Audience | What goes in |
|---|---|---|---|
| **Conceptual** | `docs/refs/modules/{module}.md` | Architects, devs, stakeholders | Purpose, principles, business rules, boundary — no implementation details |
| **Reference** | `docs/refs/modules/{module}-reference.md` | Devs, reviewers | File paths, class names, schemas, dependency graphs, routes, config keys |

Rule of thumb: *why* → conceptual; *what* / *how* → reference. Conceptual docs never list class
names; reference docs never explain rationale.

### 7.2 Drift Detection

| Question | How to check |
|---|---|
| Doc's file listing matches the directory? | `ls app/Modules/{Module}/Domain/{Domain}/` vs doc |
| Actions table lists all current Actions? | `find app/Modules/{Module}/Domain/{Domain}/Actions -name '*Action.php'` |
| Entity description matches the methods? | Read the Entity class |
| Enum cases in doc match the code? | Read the Enum class |
| Migration descriptions match? | `ls database/migrations/` |
| Cross-references still valid? | `python3 tools/scan_doc_links.py --no-external` |

### 7.3 Mismatch Resolution — Git History First

When code and docs disagree, **neither is automatically authoritative** — both can be stale.

1. `git log -p -- {file}` and `git blame {file}` for **both** sides.
2. Does a commit message explain the intent? Update the other side to match.
3. If neither side explains it → treat as a finding: report, don't silently decide.

SSoT priority wins when sources conflict, but never trust blindly — verify via git history and
intent.

### 7.4 When to Update Docs

| Code change | Doc to update |
|---|---|
| New Action added | Module reference doc (Actions table) |
| Entity method changed | Module conceptual doc (business rules) |
| Enum case added/removed | Module reference doc (enum table) |
| New migration | Module reference doc (schema section) |
| New module created | `docs/refs/modules/index.md` + conceptual + reference |
| Config key added | Module reference doc (config section) |
| Route added/changed | Module reference doc (Routes table) |
| Base class method changed | `docs/guides/arch/{pattern}-pattern.md` |
| Invariant added/changed (C/D rule) | This file (`AGENTS.md`) |

### 7.5 History Discipline (Git as SoT)

No inline `Last updated` metadata — freshness is tracked via git:

```bash
git log --follow -- <file>        # history of a doc
git diff -- <file>                # what changed in this branch
git log --since="14 days ago" --oneline -- docs/
```

Commit format: `type(scope): description` — types `feat`, `fix`, `refactor`, `docs`, `chore`,
`test`, `perf`, `security`; scope = module name.

### 7.6 Link Integrity (before every commit)

1. Every `[text](path)` resolves to an existing file.
2. Every `[text](path#anchor)` matches an existing heading.
3. No content duplicated — cross-reference instead.
4. Footer is `## Quick References` (not `## References`).

Run `python3 tools/scan_doc_links.py --no-external` to validate.

---

## 8. Version Senses

The version that ships is the one on the VPS, not `composer.json`. Drift between local, tag, and
VPS is the most common release bug. Compare `git describe --tags` locally vs
`ssh internara-vps "git -C ~/apps/internara describe --tags"` and the Docker image (GIT_URL
`#vX.Y.Z` in `docker-compose.yml`). Fix: bump `version`, tag `vX.Y.Z`, push the tag (deploys via
`release.yml`). Full checklist: `docs/guides/upgrading.md`.

---

## 9. Decision Gate — Reason Only Until Obvious

Most actions need no deliberative loop: a governing requirement, template/validator, or existing
sibling makes the answer obvious — then **act**. Deep reasoning is reserved for genuine conflicts
(spec vs code, missing contract) and is done once per conflict, not per message:

1. **Check** — is the answer dictated by spec/template/validator/sibling? If yes → act.
2. **Evaluate** — matches FR/NFR/UC? Respects layer boundaries? Does ONE thing?
3. **Verify** — lint + targeted checks pass; no debug calls; `__()` for user strings.
4. **Decide** — Split / Escalate / Defer.
   - **Split** only when size is **L** or scope grew mid-session — inform the user, define the sessions (§2).
   - **Escalate** only when a governing spec is missing/ambiguous or the decision changes
     architecture — surface it, don't guess. No forced "2+ approaches" matrices.

---

## 10. Self-Improvement Loop (Judgment-Based)

`docs/` is authored by and for **human developers** — agents never write documentation there for
themselves. When a session produced a durable decision or recurring pattern:

- Propose it as a GitHub issue if it needs human action (the human decides where it lands).
- Skip silently when nothing novel emerged — do not auto-write.

---

## 11. Project Snapshot

| Fact | Value |
|---|---|
| **Version** | v0.15.9 — Stabilization |
| **Modules** | 19 = 17 business + UI + Core |
| **Stack** | PHP 8.4 · Laravel 13 · Livewire 4 · TallstackUI v4 · Tailwind v4 · Alpine 3 · Pest 4 · Spatie (activitylog, medialibrary, model-status, permission) |
| **DB** | SQLite default / MySQL 8 / MariaDB 10.6 / PostgreSQL 15 |
| **Deploy** | Shared hosting ($5/mo) or VPS / Docker Compose |
| **License** | MIT · single-tenant (no `tenant_id` — one instance per school) |

Module roster, dependency graph, and module health: `docs/refs/modules/index.md`.

---

## 12. Quick References

- `docs/architecture.md` — 4-layer architecture, data flow, dependency rules R1–R7
- `docs/conventions.md` — C1–C8, D1–D6 invariants, pre-commit & code-review checklists
- `docs/guides/arch/index.md` — pattern catalog (one link per pattern)
- `docs/guides/arch/modular-pattern.md` — modular architecture deep-dive (§1–§23)
- `docs/specs/index.md` — 62 feature specs + 2 meta, grouped in 12 phases
- `docs/adr/index.md` — 16 ADRs across Foundation / Observability / Quality / Proxy / Strategy
- `docs/philosophy.md` — 7 guiding principles + core values table
- `docs/refs/modules/index.md` — module conceptual + reference doc index
- `docs/templates/index.md` — 10 doc-type templates (load when a doc's concern is touched)
- `tools/README.md` — scanner CLI flags, output schema, full scanner inventory
- `docs/guides/upgrading.md` — version-up checklist
- `CHANGELOG.md` — release notes
- `CONTRIBUTING.md` — contribution flow
- `SECURITY.md` — vulnerability disclosure
- `CODE_OF_CONDUCT.md` — community standards