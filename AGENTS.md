# AGENTS.md — Navigation Hub for AI Agents

Mental model and operating contract for AI agents working on Internara. This file is the
**navigation hub**, not the rule book: it points to the authoritative documentation under `docs/`
and never duplicates it.

**Single Source of Truth (SSoT) priority** when sources conflict: `adr > specs > guides > code > refs`.
Higher wins — but never trust blindly: verify via `git log --follow` / `git blame` and intent before
acting. If history is silent or contradictory, treat as a finding and justify the decision.

> **Workflow contract:** Every instruction runs the full silent cycle
> `UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE`. The 5 steps are internal reasoning — never
> narrate them. Surface to the user only: ambiguity, scope/structure/behavior changes, an L-size
> session plan, one M-size pre-commit checkpoint, and the final report.
>
> **Spec-first doctrine (non-negotiable):** no behavior without a requirement ID from a governing
> spec in `docs/specs/`. If none exists, write the spec first — never fix-first. If spec and code
> disagree, fix code to spec; if spec is demonstrably wrong, amend spec with a recorded decision
> first, then align code and tests.

---

## 1. Documentation Map — Where to Find What

Every rule, pattern, and decision lives in `docs/`. AGENTS.md never re-states them — it routes you
to the right file. When a doc's concern is touched, load the matching file (or template); do not
re-derive the rule from this hub.

### 1.1 Authoritative Docs

| Concern | File | What it owns |
|---|---|---|
| Vision, values, what-we-do-not-do | [`docs/philosophy.md`](docs/philosophy.md) | "Why we exist" — the 7 principles + core values table |
| 4-layer architecture, data flow, layer rules, circular-dependency safety | [`docs/architecture.md`](docs/architecture.md) | High-level architecture + Action Triad intro + dependency rules R1–R7 |
| Coding conventions: PHP style, security, naming, performance, testing, localization, theming | [`docs/conventions.md`](docs/conventions.md) | C1–C8, D1–D6 invariants, pre-commit & code-review checklists |
| Pattern catalog (Action Triad, Entity/Model/DTO/Enum, Event, Cache, Logging, Livewire, Service, Support, Repository, Policy, UI/UX, Testing) | [`docs/guides/arch/index.md`](docs/guides/arch/index.md) | One link per pattern → deep-dive `*-pattern.md` |
| Modular architecture deep-dive (SRP, base classes, contracts, naming, accessibility, localization patterns) | [`docs/guides/arch/modular-pattern.md`](docs/guides/arch/modular-pattern.md) | §1–§23 pattern catalog with industry-standard alignment |
| Feature / module specs (62 feature specs + 2 meta — one FR/NFR/UC set per spec) | [`docs/specs/index.md`](docs/specs/index.md) | Specs grouped in 12 phases; status per spec; spec template is `docs/templates/spec-template.md` |
| ADRs — why each architectural decision was made | [`docs/adr/index.md`](docs/adr/index.md) | 16 ADRs in 6 groups (Foundation / Observability / Quality / Proxy / Strategy) |
| Module conceptual docs (purpose, principles, business rules) | [`docs/refs/modules/{module}.md`](docs/refs/modules/index.md) | One conceptual + one reference per module |
| Tooling — scanners, CLI flags, output schema, scanner inventory | [`tools/README.md`](tools/README.md) | The full scanner reference lives there; only the runner command is duplicated below |
| Doc-type templates (skeleton + writing rules) | [`docs/templates/index.md`](docs/templates/index.md) | 10 templates — load the matching one when a doc's concern is touched |

### 1.2 Pattern & Convention Quick Reference — One-Liners

These are the **recognition cues** (one row per common pattern). The **full rule, why, and
exception list** for every row lives in `docs/conventions.md` and `docs/guides/arch/*-pattern.md`.

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

