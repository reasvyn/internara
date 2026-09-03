# Core Module Spec Audit — Session 2 (2026-09-03)

## Description

Session 2 audits **7 P1 Foundation core specs** with **Areas 1-6** (Path, Contract, Requirements,
Test, Cross-Ref, Spec Completeness). Work-scope channel verdict per spec.

**Scope:** D2FT3, FB792, ZT6VS, SE5Q9, C8F0D, J68GZ, I1BCV.

---

## Per-Spec Sync Status

Legend: ✅ synced · ⚠️ drift (auto-fixed or spec-lagging) · ❌ drift (issue to file) · — n/a

| Spec | Area 1 paths | Area 2 contracts | Area 3 reqs | Area 4 tests | Area 5 x-refs | Area 6 compl | Verdict |
|------|:------------:|:----------------:|:------------:|:------------:|:--------------:|:------------:|:-------:|
| D2FT3 | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ | ⚠️ |
| FB792 | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| ZT6VS | ✅ | ✅ | ⚠️ | — | ✅ | ✅ | ⚠️ |
| SE5Q9 | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ | ⚠️ |
| C8F0D | ⚠️ | ⚠️ | ⚠️ | — | ✅ | ✅ | ⚠️ |
| J68GZ | ✅ | ✅ | ⚠️ | — | ✅ | ✅ | ⚠️ |
| I1BCV | ⚠️ | ⚠️ | ⚠️ | ✅ | ✅ | ✅ | ⚠️ |

---

## Findings Detail (typed by `audit-areas.md`)

### D2FT3 — Architecture

- **R-3 (partial):** §4.2 / FR-ARC7 layer directory map does not list `Channels/`, `Console/`, `Concerns/`,
  or `Domains/`. Reality has them all (verified). Code-Forward, code more accurate than spec.
  *Severity: low · Action: spec-lagging catch-up.*

### FB792 — Tech Stack

- **All checks pass.** Versions match (PHP 8.4.22, Laravel 13.29, Livewire 4.4, TallStackUI 4.1).
- **FR-TS5/TS6a verified:** `grep -r "x-mary\|flash()->" app/ resources/` = 0.
- **FR-VER1 verified:** `composer audit` exit 0, `npm audit` exit 0.
- *Verdict: fully synced.*

### ZT6VS — Core & Infrastructure Services

- **R-3 (partial) — `SESSION_DRIVER=file` in `.env`** but spec FR-SESS1 says default is `database`.
  `composer show spatie/laravel-model-status` (related) confirms package present but unused.
  *Severity: low · Action: file Issue* (track decision or fix env default).
- All other ZT6VS FRs verified (`busy_timeout=5000`, `journal_mode=wal`, `foreign_key_constraints`,
  `documents` queue pipeline, cache-keys registry 27 keys ≥ 25 required).
- *Verdict: synced with one minor drift.*

### SE5Q9 — Base Classes

- **P/C-4 (contract):** `ActionFailedException` exists in code (`app/Modules/Core/Exceptions/ActionFailedException.php`)
  but is **not listed in spec FR-E1..E7**. Per git log `faf24a61f fix(core): create ActionFailedException and update tests`.
  *Severity: low · Action: spec-lagging catch-up — add to FR-E list.*
- **R-3 (partial) — `ActivityLog` scopes:** spec lists 9 scopes (`forUser`, `whereSubject`, `ofAction`,
  `inLog`, `recent`, `lastDays`, `forModule`, `groupedByDay`, `getSubjectModelAttribute`). Reality has
  8 scopes + `getSubjectModelAttribute` (counts as attribute, not scope). All 9 contract elements
  present — **fully synced**, just imprecise spec wording.
- All other SE5Q9 contracts verified: `BaseAction/BaseCommandAction/BaseReadAction/BaseProcessAction`,
  `BaseModel/BaseAuthenticatable/ActivityLog`, `BaseEntity`, `BaseData/ActionResponse/AuditCheck/AuditReport`,
  `AppException/ModuleException/RejectedException/ValidationFailedException/UnauthorizedException/
  ActionException/InfrastructureException/PresentationException/ActionFailedException`, `LabelEnum/
  StatusEnum/ColorableEnum/SendsNotifications/SettingsStore`, `HasExceptionContext/HandlesActionErrors/
  ResolvesModuleName`, all 5 Livewire base classes, all 2 Livewire concerns.
