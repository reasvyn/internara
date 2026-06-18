---
name: pest-testing
description: SDLC Phase: TESTING. Test writing, editing, and fixing using Pest — feature tests, unit tests, architecture tests, Livewire component tests.
upstream:
  - feature-building
  - code-refactoring
  - livewire-development
  - medialibrary-development
downstream:
  - feature-building
  - sync-docs
---

# Pest Testing Skill

## When to Activate

Apply this skill whenever writing, editing, or fixing tests. Activates for all testing tasks — feature tests, unit tests, and Livewire component tests using Pest.

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `feature-building` — new code needing tests |
| | `code-refactoring` — refactored code needing characterization tests |
| | `livewire-development` — new components |
| | `medialibrary-development` — upload functionality |
| **This skill** | **TESTING** — produces test files |
| **Downstream (output)** | `feature-building` — tests integrated into feature completion |
| | `sync-docs` — documentation updated with test references |
| **Phase** | [Planning] → [Analysis] → [Design] → [Implementation] → Testing → [Maintenance] |

## Key References

- **Architecture (testing strategy)**: `docs/architecture.md#testing-strategy`
- **Testing Pattern**: `docs/architecture/testing-pattern.md`
- **BaseEntity**: `app/Core/Entities/BaseEntity.php`
- **BaseAction**: `app/Core/Actions/BaseAction.php`

## Module-First Test Structure

Tests mirror source structure exactly:

```
tests/Feature/{Module}/{SubModule}/{Name}Test.php   → Actions, Livewire (integration)
tests/Unit/{Module}/{SubModule}/{Name}Test.php      → Entities, Enums (pure unit)
tests/{Feature,Unit}/{Component}/{Name}Test.php     → Shared components (Data, Enums, Livewire)
```

Create tests: `php artisan make:test --pest {Name}Test` (omit `Feature/` or `Unit/` prefix).

## Scope Isolation (Critical)

**Each Action, command, and component gets its own dedicated test file.** Do not combine multiple scopes into a single file (e.g., `ConsoleCommandsTest` grouping separate commands).

## Layer Testing Strategy

| Layer | Test Type | Database | Base Class |
|-------|-----------|----------|------------|
| Entity | Unit | No | Instantiate directly: `new Entity(...)` |
| Enum | Unit | No | Assert `label()`, transitions, terminals |
| DTO/Data | Unit | No | Constructor → `toArray()` |
| Policy | Unit | No | Mock user/model → assert gates |
| Command Action | Feature | Yes (`LazilyRefreshDatabase`) | Resolve from container, call `execute()` |
| Read Action | Feature | Yes | Resolve, call method, assert result |
| Process Action | Feature | Yes | Full workflow + partial failure scenarios |
| Livewire | Feature | Yes | `Livewire::test()` → interact → assert |

### Entity Tests (No Database)

```php
describe('Apprentice', function () {
    it('prevents login when locked', function () {
        $entity = new Apprentice(status: 'active', emailVerifiedAt: now(), setupRequired: false, lockedAt: now()->toDateTimeString());

        expect($entity->allowsLogin())->toBeFalse();
    });
});
```

### Action Tests (With Database)

```php
describe('CreateInternshipAction', function () {
    it('creates an internship with valid data', function () {
        $action = app(CreateInternshipAction::class);
        $data = new CreateInternshipData(name: 'Summer Program', ...);

        $internship = $action->execute($data);

        assertModelExists($internship);
        expect($internship->name)->toBe('Summer Program');
    });
});
```

## Performance Preferences

| Preference | Over |
|------------|------|
| `LazilyRefreshDatabase` | `RefreshDatabase` (skips replay if schema current) |
| `assertModelExists()` | `assertDatabaseHas()` (clearer intent) |
| Factory states and sequences | Manual model creation |
| Fakes **after** factory setup | Fakes before (UUID events must not be silenced) |

## TDD Workflow

Follow the architecture's bottom-up dependency order:

1. **Enum** — define state machine, transitions (unit test)
2. **Entity** — define business rules (unit test, no DB)
3. **Command Action** — persistence, transactions (feature test)
4. **Read Action** — complex queries (feature test)
5. **Process Action** — multi-step orchestration (feature test)
6. **Livewire** — UI interactions (feature test)
7. **Policy** — authorization gates (unit test)
8. **Console Command** — CLI interactions (feature test)

## Verification

- Tests in correct directory (Feature vs Unit, right Module/SubModule)?
- Entity tests avoid `RefreshDatabase` entirely?
- Action tests use `LazilyRefreshDatabase`?
- `assertModelExists()` preferred over `assertDatabaseHas()`?
- `Event::fake()` and `Http::fake()` positioned **after** factory setup?
- Each Action/component has its own dedicated test file?
- Action triad type correct (Command, Read, Process)?