### 1.3 Code → Where It Lives (Navigation Table)

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
| Base contracts | `app/Modules/Core/Actions/`, `app/Modules/Core/Entities/`, `app/Modules/Core/Enums/`, `app/Modules/Core/Models/` |
| Cross-submodule files (shared Actions, Console, Http) | `app/Modules/{Module}/` (root) |
| Tests | `tests/{Type}/{Module}/{Domain}/{Name}Test.php` where `{Type}` ∈ `Arch`, `Unit`, `Feature`, `Browser` |
| Config | `config/{module}.php` |
| Routes | `routes/web/{module}.php` (subdomain → `{domain}.php` no module prefix) |
| Translations | `lang/{en,id}/{module}.php` and `lang/{en,id}/{domain}.php` (no subdirs) |
| ADRs | `docs/adr/adr-{topic}.md` |
| Specs | `docs/specs/{ID}-{feature}.md` |

A module's **primary domain** lives flat at `app/Modules/{Module}/` to avoid redundant nesting
(`Module/Domain/Module`); separate domains go under `Domain/{Domain}/`. Split/collapse rules: §1.6
of `docs/guides/arch/modular-pattern.md`.

### 1.4 Data Flow — The Mutation Path

```
User
  → Livewire component            (validation + RejectedException catch + auth gate)
    → Command Action::execute(DTO)   (transaction + log + event; auto-dispatches queued events after commit)
      → Entity::fromModel(model)     (business invariants; final readonly; throws RejectedException)
      → Model::create/update(values)  (#[Fillable] only; explicit DTO fields)
      → $this->log()                  (SmartLogger dual-channel; PII-masked)
      → $this->dispatchEvent(...)     (queued; fires after DB::transaction() commits)
    ← ActionResponse
  ← Toast / redirect / re-render
```

When debugging or reviewing, trace this path. If any step is missing or out of order, it's a bug
or a C1–C8 / D1–D6 violation. Read flow replaces Action with `BaseReadAction::execute()` — no
transaction, no logging, no event; uses `remember()` / `forget()` against `config/cache-keys.php`.

### 1.5 Module Boundary Awareness

- Each module owns its full stack: Models, Actions, Livewire, Events, Policies, Services.
- Each Domain under `app/Modules/{Module}/Domain/{Domain}/` owns its domain's full stack.
- **Cross-module imports are allowed** — import Models, Actions, or Policies from sibling modules
  directly. Prefer **events** for fire-and-forget side effects; only when there's no listener
  should you skip the event (per `BaseAction::dispatchEvent()` docblock).
- Shared code (base classes, contracts, exceptions, static utilities) lives in `app/Modules/Core/`.
- Pattern: `adr > specs > guides > code > refs` — see SSoT priority above.

---

## 2. Agent Workflow — 5-Stage Silent Cycle

Every instruction — one-line question, bug report, feature, refactor, docs tweak, or audit — runs
the full cycle. Stages are internal; never narrate them. Surface only the items listed in the
opening note.

| Stage | Question it answers | Output | Verification when done |
|---|---|---|---|
| **1. Understand** | What is asked? Which spec governs? What's affected? How big? | Governing spec + FR/NFR/UC IDs, phase & size (S/M/L), affected modules/layers/files, blockers, reordered instruction list | Exit: spec located (or decision to proceed without), scope bounded, phase & size classified, instruction order set |
| **2. Plan** | What exists? Which approach? What contracts? | Read inventory, 2+ considered approaches, chosen design (Action triad, Entity/DTO/Model contracts, error & cache strategy), test & doc plan | Exit: context inventoried, approach documented, contracts sketched, test & doc plan clear — no code written yet |
| **3. Implement** | What changes, minimally and cleanly? | Surgical code edits (preserve unrelated code), doc/PHPDoc updates, automation/scripts | Exit: all planned changes applied, docs/PHPDoc in sync, no drift, `git status` shows only intended files |
| **4. Verify** | Is anything broken or lost? | `git status`/`diff` review, style checks, targeted & arch-guard scans; full suite only on-demand | Exit: change-type-appropriate gates pass; arch-guard clean or deviations justified/recorded; no silent tolerance of pre-existing warnings |
| **5. Summarize** | What was delivered, what remains? | Staged commit `type(scope): desc`; final report (changes, verification, caveats, next steps) | Exit: clean commit(s), report delivered, repo left cleaner than found |

