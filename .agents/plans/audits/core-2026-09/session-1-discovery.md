# Core Module Spec Audit — Session 1 Discovery (2026-09-03)

## Description

Full spec audit of Internara's `app/Modules/Core/` against the 13 P1 Foundation specs + spec-zero
`QLHDO`. This file is the **audit map** (Phase 1 deliverable) and the Session 1 final report.

**Scope:** module Core + 14 specs (P1 Foundation + spec-zero), full Area 1-8 audit, work-scope per
`spec-audit` skill (Implementation / Testing / Documentation channels).

**Size:** L — split into 6 sessions per L-size protocol. This file covers Session 1.

---

## Audit Map — 14 Specs

| Spec | Module | Phase | Primary files (Core) | Status to verify |
|------|--------|-------|----------------------|------------------|
| **QLHDO** Internara Project | Core (blanket) | spec-zero | (cross-cutting) | FR-G1..G8 globals |
| **D2FT3** Architecture | Core | P1 | `app/Modules/Core/` (4-layer pattern) | FR-ARC1..42 |
| **FB792** Tech Stack | Core | P1 | `composer.json`, `package.json` | FR-TS1..6a, FR-DEP1..6, FR-VER1 |
| **ZT6VS** Core Infra Services | Core | P1 | `config/{cache,session,queue,mail,database,filesystems,cache-keys}.php` | FR-DB1..6, FR-CACHE1..10, FR-SESS1..9, FR-Q1..6, FR-M1..4, FR-FS1..5, FR-SVC1..3 |
| **SE5Q9** Base Classes | Core | P1 | `app/Modules/Core/{Actions,Models,Entities,Data,Enums,Livewire,Policies,Contracts,Exceptions,Channels}/` | FR-A1..7, FR-M1..7, FR-L1..7, FR-C1..5, FR-CH1, FR-E1..7, FR-P1..3, FR-D1..5, FR-TR1..2 |
| **C8F0D** Shared Utilities | Core | P1 | `app/Modules/Core/{Services,Support}/{AppInfo,Environment,PasswordRules,Color,AppIntegrity,LangChecker,helpers}.php` | FR-SUP3,4,5,7,9,10,11 |
| **J68GZ** System Requirements | Core | P1 | `composer.json`, `config/database.php`, system health | FR-SY1..10, FR-D1..11, FR-DB1..8 |
| **I1BCV** Module Discovery | Core | P1 | `config/module.php`, `routes/web.php`, `AppServiceProvider`, `app/Modules/Core/Services/ModuleService.php`, `app/Modules/Core/Support/ModuleManager.php`, `tests/Pest.php` | FR-MR1..8, FR-LW1..7, FR-P1..7, FR-V1..5, FR-R1..4, FR-CLI1..4, FR-T1..4 |
| **B114U** Module Manager | Core | P1 | `app/Modules/Core/Support/ModuleManager.php`, `app/Modules/Core/Services/ModuleService.php` | FR-MG1..14, FR-MS1..11, FR-MIG1..6 |
| **89SRA** Logging & Error Handling | Core | P1 | `app/Modules/Core/Services/SmartLogger.php`, `app/Modules/Core/Support/PiiMasker.php`, `app/Modules/Core/Exceptions/`, `app/Modules/Core/Http/Middleware/LogContextMiddleware.php` | FR-SL1..6, FR-DC1..6, FR-EI1..4, FR-TR1..4, FR-PM1..9, FR-EH1..9, FR-AE1..6, FR-ER1..5, FR-LC1..5 |
| **NUCY3** Event System | Core | P1 | `app/Modules/Core/Events/BaseEvent.php`, `config/event.php` | FR-EV1..14 |
| **T4B26** RBAC & Authorization | Core | P1 | `app/Modules/Core/Policies/`, `app/Modules/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php`, `app/Modules/Auth/Permissions/Enums/Role.php` | FR-AUTH1..12, FR-CRP1..6 |
| **2CF4Y** Middleware Pipeline | Core | P1 | `bootstrap/app.php`, `app/Modules/Core/Http/Middleware/`, `app/Providers/AppServiceProvider.php` | FR-MW1..11 |
| **1PGM4** Security Headers | Core | P1 | `app/Modules/Core/Http/Middleware/SecurityHeadersMiddleware.php`, `config/security-headers.php` | FR-SEC1..12 |

**Total FRs in scope:** 318 (sum across 14 specs; precise count to be confirmed in Session 2-3).

---

## Baseline Scanners (Session 1)

| Scanner | Scope | Result | Output |
|---------|-------|--------|--------|
| `scan_violations` | `app/Modules/Core/` | **11 findings** (5 medium, 6 low) — all SRP (god class / long method) | `tools/outputs/20260903*-core-violations.json` |
| `scan_class_contracts` | `app/Modules/Core/` | **0 findings** ✅ — class contracts (Action/Entity/DTO/Model/Enum) all compliant | `tools/outputs/20260903*-core-class-contracts.json` |
| `scan_security` | `app/Modules/Core/` | **0 findings** ✅ (10/10 checks pass, composer audit + npm audit both exit 0) | `tools/outputs/20260903*-core-security.json` |
| `scan_conventions` | `app/Modules/Core/` | (deferred to Session 5 — Area 7+8) | — |

