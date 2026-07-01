# Roadmap — Project Health & Closure

> **Last updated:** 2026-07-01
> **Changes:** add current state audit; all 8 issues closed; 10 Dependabot PRs pending

## Description
> **Target:** Project health verification — all issues closed, dependencies up to date, regression check

**Status:** ✅ All 8 open issues resolved — 10 Dependabot dependency PRs pending

---

## 1. Current State

| Metric | Value |
|--------|-------|
| Open issues | **0** |
| Open pull requests | **10** (all Dependabot automated dependency bumps) |
| Passing tests | **2,400** |
| Failing tests | **1** (intermittent — passes in isolation, fails due to test ordering in full suite) |
| Test suite duration | **319s** (down from 767s) |
| Unmerged Dependabot PRs | **10** |

The project is in a healthy state. All manual work items from the previous roadmap phases
are complete. The only remaining items are automated dependency updates and one intermittent
test failure.

---

## 2. Remaining Work

### 2.1 Dependabot Pull Requests (10 PRs — Low Priority)

All 10 open PRs are automated dependency bumps created by Dependabot. Safe to merge after CI passes.

| # | Dependency | From → To |
|---|-----------|-----------|
| [#204](https://github.com/reasvyn/internara/pull/204) | `laravel/boost` | 2.4.8 → 2.4.11 |
| [#200](https://github.com/reasvyn/internara/pull/200) | `vite` | 8.0.16 → 8.1.0 |
| [#199](https://github.com/reasvyn/internara/pull/199) | `laravel/sail` | 1.61.0 → 1.63.0 |
| [#198](https://github.com/reasvyn/internara/pull/198) | `@rollup/rollup-linux-x64-gnu` | 4.61.0 → 4.62.2 |
| [#197](https://github.com/reasvyn/internara/pull/197) | `prettier-plugin-blade` | 3.1.6 → 3.2.0 |
| [#189](https://github.com/reasvyn/internara/pull/189) | `tailwindcss` | 4.3.0 → 4.3.1 |
| [#188](https://github.com/reasvyn/internara/pull/188) | `@tailwindcss/vite` | 4.3.0 → 4.3.1 |
| [#187](https://github.com/reasvyn/internara/pull/187) | `@tailwindcss/oxide-linux-x64-gnu` | 4.3.0 → 4.3.1 |
| [#186](https://github.com/reasvyn/internara/pull/186) | `daisyui` | 5.5.20 → 5.5.23 |
| [#184](https://github.com/reasvyn/internara/pull/184) | `marked` | 18.0.4 → 18.0.5 |

### 2.2 Intermittent Test Failure (1 — Low Priority)

One test fails only when the full suite runs but passes in isolation. Likely a
shared state/cache leakage between tests. Not critical — does not block CI.

---

## 3. Discovered Issues (During Implementation)

These issues were found while working on the roadmap but were not tracked as GitHub issues.
They represent code quality concerns, potential bugs, and architectural debt.

| # | Issue | Severity | File/Location | Discovery Context |
|---|-------|----------|---------------|-------------------|
| D1 | `MentorEntity::isMentor()` uses `Collection::contains(string)` instead of closure — never matches User model objects | **HIGH** | `app/User/Mentor/Entities/MentorEntity.php:47` | Cross-Role Proxy tests |
| D2 | `internship_group_members` pivot table missing `role` column — CertificateRenderer and queries fail | **HIGH** | `database/migrations/2026_01_05_000001_create_internship_groups_table.php` | CertificateRendererTest |
| D3 | `users` table had `last_activity` (integer) instead of `last_activity_at` (timestamp) — command queries compare Carbon dates | **HIGH** | `database/migrations/2026_01_02_000001_create_users_table.php` | AutoInactivateAccountsCommandTest |
| D4 | `UserIdentifierGenerator::generateUsername()` queries `users` table during factory creation — incompatible with `LazilyRefreshDatabase` | **MEDIUM** | `app/User/Services/UserIdentifierGenerator.php:44` | LazilyRefreshDatabase migration (#203) |
| D5 | `incident_reports` migration defines `severity` index twice — causes test DB schema errors | **MEDIUM** | `database/migrations/2026_01_04_000015_create_incident_reports_table.php` | Full test suite run |
| D6 | `SmartLogger` in `Support/` uses instance methods + DI — violates static-only Support convention but too many imports (100+) to move | **MEDIUM** | `app/Core/Support/SmartLogger.php` | Layer 1 audit |
| D7 | `Report::captureSnapshot()` called during `saving` event — relationships not yet available, snapshot silently skips | **MEDIUM** | `app/Reports/Report/Observers/ReportObserver.php` | ReportModelTest |
| D8 | `BaseEvent::toPayload()` converts Model properties to `{$model}_id` keys — may surprise implementers expecting original key names | **LOW** | `app/Core/Events/BaseEvent.php:63` | Event test writing |
| D9 | `ReportTest` must keep `RefreshDatabase` instead of `LazilyRefreshDatabase` due to D4 | **LOW** | `tests/Unit/Reports/Report/Models/ReportTest.php` | LazilyRefreshDatabase migration (#203) |

### Fix Status

| # | Status | Fix |
|---|--------|-----|
| D1 | ✅ Fixed | Changed `contains($id)` to `contains(fn ($m) => $m->id === $id)` |
| D2 | ✅ Fixed | Added `$table->string('role')->nullable()` to migration |
| D3 | ✅ Fixed | Changed column to `timestamp('last_activity_at')->nullable()` |
| D5 | ✅ Fixed | Removed duplicate `$table->index('severity')` |
| D4 | ⏳ Open | No fix yet — workaround: keep `RefreshDatabase` for affected tests |
| D6 | ⏳ Open | Architectural debt — move to `Services/` when breaking changes are acceptable |
| D7 | ⏳ Open | Consider using `saved` event instead of `saving`, or lazy-load inside handler |
| D8 | ⏳ Open | Document behavior in `BaseEvent` docblock — not a runtime bug |
| D9 | ⏳ Open | Already documented in Decision 4 (exclusion criteria) |

---

## 4. Deferred Work

| Area | Scope | Reason Deferred |
|------|-------|----------------|
| Event dispatch for remaining ~80 Actions | SHOULD-level | High-priority already dispatch events |
| Livewire tests for 63 components | Coverage gap | 9 event tests written as template |
| Event tests for ~25 remaining events | Coverage gap | 9 event tests written |
| SmartLogger → Services/ move | Architecture debt | 100+ imports, needs careful migration |
| UserIdentifierGenerator DB dependency | Architecture debt | Would require injecting DB-free username generator |

## 5. Next Steps

1. **Merge 10 Dependabot PRs** — all safe semver bumps, merge after CI passes
2. **Fix intermittent test failure** — investigate shared state leakage between tests
3. **Add Livewire tests** — 63 components pending, see pest-testing reference for template
4. **Add event tests** — 25 events still untested
5. **Add event dispatch to remaining Actions** — ~80 Actions, SHOULD-level
