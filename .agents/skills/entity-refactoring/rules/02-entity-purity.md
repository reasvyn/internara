# Entity Purity

Entities are `final readonly` classes with zero framework dependencies.

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
}
```

## Rules

### 1. Final + Readonly
```php
final readonly class MyEntity extends BaseEntity
```

### 2. No Framework Imports
```php
// ❌ NOT allowed
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Models\SomeModel;

// ✅ Allowed (only in BaseEntity for the bridge)
use Illuminate\Database\Eloquent\Model;
```

### 3. Constructor = All Dependencies
```php
public function __construct(
    private AccountStatus $status,
    private bool $isLocked,
    private ?Carbon $expiresAt,
) {}
```

All state is passed in via constructor. No hidden dependencies.

### 4. Factory Method
```php
public static function fromModel(Model $model): static
{
    return new self(
        // Extract only what the Entity needs
        status: AccountStatus::tryFrom($model->latestStatus()?->name ?? ''),
        isLocked: $model->locked_at !== null,
    );
}
```

### 5. Methods Return Business Answers
```php
// ✅ Good
public function canLogin(): bool { ... }
public function isTerminal(): bool { ... }
public function requiresAction(): bool { ... }
public function canTransitionTo(self $target): bool { ... }

// ❌ Bad — just returns raw data
public function status(): string { ... }
public function isLocked(): bool { ... }  // Simple getter is fine
```
