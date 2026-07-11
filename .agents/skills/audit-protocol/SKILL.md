---
name: audit-protocol
description: SDLC Phase: ANALYSIS. Systematic multi-layer codebase audit enforcing conventions, architecture patterns, security, and industry best practices. Produces structured findings in GitHub Issues with actionable fix recommendations.
downstream:
  - roadmap-planning
  - code-refactoring
  - security-audit
---

# Audit Protocol

> **Prerequisite:** Load `context-awareness` for project orientation, conventions, and architecture
> context.

## When to Activate

Use this skill when performing a systematic audit of the codebase. Audits focus on pattern
violations, code smells, security holes, and convention drift — NOT feature enhancements. Activates
during ANALYSIS phase or as a periodic quality gate.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Audit Protocol

Audit these 5 scopes in order:

1. **Code** — Audit by 4 architectural layers (UI → Business → Data → Infra)
2. **Testing** — Coverage, structure, mocking conventions
3. **Security** — XSS, SQLi, mass assignment, auth, PII (cross-cutting)
4. **Documentation** — Sync docs against actual code
5. **Dependencies** — Versi package, known vulnerabilities

Record each finding as a GitHub Issue with severity, location, and fix recommendation.

- Output: GitHub Issues documenting audit findings with severity, location, and fix recommendations

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of findings by layer and severity
    - Files audited
    - Number of issues created (critical/high/medium/low)
- Feeds into: roadmap-planning (prioritize fixes), code-refactoring (fix issues), security-audit
  (deep security pass)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                                                                         |
| -------------- | ------------------------------------------------------------------------------------------------------------- |
| **Upstream**   | `context-awareness` (conventions, architecture rules)                                                         |
| **This skill** | **ANALYSIS** — finds issues, records them                                                                     |
| **Downstream** | `roadmap-planning` (prioritize fixes), `code-refactoring` (fix issues), `security-audit` (deep security pass) |

## Audit Scopes

Audit 5 scopes in order. Each scope has specific check items.

### 1. Code — 4 Architectural Layers

#### Layer 4 — Presentation/UI (`app/*/Livewire/`, `resources/views/`, `app/*/Policies/`, `routes/`)

- No `Model::create/update/delete/save` in Livewire components
- No `DB::transaction()` or `DB::beginTransaction()` in Livewire
- No `app()->make()`, `resolve()`, or `new Action()` — method injection only
- `RejectedException` caught from Action calls (before generic `Throwable`)
- No unescaped `{!! !!}` for user content without inline justification
- Policy methods return boolean — no inline authorization in Livewire
- Routes in correct `routes/web/{module}.php` file
- No maryUI Toast methods (`$this->success()`, `$this->error()`) — use flasher
- No N+1 queries in Blade loops — eager loading verified

#### Layer 3 — Business/Domain Ops (`app/*/Actions/`, `app/*/Events/`, `app/*/Listeners/`)

- Action extends correct base class (Command/Read/Process)
- Exactly one public `execute()` method
- Command/Process uses `$this->transaction()` for DB writes
- `$this->log()` called after mutation
- `$this->dispatchEvent()` only if listener exists (check `config/event.php`)
- Business rules delegate to Entity — throw `RejectedException`, not `RuntimeException`
- DTO used for 3+ params; raw `array` not accepted in execute()
- ActionResponse returned for structured feedback

#### Layer 2 — Data/Persistent (`app/*/Models/`, `app/*/Entities/`, `app/*/Enums/`, `database/migrations/`)

- Entities: `final readonly`, `fromModel()`, zero I/O, no Action/Service/Controller imports
- DTOs: `final readonly`, scalars/enums/Carbon only, no Model/Entity/Action imports
- Models: `#[Fillable]` attribute (not `$fillable`/`$guarded`)
- Foreign keys: `foreignUuid()->constrained()` + explicit `onDelete()`/`onUpdate()`
- Enums: `implements LabelEnum` (all), `StatusEnum` for state machines
- Cache keys: registered in `config/cache-keys.php` — no inline strings
- No raw SQL without parameterized binding

#### Layer 1 — Framework/Infrastructure (`app/Core/`, `app/*/Services/`, `app/*/Support/`, `config/`)

- Services: infrastructure logic only (not domain business rules)
- Support: static-only, zero side effects
- Config files follow documented schema
- Module discovery config includes all business modules

### 2. Testing (`tests/`)

- Every Action has a matching test file
- Every Livewire component has a matching test file
- Feature tests use `LazilyRefreshDatabase` (not `RefreshDatabase`)
- `assertModelExists()` preferred over `assertDatabaseHas()`
- No Eloquent mocking — use factories + real database
- `Event::fake()` positioned AFTER factory setup
- Coverage targets met: Entity/Enum/DTO 100%, Actions ≥ 90%, Livewire ≥ 80%