**Size triage:**

| Size | Files / Scope | Workflow |
|---|---|---|
| **S** | ≤3 files, single module | Standard cycle, no checkpoint needed |
| **M** | 4–10 files, single module or close cluster | Standard cycle + **one checkpoint before commit** |
| **L** | >10 files, multi-module, cross-cutting | **Split into sessions**, per-session report + `git status`/`diff` review, never one pass |

**Classify before acting** — phase (12 phases in `docs/specs/index.md`) and size (S/M/L above) gate
the verification depth and the checkpoint cadence.

---

## 3. Phase Classification & SDLC Mapping

Phases from `docs/specs/index.md` (grouped). Locate the phase, then the spec ID, then the FR/NFR/UC
IDs. Every spec lists them in §5 (FR), §6 (NFR), §4 (UC) per the spec template.

| Phase | Specs | Module family |
|---|---|---|
| **0** Spec-zero | QLHDO | Core |
| **1** Foundation | D2FT3, FB792, ZT6VS, SE5Q9, C8F0D, J68GZ, I1BCV, 89SRA, NUCY3, T4B26, 2CF4Y, 1PGM4, B114U | Core |
| **2** Configuration | 8NZAU, VEJCX, C9ZB6, YB22J, 52O1I, 81SMS | Setup / Settings / Academics |
| **3** Identity & Auth | K8HP1, 8XMYS, YB7RG, TXR2H, 3S55V, CKKZC, D9TKW, CQVSK, SHQ1J, OCEMS | User / Auth / SysAdmin / Core |
| **4** Institutional | 4HWSB, XW6F5 | Academics |
| **5** Partnerships | XI3LB, NTHQA | Partners |
| **6** Programs | 7C5WM, IT0OE | Program |
| **7** Enrollment | MBB5R, J9GBH, 920SO, 95EVB, O2KCR, EWCZ0 | Enrollment / User |
| **8** Daily Ops | 1KSWL, 2EHSE, 3RU9S | Journals / Incident |
| **9** Assessment | ARDA6, AXKZW, T657Z | Assessment / Evaluation / Assignment |
| **10** Certification | PKYX6, ZUFG8, J0M04, WQGTP, 7UB7S | Document / Certification / Core |
| **11** Reporting | R6BMW, 7H5D6 | Reports / Document |
| **12** Maintenance | 8FVZA, HBXCI, 7HNCF, E1MSJ, 9YUUK, 06IB6, 3UOZP | Core / SysAdmin |

**Instruction ordering** when a message batches multiple instructions:
decompose → score by impact-to-effort ratio → sort → honor dependencies → group same-area work.
Surface the resulting order only when it differs from the user's sequence.

---

## 4. Verification Strategy — Change-Type Matrix

**Core principle:** ask "can I verify this without running tests?" before reaching for the suite.
The full suite consumes ~2GB+ RAM and 10+ minutes — never per-edit.

| Change type | Lightest verification first |
|---|---|
| Translation keys | `vendor/bin/pint --dirty --test` + tinker echo + `LangChecker` |
| Config / docs / pure markdown | Visual inspection + `python3 tools/scan_doc_links.py` |
| Blade / CSS / JS | `npm run build` + `npx prettier --check <file>` |
| Single method refactor | `php artisan test --compact --filter={ClassName}` |
| Cross-module refactor | `vendor/bin/pest --testsuite={Module}` |
| New feature / business logic | Full suite ONCE, after all changes batched (per C7, D1, D4, D6 checks) |
| Architecture / base class change | Targeted suites + arch-guard batch (see §5) |

**Pre-commit baseline (every commit):**

1. `git status` + `git diff` + `git diff --stat` — only intended files, no drops.
2. `vendor/bin/pint --dirty --test --format agent` (PHP + Blade).
3. `npx prettier --check <file>` for non-PHP (CSS/JS/JSON; `*.php` / `*.blade.php` / `*.md` ignored).
4. `npm run build` for Blade/CSS/JS changes; visual inspection for `*.md`/config.
5. Targeted tests: `php artisan test --compact --filter={Class}` or `--testsuite={Module}`.
6. Arch-guard batch (see §5) — every commit, never skipped.

