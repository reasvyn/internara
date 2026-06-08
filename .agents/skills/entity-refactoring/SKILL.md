---
name: entity-refactoring
description: Apply this skill when creating new Models, when a Model accumulates boolean capability checks or conditional logic that feels out of place, when you see inline business rules in Controllers or Livewire components, or when writing tests for business logic that currently require a database.
---

# Entity Refactoring Skill

## When to Activate

Apply this skill when creating new Models, when a Model accumulates `canX()`, `isY()`, or multi-field conditionals, when you see inline business rules in Controllers or Livewire components, or when business logic tests require a database when they shouldn't.

## Core Principle

Models handle **data access** (relationships, scopes, casts, queries). Entities handle **business rules** (capability checks, state transitions, policy decisions) as pure PHP objects.

This exists because:
- Business rules are easier to test **without a database**
- Models already own serialization, relationships, events, and factory definitions
- Entities can be reused across different data sources

## Key References

- **BaseEntity**: `app/Core/Entities/BaseEntity.php` — `readonly abstract class` with `fromModel(Model): static` contract
- **BaseModel**: `app/Core/Models/BaseModel.php` — UUID PK via `HasUuids`
- **Architecture docs**: `docs/architecture.md#layered-architecture` (Layer 6 — Domain Rules)
- **Conventions**: `docs/conventions.md#7-entities`

## Entity Contract

```php
final readonly class Apprentice extends BaseEntity
{
    public function __construct(
        private string $status,
        private ?Carbon $emailVerifiedAt,
        private bool $setupRequired,
        private ?string $lockedAt,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
            emailVerifiedAt: $model->email_verified_at,
            setupRequired: $model->setup_required,
            lockedAt: $model->locked_at,
        );
    }

    public function allowsLogin(): bool
    {
        return $this->status === 'active'
            && $this->emailVerifiedAt !== null
            && !$this->setupRequired
            && $this->lockedAt === null;
    }
}
```

### Rules

- `final readonly` class extending `BaseEntity`
- All state via constructor with `private` typed properties — expose via methods
- `fromModel(Model): static` factory — the only import from Eloquent
- **Framework dependencies ARE allowed** when practical (Carbon, Eloquent Model parameter in `fromModel`)
- Zero I/O, zero HTTP, zero persistence logic
- May use static factory methods (e.g., `SchoolEntity::get()`) for settings-backed entities

## Bridge Pattern

Models expose entities via named accessors that describe the **business role**, not the class name:

```php
// ✅ Correct — describes the role
public function asApprentice(): Apprentice
public function asInternshipPeriod(): InternshipPeriod

// ❌ Wrong — generic name
public function entity(): Apprentice
```

## What Moves Where

| Code | Moves To |
|------|---------|
| `$user->status === 'active'` checks | Entity method `isActive(): bool` |
| Date comparisons (`$internship->end_date < now()`) | Entity method `isExpired(): bool` |
| Multi-field conditionals | Entity method with descriptive name |
| `canTransitionTo()` logic | State machine enum implementing `StatusEnum` |
| `$fillable` / `$casts` | Stays on Model |
| Scopes, relationships, accessors | Stays on Model |

## Workflow: Extracting an Entity

1. Identify business rule conditionals in the Model (boolean checks, state transitions, date logic)
2. Create `app/{Module}/{SubModule}/Entities/{Name}.php` as `final readonly` extending `BaseEntity`
3. Extract relevant state into typed constructor parameters
4. Implement `fromModel(Model): static` to bridge from persistence
5. Move business rule methods from the Model to the Entity
6. Add a named accessor on the Model (e.g., `asApprentice(): Apprentice`)
7. Update callers to use `$model->asEntity()->method()` instead of inline checks
8. Write unit tests — no database needed

## Verification

- `final readonly` extending `BaseEntity`?
- `fromModel(Model): static` factory method present?
- Zero Eloquent/Facade imports except `Illuminate\Database\Eloquent\Model` in `fromModel`?
- Named accessor on Model (not generic `entity()`)?
- Business rules moved out of Model and Action?
- `declare(strict_types=1)` on every file?
