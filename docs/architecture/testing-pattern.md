# Testing Pattern Reference

> **Last updated:** 2026-06-10
>
> Comprehensive catalog of testing patterns, conventions, and practices used across the Internara
> codebase. Distilled from `docs/infrastructure/testing.md`, `docs/conventions.md` §22,
> `docs/architecture.md`, skill files, and ~200+ test files in the codebase.
>
> Every pattern here exists because it solves a concrete testing problem. Use judgment — if a
> pattern adds no value for your specific test, the simpler approach is fine.

---

## Table of Contents

1. [Testing Philosophy](#1-testing-philosophy)
2. [Test Structure & File Organization](#2-test-structure--file-organization)
3. [Scope Isolation](#3-scope-isolation)
4. [Test Naming Conventions](#4-test-naming-conventions)
5. [Database Handling](#5-database-handling)
6. [Test Type Categories](#6-test-type-categories)
7. [Layer-Specific Testing Strategies](#7-layer-specific-testing-strategies)
8. [Assertion Preferences](#8-assertion-preferences)
9. [Factory Usage](#9-factory-usage)
10. [Mocking & Faking Policies](#10-mocking--faking-policies)
11. [Test Infrastructure](#11-test-infrastructure)
12. [Performance Optimizations](#12-performance-optimizations)
13. [What NOT to Test](#13-what-not-to-test)
14. [Quality Enforcement](#14-quality-enforcement)
15. [CI Pipeline](#15-ci-pipeline)

---

## 1. Testing Philosophy

### 1.1 Every Change Must Have Tests
Every code change — feature, refactor, bug fix — must be accompanied by tests that verify the
change works correctly and do not break existing behavior. A change is not complete until its
tests pass.

### 1.2 Red-Green-Refactor (TDD)
Write the test first, watch it fail, then write the minimum implementation to make it pass, then
refactor. The test stays green throughout refactoring.

```bash
# 1. Write a failing test
php artisan make:test --pest CreateInternshipActionTest

# 2. Confirm it fails
php artisan test --compact --filter=CreateInternshipAction

# 3. Write the implementation

# 4. Confirm it passes
php artisan test --compact --filter=CreateInternshipAction

# 5. Refactor and re-run
```

### 1.3 Layer-by-Layer TDD Entry Points

| Layer | TDD Entry Point | What You Test First |
|-------|----------------|---------------------|
| **Enum** | Unit test | Assert `label()`, transition rules, terminal states |
| **Entity** | Unit test | Construct with test data, assert business rule methods |
| **Command Action** | Feature test | Factory + execute → assert database state changed |
| **Read Action** | Feature test | Set up data → call method → assert returned structure |
| **Process Action** | Feature test | Complete workflow + partial failure scenarios |
| **Livewire** | Feature test | Render → interact → assert component state / redirect |
| **Policy** | Unit test | Mock user/model → assert boolean gate methods |
| **Console Command** | Feature test | Call command → assert exit code / output |

TDD order follows bottom-up dependency: enums and entities have no dependencies and can be tested
first, then Actions, then Livewire components that consume them.

---

## 2. Test Structure & File Organization

### 2.1 Module-First Structure
Tests mirror the source structure exactly. Every file in `app/` has a corresponding test file in
`tests/`:

```
tests/
├── Feature/{Module}/{SubModule}/{Name}Test.php     → Integration tests (Actions, Livewire)
├── Unit/{Module}/{SubModule}/{Name}Test.php          → Isolated tests (Entities, Enums)
└── {Feature,Unit}/{Component}/{Name}Test.php         → Shared component tests
```

**Examples:**
- `app/User/Profile/Actions/UpdateProfileAction.php`
  → `tests/Feature/User/Profile/UpdateProfileActionTest.php`
- `app/User/Entities/Apprentice.php`
  → `tests/Unit/User/Entities/ApprenticeTest.php`
- `app/Core/Data/BaseData.php`
  → `tests/Unit/Core/Data/BaseDataTest.php`

### 2.2 Three Test Tiers

| Tier | Directory | Database | Speed | Tests What |
|------|-----------|----------|-------|-----------|
| **Unit** | `tests/Unit/{Module}/` | Never | Fast (ms) | Entities, Enums, DTOs, Policies, Support, Contracts |
| **Feature** | `tests/Feature/{Module}/` | Always | Medium (s) | Actions, Livewire, Console Commands, Middleware, Events |
| **Arch** | `tests/Arch/` | Never | Fast (ms) | Structural rules via `arch()` expectations |

### 2.3 Value Object Tests
Value objects, flat enums, and small validation rules that belong to a module but are too small
for their own submodule go under `tests/Unit/{Module}/Types/`.

### 2.4 Namespace Convention
Test files may omit the namespace declaration (Pest convention). When used, namespace mirrors the
directory:

```php
namespace Tests\Unit\Core\Entities;  // for tests/Unit/Core/Entities/BaseEntityTest.php
namespace Tests\Feature\Settings\Actions;  // for tests/Feature/Settings/Actions/SetSettingActionTest.php
```

---

## 3. Scope Isolation

### 3.1 One File, One Scope — CRITICAL
This is the single most important testing rule. **Do NOT combine multiple distinct testing scopes
into a single test file.**

✅ **Correct:**
- `tests/Feature/Academics/AcademicYear/CreateAcademicYearActionTest.php`
- `tests/Feature/Academics/AcademicYear/UpdateAcademicYearActionTest.php`
- `tests/Feature/Academics/AcademicYear/DeleteAcademicYearActionTest.php`

❌ **Wrong:**
- `tests/Feature/Academics/AcademicYear/AcademicYearActionsTest.php` (groups create + update + delete)
- `tests/Feature/Academics/ConsoleCommandsTest.php` (groups multiple commands)
- `tests/Feature/SetupTest.php` (groups entire module)

### 3.2 Multi-Angle Coverage Per Scope
Each dedicated test file must thoroughly cover its target from multiple angles:

| Angle | What It Tests |
|-------|--------------|
| **Happy path** | Standard input produces expected output |
| **Validation constraints** | Invalid input produces correct errors |
| **Edge cases** | Boundary values, empty states, nulls |
| **Error handling** | Graceful failures, logging, exception messages |

### 3.3 Dedicated Files for Each Component
- Every **Action** has its own test file
- Every **Console Command** has its own test file
- Every **Livewire component** has its own test file
- Every **Policy** has its own test file

---

## 4. Test Naming Conventions

### 4.1 File Naming
Files use PascalCase with `Test.php` suffix: `CreateInternshipActionTest.php`.

### 4.2 Test Descriptions
Use `it()` for descriptive sentences that read as specifications:

```php
it('creates an internship with active academic year', function () { ... });
it('rejects creation when academic year is missing', function () { ... });
it('assigns default status of draft', function () { ... });
```

The `it()` description should complete the sentence: "it **creates an internship with active
academic year**".

### 4.3 Grouping with `describe()`
Use `describe()` to group related tests by subject:

```php
describe('CreateInternshipAction', function () {
    it('creates an internship with active academic year', function () { ... });
    it('rejects creation when academic year is missing', function () { ... });
});

describe('isTerminal', function () {
    it('archived is terminal', function () { ... });
    it('activated is not terminal', function () { ... });
});
```

### 4.4 Simple `test()` for Flat Structure
For simple tests without grouping, use `test()`:

```php
test('base entity can be instantiated from model', function () { ... });
test('base entity is readonly and immutable', function () { ... });
```

---

## 5. Database Handling

### 5.1 `LazilyRefreshDatabase` (Preferred)
Defers database migration until the first query hits the database. Tests that do not touch the
database skip migration entirely. Use for all feature tests:

```php
uses(LazilyRefreshDatabase::class);
```

### 5.2 `RefreshDatabase` (When Needed)
Use only when `LazilyRefreshDatabase` causes issues with specific test scenarios (e.g., Setup
module tests that need full migration rollback):

```php
uses(RefreshDatabase::class);
```

### 5.3 In-Memory SQLite
The test suite runs against an in-memory SQLite database. Zero configuration needed:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### 5.4 Entity Tests — NO Database
Entities are `final readonly` classes with zero framework dependencies. They never touch the
database. Testing them is pure function calls:

```php
$entity = new Apprentice(
    status: AccountStatus::ACTIVATED,
    isLocked: false,
    setupRequired: false,
);
expect($entity->isLocked())->toBeFalse();
```

### 5.5 Schema for Base Class Tests
When testing base classes that need tables, create them in `beforeEach()` and clean up in
`afterEach()`:

```php
beforeEach(function () {
    Schema::create('test_base_models', function ($table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_base_models');
});
```

---

## 6. Test Type Categories

| Test Type | Database | Speed | Tests |
|-----------|----------|-------|-------|
| **Unit** | No | ms | Entities, Enums, DTOs, Policies, Contracts, Support utilities, Exceptions |
| **Feature** | Yes (SQLite) | seconds | Actions, Livewire, Console Commands, Middleware, Events, Models |
| **Arch** | No | ms | Structural rules (`arch()` expectations) |

### 6.1 Shared Component Tests
Code shared across multiple modules (Core base classes, global Livewire components) has tests
directly under `tests/{Feature,Unit}/{Component}/`:

- `tests/Unit/Core/Data/ActionResponseTest.php`
- `tests/Unit/Core/Contracts/LabelEnumTest.php`
- `tests/Feature/Core/Support/SmartLoggerTest.php`

---

## 7. Layer-Specific Testing Strategies

### 7.1 Entity Tests

| Pattern | Example |
|---------|---------|
| Direct instantiation, no DB | `new Apprentice(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false)` |
| Assert business rule methods | `expect($entity->canBeDeleted())->toBeTrue()` |
| Test `fromModel()` factory | `MockEntity::fromModel($model)` then assert entity values |
| Test `equals()` and `with()` | `$a->equals($b)`, `$entity->with('name', 'Modified')` |
| Enforce `final readonly` via reflection | `expect($prop->isReadOnly())->toBeTrue()` for all properties |

### 7.2 Enum Tests

| Pattern | Example |
|---------|---------|
| Assert `label()` for each case | `expect(InternshipStatus::PUBLISHED->label())->toBeString()` |
| Assert `canTransitionTo()` | `expect(DRAFT->canTransitionTo(PUBLISHED))->toBeTrue()` |
| Assert `isTerminal()` | `expect(COMPLETED->isTerminal())->toBeTrue()` |
| Assert `validTransitions()` empty for terminal | `expect(COMPLETED->validTransitions())->toBe([])` |
| Cross-type rejection | `DRAFT->canTransitionTo($mockImplementingStatusEnum)` returns `false` |
| Group by behavior | `describe('isTerminal', ...)`, `describe('transitions', ...)` |

### 7.3 Data / DTO Tests

| Pattern | Example |
|---------|---------|
| Constructor + property assertions | `new InternshipData(name: 'PKL 2025')` → `expect($data->name)->toBe('PKL 2025')` |
| `fromArray()` hydration | `SettingData::fromArray(['key' => 'test'])` |
| `toArray()` serialization | `$data->toArray()` matches expected array |
| Snake_case key conversion | `fromArray(['is_admin' => true])` → `$data->isAdmin === true` |
| Defaults for missing params | `fromArray(['name' => 'John'])` → `$data->isAdmin === false` |
| `only()` and `except()` | `$dto->only('name')`, `$dto->except('age')` |
| `merge()` creates new immutable instance | `$original->merge(['name' => 'Updated'])` |
| `from()` polymorphic source | `MockData::from(['name' => 'X'])`, `MockData::from($objectWithToArray)` |

### 7.4 Command Action Tests (Feature)

| Pattern | Example |
|---------|---------|
| Resolve from container | `$action = app(CreateInternshipAction::class);` |
| Factory setup → execute → assert DB | `$action->execute([...]); assertDatabaseHas('internships', [...])` |
| Assert result is model instance | `expect($internship)->toBeInstanceOf(Internship::class)` |
| `fresh()` to get DB state | `expect($partnership->fresh()->status->value)->toBe('terminated')` |
| Validation exceptions | `expect(fn () => $action->execute([...]))->toThrow(ValidationException::class)` |
| Domain rule exceptions | `expect(fn () => $action->execute(...))->toThrow(RejectedException::class)` |

### 7.5 Read Action Tests (Feature)

| Pattern | Example |
|---------|---------|
| Set up data → call → assert structure | `$data = $reader->activeCount(); expect($data)->toBeInt()` |
| No DB mutation expected | Assert return values only, no `assertDatabaseHas` |
| Typed return assertions | `expect($data)->toBeInstanceOf(Collection::class)` |

### 7.6 Process Action Tests (Feature)

| Pattern | Example |
|---------|---------|
| Mock child Actions for orchestration | `Mockery::mock(CreateRegistrationAction::class)->shouldReceive('execute')->once()` |
| Inject mocks via constructor | `new RegisterStudentProcess($createMock, $assignMock, $notifyMock)` |
| Full workflow + partial failure | Both happy path and rollback scenarios |

### 7.7 Livewire Tests (Feature)

| Pattern | Example |
|---------|---------|
| `Livewire::test()` entry point | `Livewire::test(ThemeSwitcher::class)` |
| `->assertSet()` for properties | `->assertSet('theme', 'system')` |
| `->assertViewIs()` for views | `->assertViewIs('settings.livewire.theme-switcher')` |
| `->call()` → `->assertSet()` | `->call('setTheme', 'dark')->assertSet('theme', 'dark')` |
| `->assertDispatched()` for events | `->assertDispatched('theme-changed', theme: 'dark')` |
| Invalid input falls back gracefully | `->call('setTheme', 'invalid')->assertSet('theme', 'system')` |

### 7.8 Policy Tests (Unit)

| Pattern | Example |
|---------|---------|
| Direct gate method assertions | `expect($policy->isAdmin($user))->toBeTrue()` |
| Anonymous classes as mocks | `$user = new class extends User { public function hasRole(...) { return true; } };` |
| No DB needed | Pure boolean gate logic |

### 7.9 Console Command Tests (Feature)

| Pattern | Example |
|---------|---------|
| `$this->artisan()` | `$this->artisan('module:discover')->assertExitCode(0)` |
| `assertExitCode()` | `->assertExitCode(1)` for failure |
| `expectsOutputToContain()` | `->expectsOutputToContain(__('setup.cli.already_installed'))` |
| `expectsConfirmation()` | `->expectsConfirmation(__('setup.cli.proceed_confirm'), 'no')` |
| `partialMock()` for services | `$this->partialMock(SystemProvisioner::class, function ($mock) { ... })` |

### 7.10 Middleware Tests (Feature)

| Pattern | Example |
|---------|---------|
| Register test route in `beforeEach` | `Route::get('/_test', fn () => 'ok')->middleware(SecurityHeaders::class)` |
| `$this->get()` + `assertHeader()` | `$response->assertHeader('X-Content-Type-Options', 'nosniff')` |
| `File::shouldReceive()` for filesystem | `File::shouldReceive('exists')->once()->andReturnTrue()` |

### 7.11 Event / Listener Tests (Feature)

| Pattern | Example |
|---------|---------|
| `Event::fake([SpecificEvent::class])` after factory | `$user = User::factory()->create(); Event::fake([NotificationSent::class])` |
| Direct listener instantiation | `$listener = app(InvalidateSettingsCache::class); $listener->handle(new SettingUpdated(...))` |
| Cache before/after assertions | `expect(Cache::get($cacheKey))->toBeNull()` |

### 7.12 Support / Utility Tests (Unit)

| Pattern | Example |
|---------|---------|
| `describe('methodName', ...)` groupings | `describe('maskArray', ...)`, `describe('maskEmail', ...)` |
| Anonymous classes with trait usage | `$trait = new class { use HandlesActionErrors; public function run(...) { ... } };` |
| `Event::fake([MessageLogged::class])` for logger | `Event::assertDispatched(MessageLogged::class, fn ($e) => $e->message === 'test')` |

---

## 8. Assertion Preferences

| Preference | Over | Rationale |
|------------|------|-----------|
| `assertModelExists($model)` | `assertDatabaseHas()` | Clearer intent — says exactly what's checked |
| `expect()->toBe*()` | `$this->assert*()` | Pest's fluent API |
| `fn () => ... ->toThrow()` | `$this->expectException()` | Cleaner inline exception assertions |
| `->toBeTrue()` / `->toBeFalse()` | `->assertTrue()` / `->assertFalse()` | Pest fluent style |
| `->toBeInstanceOf()` | Type checks | Verify return types |
| `->toHaveCount()` | `assertCount()` | Collection count |
| `->toHaveKeys()` | Multiple assertions | Array key presence |
| `->toContain()` | `str_contains()` with assert | String containment |
| `->not->toBe()` / `->not->toContain()` | Negative assertions | Inverse checks |
| `->toMatchArray()` | Multiple array assertions | Partial array matching |

---

## 9. Factory Usage

### 9.1 Factory States Over Manual Creation
Use factory states and sequences instead of manually setting up model attributes:

```php
// ✅ Prefer factory states
$year = AcademicYear::factory()->create();
$activeYear = AcademicYear::factory()->active()->create();

// ❌ Avoid manual creation when a state exists
$year = new AcademicYear;
$year->name = '2025/2026';
$year->save();
```

### 9.2 Factory with Specific Attributes
Override defaults for specific test scenarios:

```php
User::factory()->create(['email' => 'same@example.com']);
Partnership::factory()->create([
    'company_id' => $company->id,
    'status' => 'active',
]);
```

### 9.3 Sequences (for Unique Data)
Use factory sequences when each record needs different values:

```php
User::factory()
    ->count(3)
    ->sequence(
        ['email' => 'a@test.com'],
        ['email' => 'b@test.com'],
        ['email' => 'c@test.com'],
    )
    ->create();
```

### 9.4 `recycle()` (for Shared Instances)
Reuse shared relationship instances across factories to avoid redundant creation:

```php
$company = Company::factory()->create();
Partnership::factory()
    ->count(3)
    ->recycle($company)
    ->create();
```

---

## 10. Mocking & Faking Policies

### 10.1 Minimize Mocking
Prefer real implementations over mocks. Mock only when:
- Testing Process Action orchestration (mock child Actions)
- Testing external services (mail, HTTP, filesystem)
- The real implementation would cause side effects in tests

### 10.2 `Event::fake()` AFTER Factory Setup
UUID generation fires events during model creation. Fake AFTER setup:

```php
// ✅ Correct: fake after factory
$user = User::factory()->create();
Event::fake([NotificationSent::class]);
$notification = app(SendNotificationAction::class)->execute(...);
Event::assertDispatched(NotificationSent::class);

// ❌ Wrong: fake silences UUID events
Event::fake();
$user = User::factory()->create();  // UUID events silenced
```

### 10.3 Fake Specific Events, Not All
Fake only the events you need to assert on:

```php
Event::fake([MessageLogged::class]);          // ✅ Specific
Event::fake([NotificationSent::class]);       // ✅ Specific
Event::fake();                                // ❌ Avoid — silences everything
```

### 10.4 Same Pattern for `Http::fake()`
```php
Http::fake();                                   // Setup fake first
Http::preventStrayRequests();                   // Catch unexpected calls
$response = Http::get('https://api.example.com');
```

### 10.5 Mockery for Process Action Tests
Mock child Actions to verify orchestration:

```php
$childMock = Mockery::mock(CreateRegistrationAction::class);
$childMock->shouldReceive('execute')->once()->andReturn($registration);
$process = new RegisterStudentProcess($childMock, ...);
$result = $process->execute($data);
```

### 10.6 Anonymous Classes for Simple Mocks
For simple policy or model mocks, use anonymous classes:

```php
$user = new class extends User {
    public function hasRole($roles, ?string $guard = null): bool
    {
        return $roles === 'admin';
    }
};
```

---

## 11. Test Infrastructure

### 11.1 Base Test Case (`tests/TestCase.php`)
Extends `Illuminate\Foundation\Testing\TestCase`. Sets up:
- `setup.is_installed` setting (required for app boot)
- `Gate::before()` callback for `super_admin` bypass

### 11.2 Pest Configuration (`tests/Pest.php`)
```php
pest()
    ->extend(Tests\TestCase::class)
    ->in(__DIR__.'/Feature', __DIR__.'/Unit');
```

### 11.3 Global Helpers
Defined in `tests/Pest.php` for common acting-as scenarios:

```php
function actingAsSuperAdmin(): User { /* creates super_admin, actingAs */ }
function actingAsAdmin(): User { /* creates admin, actingAs */ }
function actingAsStudent(): User { /* creates student, actingAs */ }
```

### 11.4 PHPUnit Configuration (`phpunit.xml`)

| Setting | Value | Purpose |
|---------|-------|---------|
| `memory_limit` | `512M` | Prevents memory exhaustion |
| `APP_ENV` | `testing` | Testing environment |
| `BCRYPT_ROUNDS` | `4` | Fast hashing |
| `CACHE_STORE` | `array` | In-memory cache |
| `DB_CONNECTION` | `sqlite` | Fastest backend |
| `DB_DATABASE` | `:memory:` | In-memory database |
| `MAIL_MAILER` | `array` | No real mail |
| `QUEUE_CONNECTION` | `sync` | Immediate execution |
| `SESSION_DRIVER` | `array` | In-memory session |
| `PULSE_ENABLED` | `false` | No Pulse overhead |

### 11.5 Coverage Configuration (`phpunit.coverage.xml`)
- **Minimum coverage:** 80% (enforced in CI)
- **Engine:** pcov (faster than xdebug)
- **Reports:** HTML (`storage/coverage/html/`) + Clover (`storage/coverage/clover/`)

### 11.6 Composer Scripts

| Script | Command | Use Case |
|--------|---------|----------|
| `test` | `@php artisan test` | Full suite with cache cleared |
| `test:coverage` | `vendor/bin/pest --coverage --min=80` | Coverage check |
| `test:feature` | `vendor/bin/pest tests/Feature` | Feature tests only |
| `test:unit` | `vendor/bin/pest tests/Unit` | Unit tests only |
| `coverage` | `php -d extension=pcov.so vendor/bin/pest --coverage` | Full coverage report |

---

## 12. Performance Optimizations

| Optimization | Why |
|-------------|-----|
| **`LazilyRefreshDatabase`** | Skips migration if schema already current |
| **Entity tests with NO database** | Milliseconds; pure function calls |
| **`assertModelExists()` over `assertDatabaseHas()`** | Clearer intent, same speed |
| **In-memory SQLite** | Fastest possible DB |
| **`BCRYPT_ROUNDS = 4`** | Password hashing 100x faster |
| **Cache/Queue/Mail = array/sync** | No external processes or filesystem I/O |
| **`--compact` flag** | Faster test output parsing |
| **`--filter` flag** | Run only the relevant test |
| **Pulse/Telescope disabled** | No monitoring overhead |
| **Factory states over manual creation** | Fewer DB queries, less code |

---

## 13. What NOT to Test

| Don't Test | Why |
|-----------|-----|
| **Eloquent relationships directly** | Framework behavior; test through Actions |
| **Simple getters/setters** | Trivial passthrough code |
| **Configuration loading** | Framework behavior |
| **Framework-provided functionality** | UUID generation, pagination — Laravel's job |
| **Simple model scopes in isolation** | Test through Actions that use them |
| **Trivial views** | Only needed when underlying Actions are untested |

---

## 14. Quality Enforcement

### 14.1 Pre-Commit Checklist (Testing)

- [ ] `declare(strict_types=1)` present
- [ ] No `dd()`, `dump()`, `var_dump()`, `ray()` left in code
- [ ] Tests pass: `php artisan test --compact`
- [ ] Action follows correct triad pattern (Command/Read/Process)
- [ ] Each Action/component has its own dedicated test file
- [ ] Entity tests avoid `RefreshDatabase`
- [ ] Action tests use `LazilyRefreshDatabase` when possible
- [ ] `Event::fake()` positioned after factory setup
- [ ] Pint clean: `vendor/bin/pint --format agent`

### 14.2 Tool Chain

| Tool | What It Enforces | Command |
|------|------------------|---------|
| **Laravel Pint** | PHP code style | `vendor/bin/pint --format agent` |
| **PHPStan** | Type safety, dead code | `vendor/bin/phpstan analyse --no-progress` |
| **Prettier** | Markdown, JSON, YAML, Blade | `npm run format` |
| **Pest** | Test correctness | `php artisan test --compact` |
| **Code Review** | Architecture patterns | Manual PR review |

---

## 15. CI Pipeline

### 15.1 Jobs

| Job | What It Runs | Tool |
|-----|-------------|------|
| **quality** | Pint + PHPStan + composer audit | `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` |
| **architecture** | Arch tests | `vendor/bin/pest tests/Arch` |
| **tests** | Feature + Unit with 80% min coverage | `vendor/bin/pest --coverage --min=80` |
| **security** | Filesystem vulnerability scan | `trivy` |
| **summary** | Aggregates all job results | Shell script |

### 15.2 Configuration
- **Trigger:** Push + PR to `main` and `develop`
- **PHP:** 8.4 with required extensions
- **Coverage:** Uploaded to Codecov via `codecov/codecov-action@v4`
- **Fail policy:** Any job failure blocks the pipeline

---

## Quick Reference

### Common Test Commands

```bash
# Run a specific test class
php artisan test --compact --filter=CreateInternshipAction

# Run a single test method
php artisan test --compact --filter='it creates an internship'

# Run all tests for a submodule
php artisan test --compact --filter=Internship

# Run full suite before committing
php artisan test --compact

# Check coverage
composer run coverage

# Run unit tests only
composer run test:unit

# Run feature tests only
composer run test:feature
```

### Common Test Template

```php
<?php

declare(strict_types=1);

namespace Tests\{Feature,Unit}\{Module}\{SubModule};

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('{SubjectName}', function () {
    it('{does something under condition}', function () {
        // Arrange
        // Act
        // Assert
        expect($result)->{toBeSomething}();
    });
});
```

---

*This document is auto-synchronized with the codebase. When testing practices evolve, update the
relevant sections in `docs/infrastructure/testing.md`, `docs/conventions.md` §22, or the skill
files, then reflect changes here. See `docs/doc-index.md` for the complete documentation catalog.*