**Full suite on-demand only:** `php artisan test --compact` on merge-day or when the user asks.
Default is targeted checks.

---

## 5. Arch-Guard Tooling — What to Run When

**Automation-First:** before doing manual or repeated work, check `tools/` and run the matching
scanner. Never redo by hand what a script does. If a recurring pattern has no script, add one
(`tools/scan_*.py` — full CLI in `tools/README.md`).

### 5.1 Standard Batched Run

```bash
# Architecture invariants + class contracts
python3 tools/scan_violations.py          # C1–C8, D1–D6, P2, P5
python3 tools/scan_class_contracts.py     # Action/Entity/DTO/Model/Enum/Event/Policy/Service/Listener

# Code quality
python3 tools/scan_conventions.py         # D1 strict_types, D4 Fillable, D2 debug calls
python3 tools/scan_naming.py              # file + class naming

# Security
python3 tools/scan_security.py            # XSS, SQLi, CSRF, mass assignment, auth, secrets, uploads, rate limiting, dep audit

# Docs integrity
python3 tools/scan_doc_links.py           # broken file/anchor links
python3 tools/scan_spec_tests.py          # spec↔test traceability (FR/NFR/UC)
```

### 5.2 Composer Shortcuts

| Command | What it runs |
|---|---|
| `composer arch` | `scan_doc_links` + `scan_arch_patterns` + `scan_module_boundaries` + `scan_ui_consistency` (summary) |
| `composer arch:strict` | Same scanners with `--strict` (exit 1 on findings) |
| `composer lint` | `npm run lint` + `vendor/bin/pint --test` |
| `composer test` | `php artisan optimize:clear` + `php artisan test` |
| `composer test:suite {Module}` | `php artisan optimize:clear` + `vendor/bin/pest --testsuite={Module}` |
| `composer quality` | lint + arch + test |
| `composer quality:full` | format + test:coverage |

### 5.3 One-Liner Scanners (full inventory in `tools/README.md`)

| Scanner | What it checks |
|---|---|
| `scan_files.py` / `scan_architecture.py` | File counts, LoC, component counts per module (metadata only) |
| `scan_arch_patterns.py` | Architecture pattern adherence (`ARCH_*` rules) |
| `scan_module_boundaries.py` | Cross-module boundary checks (`MODULE_*` rules) |
| `scan_ui_consistency.py` | UI/component consistency (`UI_*` rules) |
| `scan_dead_code.py` | Unregistered observers, orphan events, unused DTOs/Actions/Jobs |
| `scan_spec_tests.py` | Spec↔test coverage (`SPEC_TEST_*` rules) |
| `scan_issues.py` | GitHub issues by module/severity |
| `scan_tests.py` | Per-module test result parser |
| `tool_runner.py --scanner a,b --module M` | Orchestrate multiple scanners with shared cache |
| `clean_outputs.py --prune` | Keep latest timestamped output per category |

All scanners accept `--module {Name}`, `--format summary|text|html|markdown`, `--output <path>`,
`--json`, `--strict`, `--quiet`, `--severity high`, `--baseline file.json`, `--workers N`. Default
output: `tools/outputs/{YYYYMMDDHHMMSS}-{scan_name}.json` (directory is gitignored).

---

## 6. Testing — Spec-Driven Minimalism

**Coverage = spec requirements covered, not lines of code.** A requirement with no test is a
**spec gap** (fill it). A test with no requirement is **orphan noise** (remove it). The legacy
per-layer percentage targets were removed because they produced padding tests — use them only as an
internal diagnostic, never as a mandate.

### 6.1 Pattern by What You're Testing

