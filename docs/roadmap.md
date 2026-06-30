# Roadmap — Open Issue Resolution

> **Last updated:** 2026-06-28
> **Changes:** replace Cross-Role Proxy roadmap with comprehensive open-issue resolution plan covering all 8 GitHub issues

## Description
> **Target:** Resolve all open GitHub issues across architecture, code quality, testing, and conventions
> **Total open issues:** 8 (2 HIGH, 5 MEDIUM, 1 enhancement)

---

## 1. Open Issues Overview

| # | Title | Priority | Type | Created |
|---|-------|----------|------|---------|
| [#192](https://github.com/reasvyn/internara/issues/192) | 23 events not registered in config/event.php | HIGH | Bug/Tech Debt | 2026-06-23 |
| [#201](https://github.com/reasvyn/internara/issues/201) | Fix remaining 37 test failures across 8 modules | HIGH | Bug | 2026-06-29 |
| [#191](https://github.com/reasvyn/internara/issues/191) | 115 Command Actions missing event dispatch | MEDIUM | Tech Debt | 2026-06-23 |
| [#193](https://github.com/reasvyn/internara/issues/193) | 25 Command Actions missing `->log()` | MEDIUM | Tech Debt | 2026-06-23 |
| [#194](https://github.com/reasvyn/internara/issues/194) | Test coverage gaps (63 Livewire, 34 Events) | MEDIUM | Enhancement | 2026-06-23 |
| [#195](https://github.com/reasvyn/internara/issues/195) | 22 events emitted without registered listeners | MEDIUM | Tech Debt | 2026-06-23 |
| [#202](https://github.com/reasvyn/internara/issues/202) | Missing `#[Fillable]` attributes on models | MEDIUM | Tech Debt | 2026-06-29 |
| [#203](https://github.com/reasvyn/internara/issues/203) | Migrate `RefreshDatabase` to `LazilyRefreshDatabase` | MEDIUM | Tech Debt | 2026-06-29 |

---

## 2. Issue Details & Fix Files

### HIGH Priority

#### [#192](https://github.com/reasvyn/internara/issues/192) — Register orphaned events in config/event.php

23 event classes exist in code but have no entry in `config/event.php`. Without registration, events fired by Actions have no listeners — side effects (cache invalidation, notifications) never execute.

**Scope:** 23 unregistered events across 17 modules
**Fix:** For each orphaned event, either:
- Register it with its intended listener in `config/event.php`, OR
- Remove the event class if it's dead code

**Files to modify:**
- `config/event.php` — add 23 missing event→listener mappings
- `app/*/Events/*.php` — verify each exists and has correct listener(s)
- `app/*/Listeners/*.php` — verify listener classes exist

---

#### [#201](https://github.com/reasvyn/internara/issues/201) — Fix remaining 37 test failures

| Module | Failures | Likely Root Cause |
|--------|----------|-------------------|
| Feature/Document | 2 | Assertion mismatch (route/view expectations) |
| Feature/Reports | 9 | Stale action signatures or assertions |
| Feature/SysAdmin | 9 | Command assertions, Livewire view data |
| Feature/User | 2 | Account status action assertions |
| Feature/Setup | 1 | Middleware route mismatch |
| Feature/Program | 6 | Policy/action behavior changed |
| Unit/Program | 5 | Policy/enum mismatch |
| Unit/Journals | 3 | Entity/enum mismatch |

**Fix approach:** For each failing file, run it, read the error, read the source, update the test to match current code behavior.

---

### MEDIUM Priority

#### [#191](https://github.com/reasvyn/internara/issues/191) — Dispatch events from 115 Command Actions

115 out of 151 Command Actions don't dispatch events. Convention says SHOULD dispatch for significant state changes.

**Scope:** 115 Actions across 17 modules
**Fix:** For each Action, add:
```php
event(new {Entity}{Actioned}({model}));
```

**Automation:** Grep for `BaseCommandAction` in each module, check for `event(new` or `dispatchEvent(`. If missing, add appropriate event.

---

#### [#193](https://github.com/reasvyn/internara/issues/193) — Add `->log()` to 25 Command Actions

25 Command Actions are missing `$this->log()` after successful mutations.

**Scope:** 25 Actions (list documented in issue body)
**Fix:** Add `$this->log('{verb}_{entity}', ${entity})` before returning from `transaction()`.

---

#### [#194](https://github.com/reasvyn/internara/issues/194) — Write tests for uncovered components

**Scope:**
- 63 Livewire components without tests
- 34 Events without tests

**Fix approach:**
- Livewire: Create `tests/Feature/{Module}/{SubModule}/Livewire/{Component}Test.php` with render + interaction tests
- Events: Create `tests/Unit/{Module}/{SubModule}/Events/{Event}Test.php` verifying dispatch and payload

---

#### [#195](https://github.com/reasvyn/internara/issues/195) — Register listeners for 22 events

22 events are dispatched from Actions but have no registered listener. Side effects (cache invalidation, notifications, activity log entries) are lost.

**Fix:** Add listener registrations in `config/event.php`. Common patterns:
- Cache invalidation: `{Entity}{Actioned}` → `Clear{Entity}CacheListener`
- Notifications: `{Entity}{Actioned}` → `Send{Entity}{Type}Notification`

---

#### [#202](https://github.com/reasvyn/internara/issues/202) — Add `#[Fillable]` attributes

**Models to fix:**
- `app/Assessment/Rubric/Models/Rubric.php`
- `app/Assignment/Models/Assignment.php`
- `app/Partners/Partnership/Models/Partnership.php`
- `app/Reports/Report/Models/Report.php`
- `app/SysAdmin/Announcement/Models/Announcement.php`
- `app/User/Models/User.php`

**Fix:** Replace `protected $fillable = [...]` with `#[Fillable]` attribute.

---

#### [#203](https://github.com/reasvyn/internara/issues/203) — Migrate to LazilyRefreshDatabase

Replace `uses(RefreshDatabase::class)` with `uses(LazilyRefreshDatabase::class)` across all test files for faster test execution.

**Scope:** All test files still using `RefreshDatabase`
**Detection:** `grep -rn "RefreshDatabase" tests/ | grep -v "LazilyRefresh"`

---

## 3. Implementation Phases

### Phase 1: Conventions (HIGH — 3 issues)
| Order | Issue | Effort | Impact |
|-------|-------|--------|--------|
| 1 | [#203](https://github.com/reasvyn/internara/issues/203) LazilyRefreshDatabase | Small | Performance |
| 2 | [#202](https://github.com/reasvyn/internara/issues/202) #[Fillable] attributes | Small | Conventions |
| 3 | [#192](https://github.com/reasvyn/internara/issues/192) Register orphaned events | Medium | Correctness |

### Phase 2: Test Fixes (HIGH — 1 issue)
| Order | Issue | Effort | Impact |
|-------|-------|--------|--------|
| 4 | [#201](https://github.com/reasvyn/internara/issues/201) 37 test failures | Large | Test Stability |

### Phase 3: Action Completeness (MEDIUM — 2 issues)
| Order | Issue | Effort | Impact |
|-------|-------|--------|--------|
| 5 | [#193](https://github.com/reasvyn/internara/issues/193) Missing ->log() | Medium | Observability |
| 6 | [#191](https://github.com/reasvyn/internara/issues/191) Missing event dispatch | Large | Side Effects |

### Phase 4: Test Coverage (MEDIUM — 2 issues)
| Order | Issue | Effort | Impact |
|-------|-------|--------|--------|
| 7 | [#195](https://github.com/reasvyn/internara/issues/195) Event listeners | Medium | Correctness |
| 8 | [#194](https://github.com/reasvyn/internara/issues/194) Test coverage gaps | Large | Quality |

---

## 4. Completed Work (Previous Phases)

| Work Item | Phase | Status |
|-----------|-------|--------|
| Architecture: 12-layer → 4-layer simplification | Foundation | ✅ |
| Layer 1 audit & refactor (Support → Services) | Foundation | ✅ |
| Test suite: 97 files fixed, 178→37 failures | Foundation | ✅ |
| Test performance: 661s→482s (-27%) | Foundation | ✅ |
| Slow test audit: no timeout/memory leaks | Foundation | ✅ |
| Cross-Role Proxy: all 5 phases | Proxy | ✅ |

---

## 5. Integration Order

| Phase | Order | Issue | Files | Dependencies |
|-------|-------|-------|-------|-------------|
| **Conventions** | 1 | [#203](https://github.com/reasvyn/internara/issues/203) | All test files | — |
| | 2 | [#202](https://github.com/reasvyn/internara/issues/202) | 6 model files | — |
| | 3 | [#192](https://github.com/reasvyn/internara/issues/192) | `config/event.php`, event/listener files | — |
| **Test Fixes** | 4 | [#201](https://github.com/reasvyn/internara/issues/201) | 30+ failing test files | 1, 2 (reduces noise) |
| **Actions** | 5 | [#193](https://github.com/reasvyn/internara/issues/193) | 25 Command Action files | — |
| | 6 | [#191](https://github.com/reasvyn/internara/issues/191) | 115 Command Action files | 5 (locations overlap) |
| **Coverage** | 7 | [#195](https://github.com/reasvyn/internara/issues/195) | `config/event.php`, listener files | 3, 6 (events need registration) |
| | 8 | [#194](https://github.com/reasvyn/internara/issues/194) | 97 new test files | 4 (stabilize first) |
