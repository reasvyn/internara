# Entity Rules

Business rules live in pure PHP Entity classes — no Eloquent, no Facades.

## Location

```
app/Entities/{Domain}/{Name}.php
```

Example: `app/Entities/User/Apprentice.php`, `app/Entities/AcademicYear/AcademicYearState.php`

## Structure

```php
<?php

declare(strict_types=1);

namespace App\Entities\User;

use App\Entities\BaseEntity;
use App\Enums\Auth\AccountStatus;
use Illuminate\Database\Eloquent\Model;

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

- `final readonly` class extending `BaseEntity`
- No `use Illuminate\*` imports (except `BaseEntity` may use `Model`)
- Only bridge to ORM is `static fromModel(Model $model): static`
- Pure business logic methods only — no queries, no persistence
- Testable without a database
- Callers use `$model->as{EntityName}()->method()`

## Entity vs Enum

- **Enum**: Simple constants with light logic (`isTerminal()`, `canTransitionTo()`)
- **Entity**: Complex business rules involving multiple fields or cross-entity checks