| What | Pattern | Where the rule lives |
|---|---|---|
| Command Action (mutation) | Arrange (factory + DTO) → Act (execute) → Assert (`assertModelExists` + `ActionResponse`) | `docs/guides/arch/testing-pattern.md` |
| Read Action (query) | Arrange (seed) → Act (execute) → Assert (typed return, collection shape) | same |
| Entity | Only the business-rule methods a requirement names; no DB | same |
| DTO | Only the shape the spec's §6 data contract defines; no DB | `docs/guides/arch/data-pattern.md` |
| Enum | Only `label()` / transitions for the cases/rules the spec lists | `docs/guides/arch/enum-pattern.md` |
| Livewire | Render, mount, form submission, authorization; `actingAs()` | `docs/guides/arch/livewire-pattern.md` |
| Policy | `allow` / `deny` for each role; no DB beyond the model | `docs/guides/arch/policy-pattern.md` |

**Naming:** test descriptions use `{SpecID}-{ReqID}: description` grouped under
`describe("{SpecID}: short description")`. Example: `test("7C5WM-FR12: internship cannot start
without placement")`. The ID lives in the file's `> **Spec ID:**` metadata, the filename
`{ID}-{feature}.md`, and the index `ID` column.

### 6.2 Layer Strategy (DB or no DB?)

| Layer | Test type | DB? | Why |
|---|---|---|---|
| Enum, Entity, DTO, Policy, Support | Unit (`tests/Unit/`) | No | Pure logic, no I/O — fast |
| Action, Livewire, Console, Process | Feature (`tests/Feature/`) | Yes | Real DB; `LazilyRefreshDatabase` |
| Module structure / boundaries | Arch (`tests/Arch/`) | No | Static, file-level |
| Browser flows | Browser (`tests/Browser/`) | Yes | Full-stack; use sparingly |

### 6.3 Health Indicators

| Symptom | Diagnosis |
|---|---|
| Passes in isolation, fails in suite | Shared state or ordering — check `LazilyRefreshDatabase` |
| `Class "X" not found` | Autoload stale — `composer dump-autoload` |
| `SQLSTATE[HY000]` | Migration missing — `php artisan migrate:fresh` |
| Test times out | Infinite loop or undrained queue — add `Queue::fake()` |
| Flaky test | Race condition or missing `RefreshDatabase` — isolate |
| Was failing before your change | Pre-existing — flag it, don't fix unless asked |

Mocking rules: never mock Eloquent or the Query Builder (use real DB); mock only external
boundaries (HTTP `Http::fake()`, mail, queue, filesystem, cache, notifications). Full rules in
`docs/conventions.md` §12.1 and `docs/guides/arch/testing-pattern.md`.

### 6.4 TDD Build Order (per workflow pattern §21 of `docs/guides/arch/modular-pattern.md`)

Docs → Migration/Model → Enum → Entity → DTO → Command → Read → Process → Livewire → Policy →
Console. Tests follow the same order, written spec-traceable from the start.

---

## 7. Documentation Discipline

### 7.1 Two-Tier Model

| Tier | File | Audience | What goes in |
|---|---|---|---|
| **Conceptual** | `docs/refs/modules/{module}.md` | Architects, devs, stakeholders | Purpose, design principles, business rules, module boundary — no implementation details |
| **Reference** | `docs/refs/modules/{module}-reference.md` | Devs, reviewers | Full API reference — file paths, class names, table schemas, dependency graphs, route tables, config keys |

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

1. `git log -p -- {file}` and `git blame {file}` for **both** the code and the doc.
2. Look for the intent — does a commit message explain the change?
3. If a commit explains it, update the other side to match the documented intent.
4. If neither side explains it, treat as a finding — report, don't silently decide.

SSoT priority (`adr > specs > guides > code > refs`) wins when sources conflict, but never
trust blindly — even the highest must be verified via git history and intent.

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

No inline `Last updated` metadata in markdown files. Freshness is tracked via git:

```bash
git log --follow -- <file>        # history of a doc
git diff -- <file>                # what changed in this branch
git log --since="14 days ago" --oneline -- docs/
```

Commit message format: `type(scope): description` — types `feat`, `fix`, `refactor`, `docs`,
`chore`, `test`, `perf`, `security`; scope = module name.

### 7.6 Link Integrity (before every commit)

1. Every `[text](path)` resolves to an existing file
2. Every `[text](path#anchor)` matches an existing heading
3. No content duplicated — cross-reference instead
4. Standard footer: `## Quick References` (not `## References`)

