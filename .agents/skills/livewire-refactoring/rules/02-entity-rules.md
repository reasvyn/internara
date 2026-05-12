# Entity Rules

Business rules live in Entity classes — plain PHP objects with no framework dependencies.

## Structure

```php
final readonly class Apprentice extends BaseEntity
{
    public function __construct(
        private AccountStatus $status,
        private bool $isLocked,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: AccountStatus::tryFrom($model->latestStatus()?->name ?? ''),
            isLocked: $model->locked_at !== null,
        );
    }

    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::SUSPENDED;
    }

    public function canTransitionTo(AccountStatus $target): bool
    {
        return in_array($target, $this->status->validTransitions(), true);
    }
}
```

## Rules

### 1. Pure PHP
- No `use Illuminate\*` imports (except in `BaseEntity` for `Model` bridge)
- No Eloquent, no Facades, no Service Container
- Only PHP primitives, Enums, and other Entities

### 2. Bridge via `fromModel()`
- The only connection to the ORM is the `static fromModel(Model $model)` factory
- Callers go through `$model->as{EntityName}()->method()`
- No generic `entity()` method — each model has a named accessor

### 3. Final + Readonly
- All entities are `final readonly` classes extending `BaseEntity`
- Constructor property promotion with `private` visibility

### 4. Testable Without Database
```php
// Unit test — no database needed
test('suspended user cannot log in', function () {
    $entity = new Apprentice(
        status: AccountStatus::SUSPENDED,
        isLocked: false,
    );
    expect($entity->isSuspended())->toBeTrue();
});
```

## What to Move to Entities

| Instead of this in Component/Action | Put it in Entity |
|---|---|
| `if ($user->is_active && !$user->locked_at)` | `$user->asApprentice()->canLogin()` |
| `if ($year->is_active) throw ...` | `$year->asAcademicYearState()->canBeDeleted()` |
| `$status->value === 'suspended'` | `$status->isTerminal()` (on the Enum) |
| Complex permission checks | Entity method with clear name |
