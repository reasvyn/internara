# Action Pattern

All business logic lives in Action classes with a single `execute()` method.

## Location

```
app/Actions/{Domain}/{Verb}{Noun}Action.php
```

Examples: `app/Actions/User/CreateUserAction.php`, `app/Actions/School/ActivateAcademicYearAction.php`

## Structure

```php
<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Actions\Core\LogAuditAction;
use App\Models\User;
use App\Support\User\HandlesActionErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class CreateUserAction
{
    use HandlesActionErrors;

    public function __construct(
        protected readonly LogAuditAction $logAuditAction,
    ) {}

    public function execute(array $data): User
    {
        $validated = Validator::validate($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);

        return DB::transaction(function () use ($validated) {
            $user = User::create($validated);
            $this->logAuditAction->execute(
                action: 'user_created',
                subjectType: User::class,
                subjectId: $user->id,
                module: 'User',
            );
            event(new UserCreated($user));
            return $user;
        });
    }
}
```

## Rules

- One Action = one business operation
- Constructor: `protected readonly` dependency injection
- `execute()`: validation → transaction → side effects → return
- Validation via `Validator::validate()` (authoritative source)
- DB mutations wrapped in `DB::transaction()`
- Side effects (audit, events) inside the transaction
- `HandlesActionErrors` trait for consistent try-catch

## Throwing Errors

- Validation errors: `Validator::validate()` handles automatically
- Business rule violations: `throw new RuntimeException('message')`
- The Livewire component catches and displays via `flash()->error()`