### 3. Security (Cross-Cutting)

- XSS: All Blade output uses `{{ }}`; every `{!! !!}` has inline justification
- SQL injection: No raw SQL without parameterized binding
- Mass assignment: `#[Fillable]` on every model; no `$request->all()` passed to create/update
- Authorization: Every mutation method has `$this->authorize()` or Policy check
- Rate limiting: Active on login, password reset, recovery flows
- File uploads: Via Spatie MediaLibrary only (never `Storage::put()`)
- PII: Isolated in separate tables; masked in logs via `SmartLogger::withPiiMasking()`
- CSP: Enforced by `SecurityHeaders` middleware; no bypass without justification
- CSRF: All state-changing forms include `@csrf` or use Livewire

### 4. Documentation (`docs/`, `README.md`, `AGENTS.md`)

- File paths in docs point to existing files
- Class names and method signatures match actual code
- Action listings include all `execute()` methods
- Enum values include all cases
- No broken relative links
- Metadata (`Last updated`, `Changes`) present on every `.md` file
- Module structure docs match actual `app/` directory layout

### 5. Dependencies (`composer.json`, `package.json`)

- Package versions current (not EOL or deprecated)
- Known vulnerabilities: check `composer audit` output
- No pinned dev-only packages in `require` section (belongs in `require-dev`)
- Alat bantu: `composer audit`, `npm audit`, `composer outdated`

## Issue Format

Each finding recorded as a GitHub Issue should include:

- **Title:** `{scope}: {short description}` (e.g.,
  `code: Livewire:: Model::create() in RegistrationCenter`,
  `testing: missing test for CreateInternshipAction`)
- **Location:** File path and line number
- **Scope:** Code / Testing / Security / Documentation / Dependencies
- **Violation:** Which rule/pattern is violated (reference doc and section)
- **Severity:** Critical / Major / Minor
- **Fix:** Brief recommendation of the correct approach

## Key Rules

1. Audit every module, not just the one being changed
2. Record issues even if fixing them is not in scope — prioritization happens downstream
3. Do NOT fix issues during audit — that is the refactoring phase
4. Verify findings against actual code — docs and skills may be stale

## Automation Scripts

Pre-built scripts for efficient auditing. Run from project root.

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_architecture.py` | Component counts per module, submodule structure | `python3 scripts/scan_architecture.py` |
| `scan_conventions.py` | strict_types, Fillable, debug calls, hardcoded strings | `python3 scripts/scan_conventions.py` |
| `scan_dead_code.py` | Unregistered observers, unused DTOs, orphan events | `python3 scripts/scan_dead_code.py` |
| `scan_issues.py` | Fetch GitHub issues, summarize by module/severity | `python3 scripts/scan_issues.py` |

All scripts output to `scripts/outputs/{timestamp}-{description}.json`. Use `--module {Name}` to scope
to a single module. See `scripts/README.md` for full documentation.

## Verification Checklist

- [ ] **Code** — All 4 layers audited: UI, Business, Data, Infra
- [ ] **Testing** — Coverage, structure, mocking conventions checked
- [ ] **Security** — XSS, SQLi, mass assignment, auth, PII, CSP, CSRF checked
- [ ] **Documentation** — Doc-to-code sync verified
- [ ] **Dependencies** — Versions and known vulnerabilities checked
- [ ] All findings recorded as GitHub Issues with scope, severity, and fix recommendation
- [ ] No fixes applied during audit (scope discipline)
- [ ] Existing issues checked for duplicates before filing

## References

| Topic                      | Doc                                      |
| -------------------------- | ---------------------------------------- |
| Architecture & layer rules | `docs/architecture.md`                   |
| Coding conventions         | `docs/conventions.md`                    |
| Action Triad patterns      | `docs/architecture/action-pattern.md`    |
| Entity-Model separation    | `docs/architecture/entity-pattern.md`    |
| Model conventions          | `docs/architecture/model-pattern.md`     |
| Livewire component rules   | `docs/architecture/livewire-pattern.md`  |
| Exception hierarchy        | `docs/architecture/exception-pattern.md` |
| Caching conventions        | `docs/architecture/cache-pattern.md`     |
| Testing patterns           | `docs/architecture/testing-pattern.md`   |
| Security conventions       | `docs/conventions.md` (§3)               |
| RBAC & authorization       | `docs/foundation/rbac.md`                |
| Documentation conventions  | `docs/conventions.md` (§0)               |
| Docs metadata rules        | `AGENTS.md` (§Quick Reference)           |
| Critical invariants        | `AGENTS.md` (§Critical Invariants)       |