Run `python3 tools/scan_doc_links.py --no-external` to validate.

---

## 8. GitHub Version Senses

The version that ships is the one on the VPS, not the one in `composer.json`. Drift between
local, tag, and VPS is the most common release bug.

| Question | How to check |
|---|---|
| What version is the code at? | `grep '"version"' composer.json package.json` + `git describe --tags` + `git tag --sort=-v:refname \| head` |
| What version is on VPS? | `ssh internara-vps "cat ~/apps/internara/composer.json \| grep version; git -C ~/apps/internara describe --tags; git -C ~/apps/internara log --oneline -1"` |
| Did the latest tag reach the VPS? | Compare local `git describe --tags` with `ssh internara-vps "git -C ~/apps/internara describe --tags"` — must match the pushed `vX.Y.Z` |
| Is the Docker image stale? | `GIT_URL` in `docker-compose.yml` (`#main` vs `#vX.Y.Z`), `docker images internara-app` `CREATED`, `docker exec ... cat /app/public/index.php \| head` |
| Why is VPS on an older version? | `composer.json` version differs from last pushed tag → bump `version`, `git tag vX.Y.Z`, `git push origin vX.Y.Z` — `release.yml` deploys on tag push |

Upgrading checklist: `docs/guides/upgrading.md`.

---

## 9. Metacognitive Loop — Per-Reasoning-Step

```
CONSTRUCT → EVALUATE → VERIFY → DECIDE
```

1. **CONSTRUCT** — read relevant docs/code; verify paths and signatures; consider 2+ approaches.
2. **EVALUATE** — matches FR/NFR/UC from the governing spec? Respects layer boundaries? Does ONE thing?
3. **VERIFY** — lint + static analysis + tests pass; no debug calls; `__()` for user strings.
4. **DECIDE** — Accept / Revise / Split / Escalate / Defer.
   - **Split** when size is **L** or scope grew — inform the user, propose a session plan, never push through.
   - **Escalate** when the decision changes scope/architecture, or a governing spec is missing/ambiguous — surface it, don't guess.

---

## 10. Self-Improvement Loop (Judgment-Based)

When a session produced a **durable decision**, **non-obvious trap/correction**, or **recurring
pattern** worth a future session knowing:

- Record as a concise note in `docs/` where a future reader would look (or open a GitHub issue if
  it needs action).
- Durable architectural decisions become an ADR in `docs/adr/`.
- Not an automatic step — skip when nothing novel emerged.

Capture destinations: `docs/adr/adr-{topic}.md` for decisions, the matching
`docs/guides/arch/*-pattern.md` for pattern refinements, the spec file for requirement clarifications.

---

## 11. Project Snapshot — Quick Reference

| Fact | Value |
|---|---|
| **Version** | v0.15.9 — Stabilization |
| **Modules** | 19 = 17 business + UI + Core |
| **Stack** | PHP 8.4 · Laravel 13 · Livewire 4 · TallstackUI v4 · Tailwind v4 · Alpine 3 · Pest 4 · Spatie (activitylog, medialibrary, model-status, permission) |
| **DB** | SQLite default / MySQL 8 / MariaDB 10.6 / PostgreSQL 15 |
| **Deploy** | Shared hosting ($5/mo) or VPS / Docker Compose |
| **License** | MIT |
| **Single-tenant** | No `tenant_id` overhead — one instance per school |

**Module roster** (see `docs/refs/modules/index.md` for full dependency graph):

Core · UI · Auth · User · SysAdmin · Setup · Settings · Academics · Program · Enrollment ·
Assessment · Evaluation · Assignment · Journals · Incident · Partners · Certification · Reports ·
Document.

**Module health** (per `docs/refs/modules/{module}.md` and the AGENTS Snapshot above):

- **Production-Ready:** Core, Auth, User, Settings, Setup, SysAdmin, Academics
- **Stable-Needs Attention:** Program, Partners, Enrollment, Journals, Incident, Assignment, Reports
- **Needs Work P0:** Assessment, Certification, Document
- **Skeleton:** Evaluation

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
