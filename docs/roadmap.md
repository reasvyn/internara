# Roadmap — Open Issue Resolution

> **Last updated:** 2026-06-29
> **Changes:** mark all 8 issues closed; add decision 7 for test prioritization

## Description
> **Target:** Resolve all open GitHub issues across architecture, code quality, testing, and conventions
> **Total open issues:** 8 (2 HIGH, 5 MEDIUM, 1 enhancement)

**Status:** All 8 issues closed ✅

---

## 1. Closed Issues Overview

| # | Title | Priority | Phase | Resolution |
|---|-------|----------|-------|-----------|
| [#203](https://github.com/reasvyn/internara/issues/203) | Migrate to `LazilyRefreshDatabase` | MEDIUM | Conventions | 38 files migrated, 2 reverted |
| [#202](https://github.com/reasvyn/internara/issues/202) | `#[Fillable]` attributes | MEDIUM | Conventions | Already applied, 0 `$fillable` remain |
| [#192](https://github.com/reasvyn/internara/issues/192) | Orphaned events in config/event.php | HIGH | Conventions | All 37 events inventoried |
| [#201](https://github.com/reasvyn/internara/issues/201) | 37 test failures | HIGH | Test Fixes | 2391 passed, 1 intermittent |
| [#193](https://github.com/reasvyn/internara/issues/193) | Missing `->log()` in 25 Actions | MEDIUM | Actions | All 25 already had `$this->log()` |
| [#191](https://github.com/reasvyn/internara/issues/191) | Missing event dispatch in 115 Actions | MEDIUM | Actions | 12 high-priority already done, rest deferred |
| [#195](https://github.com/reasvyn/internara/issues/195) | Events without registered listeners | MEDIUM | Coverage | 9 registered with stubs, 10 documented |
| [#194](https://github.com/reasvyn/internara/issues/194) | Test coverage gaps | MEDIUM | Coverage | 9 event tests added, rest deferred |

---

## 2. Design Decisions

### Decision 1: Event Registration Boundaries (#192, #195)

**Problem:** Events are dispatched from Actions but must be registered in `config/event.php` for listeners to fire. Some events intentionally have no listeners.

**Rule:**
- If an event has no intended side effect (cache invalidation, notification, logging beyond the Action's own `$this->log()`), document it as **"intentionally no listeners"** in `config/event.php`
- If an event SHOULD trigger a side effect but the listener doesn't exist yet, document it as **"TODO: needs listener"** in `config/event.php`
- Events in the TODO category must be tracked in their own issue

**Categories:**
| Category | Action | Example |
|----------|--------|---------|
| **Fire-and-forget** (no listener) | Add comment in config | `BackupCompleted`, `AttendanceClockIn`, `GradeCalculated` |
| **Cache invalidation** | Register listener | `SettingUpdated` → `InvalidateSettingsCache` |
| **Notification** | Register listener | `LoginFailed` → `LogLoginFailed` |
| **TODO** (future) | Add comment | `AssessmentFinalized` (needs cache invalidation) |

---

### Decision 2: Event Dispatch Convention (#191)

**Problem:** 115/151 Command Actions (76%) don't dispatch events, so side effects never fire.

**Rule (SHOULD, not MUST):**
> A Command Action SHOULD dispatch an event for **any state change visible to users or other modules**.

**Naming:**
| Verb | Event Name | Example |
|------|-----------|---------|
| Create | `{Entity}Created` | `InternshipCreated` |
| Update | `{Entity}Updated` | `CompanyUpdated` |
| Delete | `{Entity}Deleted` | `AcademicYearDeleted` |
| State change | `{Entity}{PastTenseAction}` | `UserAccountLocked`, `ReportApproved` |

**Event payload:**
```php
class {Entity}{Action} extends BaseEvent
{
    public function __construct(
        public readonly {Model} ${model},  // The affected model
    ) {}
}
```

**Implementation:**
- All new events extend `App\Core\Events\BaseEvent` (provides `eventName()`, `toPayload()`, `broadcastOn()`)
- Events are dispatched via `event(new {Event}({model}))`
- Or queued via `{Event}::dispatch({model})` for I/O-bound listeners

**Scope reduction:**
- Not all 115 need events — only those with **significant state changes** (create, delete, status transition, score calculation)
- Simple `update()` calls that modify non-visible fields (e.g., `touch()`, internal counters) can skip

---

### Decision 3: Log Naming Convention (#193)

**Problem:** 25/151 Command Actions don't call `$this->log()`, so the activity trail is incomplete.

**Rule (MUST):**
> Every Command Action MUST call `$this->log()` before returning from `transaction()`.

**Naming:**
```php
$this->log('{verb}_{entity}', ${entity}, [
    '{entity}_id' => ${entity}->id,
]);
```

| Verb | Log Name | Example |
|------|----------|---------|
| Create | `{entity}_created` | `$this->log('internship_created', $internship)` |
| Update | `{entity}_updated` | `$this->log('company_updated', $company)` |
| Delete | `{entity}_deleted` | `$this->log('placement_deleted', $placement)` |
| State change | `{entity}_{new_state}` | `$this->log('user_locked', $user)` |

---

### Decision 4: LazilyRefreshDatabase Exclusion Criteria (#203)

**Problem:** `LazilyRefreshDatabase` defers migrations until first DB query. Tests that use `User::factory()->create()` (which calls `UserIdentifierGenerator::generateUsername()`) trigger the first DB query, but the migration hasn't run yet.

**Decision:**
- Tests using simple factories (no `User` created via complex relationships) → `LazilyRefreshDatabase`
- Tests using `User::factory()` or `Registration::factory()` (which internally uses `UserIdentifierGenerator`) → **keep `RefreshDatabase`** to avoid the chicken-and-egg migration timing issue
- Tests explicitly testing `UserIdentifierGenerator` → must have explicit database setup

**Previously migrated (38 files):** All straightforward cases. 2 files reverted to `RefreshDatabase`:
- `tests/Unit/Reports/Report/Models/ReportTest.php` — uses `Registration::factory()` → `User::factory()` → triggers `UserIdentifierGenerator`

---

### Decision 5: Test Fix Priority (#201)

**Problem:** 37 test failures across 8 modules when issue was created. Now resolved to 0 (with 1 intermittent ordering failure).

**Decision:**
- Pre-existing flaky tests (database pollution, test ordering) → mark with `@group flaky`, do not block CI
- Tests failing due to stale assertions → update assertion to match current code
- Tests failing due to missing `LazilyRefreshDatabase`/`RefreshDatabase` → add the trait
- Tests referencing deleted/renamed classes → update or delete the test

**Resolution status:** All 37 failures resolved as of 2026-06-29. 1 intermittent ordering failure remains in full-suite run (passes in isolation).

---

### Decision 6: Minimum Test Template (#194)

**Problem:** 63 Livewire components and 34 Events lack tests.

**Livewire minimum test:**
```php
test('renders without errors', function () {
    Livewire::test({Component}::class)
        ->assertViewHas('propertyName');
});

test('performs {action}', function () {
    Livewire::test({Component}::class)
        ->call('{action}')
        ->assertSet('property', expectedValue);
});
```

**Event minimum test:**
```php
test('dispatches with model payload', function () {
    Event::fake([{Event}::class]);

    // Perform action that dispatches the event
    $action->execute($data);

    Event::assertDispatched({Event}::class, fn ($event) => $event->{model}->id === $expectedId);
});
```

**Policy for new tests:**
- Event tests are **high priority** — every untested event should have a simple dispatch test
- Livewire tests are **medium priority** — prioritize interactive components (forms, CRUD tables) over read-only displays
- No Mockery for Eloquent — use real factories with `LazilyRefreshDatabase`

---

## 3. Implementation Phases

### Phase 1: Conventions ✅ (Completed)

| Order | Issue | What Was Done |
|-------|-------|---------------|
| 1 | [#203](https://github.com/reasvyn/internara/issues/203) | Migrated 38 test files from `RefreshDatabase` to `LazilyRefreshDatabase`. 2 files kept `RefreshDatabase` due to `UserIdentifierGenerator` dependency. |
| 2 | [#202](https://github.com/reasvyn/internara/issues/202) | All 6 models already using `#[Fillable]`. Zero `$fillable` arrays remain in `app/`. |
| 3 | [#192](https://github.com/reasvyn/internara/issues/192) | 14 orphaned events documented as TODO, 10 documented as fire-and-forget. Config now covers entire 37-event inventory. |

### Phase 2: Test Fixes ✅ (Completed)

| Order | Issue | What Was Done |
|-------|-------|---------------|
| 4 | [#201](https://github.com/reasvyn/internara/issues/201) | 37 failures → 0. Fixed across 30+ files. 1 intermittent test ordering issue detected (passes in isolation). |

### Phase 3: Action Completeness (Pending)

| Order | Issue | Effort | Key Decision |
|-------|-------|--------|-------------|
| 5 | [#193](https://github.com/reasvyn/internara/issues/193) | Medium | Add `$this->log()` to 25 Actions. Pattern: `$this->log('{verb}_{entity}', $model)` |
| 6 | [#191](https://github.com/reasvyn/internara/issues/191) | Large | Add event dispatch to Actions with significant state changes. Estimate: ~80 Actions need events (not all 115). |

### Phase 4: Test Coverage (Pending)

| Order | Issue | Effort | Key Decision |
|-------|-------|--------|-------------|
| 7 | [#195](https://github.com/reasvyn/internara/issues/195) | Medium | Create listener stubs for 14 TODO events. Each gets a registered listener in `config/event.php`. |
| 8 | [#194](https://github.com/reasvyn/internara/issues/194) | Large | Write tests for 34 Events (high priority) and 63 Livewire components (medium priority). Use template from Decision 6. |

---

## 4. Integration Order

| Phase | Order | Issue | Scope | Dependencies | Design Ref |
|-------|-------|-------|-------|-------------|------------|
| **Actions** | 5 | [#193](https://github.com/reasvyn/internara/issues/193) | 25 Command Action files | — | Decision 3 |
| | 6 | [#191](https://github.com/reasvyn/internara/issues/191) | ~80 Command Action files | 5 (same files, add event after log) | Decision 2 |
| **Coverage** | 7 | [#195](https://github.com/reasvyn/internara/issues/195) | `config/event.php`, 14+ listener files | 3, 6 (events need registration) | Decision 1 |
| | 8 | [#194](https://github.com/reasvyn/internara/issues/194) | 34 Event tests + 63 Livewire tests | 4 (stable tests first) | Decision 6 |

---

## 5. Completed Work

| Work Item | Phase | Date |
|-----------|-------|------|
| Architecture: 12-layer → 4-layer simplification | Foundation | 2026-06-28 |
| Layer 1 audit & refactor (Support → Services) | Foundation | 2026-06-28 |
| Cross-Role Proxy: all 5 phases | Proxy | 2026-06-28 |
| [#203](https://github.com/reasvyn/internara/issues/203) LazilyRefreshDatabase migration | Conventions | 2026-06-29 |
| [#202](https://github.com/reasvyn/internara/issues/202) #[Fillable] attributes | Conventions | 2026-06-29 |
| [#192](https://github.com/reasvyn/internara/issues/192) Event registry | Conventions | 2026-06-29 |
| [#201](https://github.com/reasvyn/internara/issues/201) Test failures | Test Fixes | 2026-06-29 |
| [#193](https://github.com/reasvyn/internara/issues/193) Missing ->log() | Actions | 2026-06-29 |
| [#191](https://github.com/reasvyn/internara/issues/191) Event dispatch (partial) | Actions | 2026-06-29 |
| [#195](https://github.com/reasvyn/internara/issues/195) Event listeners | Coverage | 2026-06-29 |
| [#194](https://github.com/reasvyn/internara/issues/194) Event tests (partial) | Coverage | 2026-06-29 |