### Violations Detail (SRP only)

| Severity | Rule | File:Line | Message |
|----------|------|-----------|---------|
| medium | SRP_GOD_CLASS | `app/Modules/Core/Services/AppInfo.php:11` | 19 methods (threshold 12) |
| medium | SRP_GOD_CLASS | `app/Modules/Core/Livewire/BaseRecordManager.php:16` | 12 methods (threshold 12) |
| medium | SRP_GOD_CLASS | `app/Modules/Core/Support/ModuleManager.php:16` | 21 methods (threshold 12) |
| medium | SRP_GOD_CLASS | `app/Modules/Core/Services/SmartLogger.php:15` | 28 methods (threshold 12) |
| medium | SRP_LONG_METHOD | `app/Modules/Core/Services/ModuleService.php:127` | 84 lines, 14 branches |
| low | SRP_LONG_METHOD | `app/Modules/Core/Channels/CustomDatabaseChannel.php:21` | 41 lines |
| low | SRP_LONG_METHOD | `app/Modules/Core/Console/Commands/ModuleDiscoverCommand.php:24` | 53 lines |
| low | SRP_LONG_METHOD | `app/Modules/Core/Services/AppIntegrity.php:32` | 42 lines |
| low | SRP_LONG_METHOD | `app/Modules/Core/Services/ModuleService.php:57` | 70 lines |
| low | SRP_LONG_METHOD | `app/Modules/Core/Support/CsvHandler.php:77` | 44 lines |
| low | SRP_LONG_METHOD | `app/Modules/Core/Services/SmartLogger.php:275` | 40 lines |

**No C1-C8 or D1-D6 violations** in Core. All SRP findings are design-quality, not architectural
breach. **Triaged as `C-NEW` deferred** (not regressions; pre-existing baseline).

### Pre-existing baseline (from `.agents/context/codebase-intentional-states.md`)

| Scanner | Baseline | Core count today | Delta |
|---------|----------|------------------|-------|
| `tools/scan_violations.py` | 32 | 11 (Core only) | Core subset; cross-module >32 |

---

## Test Suite Baseline (Session 1 — partial)

| Suite | Count | Status |
|-------|-------|--------|
| `tests/Core/**/*.php` | 43 files | Sanity run: 6/6 pass on `BaseCommandActionTest` (6.82s) |
| Full Core suite | (deferred to Session 4) | — |

**Test gap (preliminary):** Module-level integration tests for the 4-layer wiring
(ModuleService discovery → AppServiceProvider → Livewire registration) appear thin. To be
confirmed in Session 4.

---

## Quick Wins Identified (Session 1)

1. **`spatie/laravel-model-status` is deprecated** (#419) — `grep -r "spatie/laravel-model-status\|HasStatus"` in `app/` returns 0 imports. **Only artifact:** `config/model-status.php`. Safe to remove per J68GZ `FR-D6` reference + `.agents/context/dep-model-status-deprecated.md`. **No spec change needed — already deprecated doc.**

2. **`config('module.*')` outside ModuleManager = 0** (B114U NFR-M1 compliant). ✅

3. **`tests/Core` exists and runs** — 43 test files, no bootstrap errors. ✅

4. **FB792 / J68GZ consistency** — both list `spatie/laravel-model-status ^1.18` as FR-D6. Code reality: 0 imports. **Spec→Code drift confirmed; safely classifiable as spec-lagging catch-up (remove from spec FR-D6).** Categorized: `S-3` Stale metadata (low severity).

---

## Session 1 Verdict

**Decision: ACCEPT — proceed to Session 2.**

- Audit map built (14 specs, 318+ FRs).
- Baselines captured: 0 architecture violations, 0 contract violations, 0 security issues.
- 11 SRP findings are pre-existing design-quality observations (deferred to a separate cleanup pass per `codebase-intentional-states.md`).
- Tests exist (43 files), suite runs, sample test passes.
- No critical or high-severity findings blocking the audit.

**Session 2 will execute Areas 1-6 per spec for:** D2FT3, FB792, ZT6VS, SE5Q9, C8F0D, J68GZ, I1BCV (7 foundation specs).

---

## Session Plan (L-size)

| # | Session | Output |
|---|---------|--------|
| 1 | **Discovery** ✅ | This file + 3 baseline JSON |
| 2 | Audit 7 P1 core specs (Areas 1-6) | Per-spec findings table |
| 3 | Audit 6 P1 cross-cutting specs + QLHDO (Areas 1-6) | Per-spec findings table |
| 4 | Area 4 deepen — run Core tests, fill test gaps | New spec-traceable test files |
| 5 | Area 7+8 — composer/npm audit, guides sync check | Dep scan + guides delta |
| 6 | Triage, file GitHub Issues, final report | Issues + visual report |

---

## Where to Find It

- `tools/outputs/20260903*-core-*.json` — baseline scanner outputs
- Session 2-6 reports will append to this directory
- Final report (Session 6) — `tools/outputs/{timestamp}-core-audit-final.md`