- `BaseReadAction` correctly does NOT extend `BaseAction` (per spec §6.1).
- *Verdict: nearly fully synced, one spec-lagging item.*

### C8F0D — Shared Utilities

- **P-1 (path) — `Environment` class location:** spec §6.3 says `app/Modules/Core/Services/Environment.php`
  but reality is `app/Modules/Core/Support/Environment.php`. Migration from Services to Support likely
  tied to the B114U "static = Support" rule.
  *Severity: low · Action: spec-lagging catch-up.*
- **R-3 (partial) — `Environment::isProduction()` missing:** spec FR-SUP5 lists `isProduction()` but
  code has `isDevelopment()` (different semantics). Code renamed `isProduction` → `isDevelopment`.
  *Severity: low · Action: spec-lagging catch-up — update FR-SUP5 list.*
- All other C8F0D utilities verified: `AppInfo` (6+ methods, 24h cache), `AppIntegrity::verify()`,
  `PasswordRules::default()`, `Color` (hex/RGB/luminance/contrast/lighten/darken/shades),
  `LangChecker extends Translator` with caller file:line logging.
- *Verdict: synced with two minor spec-lagging items.*

### J68GZ — System Requirements

- **R-3 (partial) — `spatie/laravel-model-status` listed in FR-D6** but code has 0 imports. Deprecated
  via #419; `config/model-status.php` is the only artifact.
  *Severity: medium · Action: file Issue* (remove from FR-D6 + remove config file; per #419 plan).
- All extensions verified (11 required + 3 recommended all present).
- `busy_timeout=5000` ✓, `journal_mode=wal` ✓, `foreign_key_constraints=true` ✓.
- *Verdict: synced with one spec-lagging item (the deprecated dependency).*

### I1BCV — Module Discovery

- **R-2 (Code→Spec) — config auto-discovery:** spec FR-MR1..FR-MR8 say `config/module.php` must define
  a manual `$modules` array with submodules, exports `list`/`registry`/`test_dirs`/`paths`. Reality:
  `config/module.php` now **scans the filesystem** to build the list dynamically. Spec lists steps
  like "Add module key to `$modules` array" (UC-1, FR-MR1, FR-MR2) — no longer accurate.
  *Severity: medium · Action: spec amendment — file Issue* (decision needed: spec lag, or
  document the new auto-discovery model).
- **R-2 — `tests/Pest.php` also auto-discovers** (filesystem scan), spec FR-T1..FR-T4 required manual
  sync with `config/module.php`. Spec is now stale on this point.
- **B114U supersedes I1BCV partially:** `ModuleManager`/`ModuleService` replace `ModuleDiscoverService`
  (B114U §6.2). `ModuleDiscoverService` no longer exists in code (verified: `find` returns nothing).
  ✓ FR-MIG1 satisfied.
- `config/event.php` exists, 154 lines, event-to-listener registry ✓ (cross-spec NUCY3 check).
- *Verdict: significant spec rewrite needed (auto-discovery model not documented in spec).*

---

## Cross-Spec Findings

| # | Finding | Spec(s) | Severity | Action |
|---|---------|---------|----------|--------|
| **X-1** | `spatie/laravel-model-status` (`^1.18`) is in `composer.json` but 0 imports in `app/` | J68GZ FR-D6, FB792 §6.1 | medium | File Issue — remove from J68GZ FR-D6 + delete `config/model-status.php` (per #419) |
| **X-2** | `ActionFailedException` exists in code, not in spec FR list | SE5Q9 §4.5 | low | Spec-lagging catch-up (add FR-E8) |
| **X-3** | `Environment` class moved Services→Support | C8F0D §6.3 | low | Spec-lagging catch-up (update path) |
| **X-4** | `Environment::isProduction()` renamed to `isDevelopment()` | C8F0D FR-SUP5 | low | Spec-lagging catch-up (update method list) |
| **X-5** | `.env` has `SESSION_DRIVER=file` but spec FR-SESS1 default is `database` | ZT6VS FR-SESS1 | low | File Issue (deliberate env override vs spec) |
| **X-6** | `config/module.php` + `tests/Pest.php` now filesystem-auto-discover; spec still mandates manual sync | I1BCV FR-MR1..MR8, FR-T1..T4 | medium | File Issue — spec amendment (auto-discovery model not documented) |
| **X-7** | Channels/, Console/, Concerns/, Domains/ layer dirs not in spec FR-ARC7 layer map | D2FT3 FR-ARC7 | low | Spec-lagging catch-up (extend layer map) |

---

## Decision Matrix Application

Per `decision-matrix.md` + `fix-or-issue.md`:

| # | Decision | Justification |
|---|----------|---------------|
| X-1 | **Comment on existing issue [#419](https://github.com/reasvyn/internara/issues/419)** (no duplicate) | Pre-existing issue covers dep removal; added audit evidence + spec-lagging scope (J68GZ FR-D6 + FB792 §6.1) |
| X-2 | **Auto-fix spec** (add FR-E8) | Spec-lagging catch-up — pure documentation |
| X-3 | **Auto-fix spec** (update path) | Spec-lagging catch-up — pure documentation |
| X-4 | **Auto-fix spec** (update method list) | Spec-lagging catch-up — pure documentation |
| X-5 | **File Issue [#433](https://github.com/reasvyn/internara/issues/433)** | Deliberate override vs spec drift; needs maintainer design decision |
| X-6 | **File Issue [#434](https://github.com/reasvyn/internara/issues/434)** | Multi-file spec rewrite (I1BCV + B114U + AGENTS.md) exceeds 1-file gate |
| X-7 | **Auto-fix spec** (extend layer map) | Spec-lagging catch-up — pure documentation |

**Auto-fixed this session:** X-2, X-3, X-4, X-7 (spec doc updates only, no code change).
**Issues filed this session (per-scope cadence):** [#433](https://github.com/reasvyn/internara/issues/433) (X-5), [#434](https://github.com/reasvyn/internara/issues/434) (X-6), comment on [#419](https://github.com/reasvyn/internara/issues/419) (X-1).

---

## Test Gap Status (preliminary, deepened in Session 4)

| Spec | Test files | Sample run | Gap? |
|------|------------|------------|------|
| D2FT3 | (architectural; tested via other specs) | — | No (indirect) |
| FB792 | (manifest; tested via composer install) | ✅ | No |
| ZT6VS | (config files; tested via integration) | — | Yes (env override tests) |
| SE5Q9 | `tests/Core/{Actions,Data,Events,Enums,Exceptions,Livewire,Models,Policies,Http,Services,Support}/` 43 files | 6/6 sample pass | No (extensive) |
| C8F0D | partial — `AppInfo`/`PasswordRules` likely tested, `Environment`/`AppIntegrity` not seen | — | Yes (extend) |
| J68GZ | (system health; tested via Setup/Installation) | — | Yes (FR-SY8) |
| I1BCV | `tests/Core/Actions/` etc. — likely covers ModuleManager/ModuleService | — | Likely (Session 4) |

**Test-Gap Fill scope:** deferred to Session 4 (full test run + targeted fills).

---

## Session 2 Verdict

**Decision: ACCEPT — proceed to Session 3.**

- 7 specs audited across Areas 1-6. **Zero C1-C8 / D1-D6 violations.**
- 7 cross-spec findings; 4 are spec-lagging catch-up (auto-fix this session), 3 need Issues
  (X-1, X-5, X-6 — deferred to Session 6).
- No code fixes required this session; only doc updates.
- Test gaps identified; full fill deferred to Session 4.

---

## Where to Find It

- `.agents/plans/audits/core-2026-09/session-2-p1-core-specs.md` (this file)
- `tools/outputs/20260903*-core-*.json` — baseline scanner outputs (Session 1)
- Auto-fixed spec changes (X-2, X-3, X-4, X-7) — applied in this session
- Session 3: cross-cutting specs (89SRA, NUCY3, T4B26, 2CF4Y, 1PGM4, B114U) + QLHDO
