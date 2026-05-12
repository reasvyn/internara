# Action Pattern

Every business operation lives in an Action class with a single `execute()` method.

## Structure

```php
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
            $this->logAuditAction->execute(...);
            event(new UserCreated($user));
            return $user;
        });
    }
}
```

## Rules

### 1. Single Responsibility
- One Action = one business operation
- Named `{Verb}{Noun}Action` (e.g., `CreateUserAction`, `UpdateProfileAction`)
- Single `execute()` method

### 2. Validation Lives Here
- All `Validator::make()` / `Validator::validate()` calls belong in the Action
- The component may repeat rules for Livewire inline validation (UX), but the Action is the authoritative source
- Throw `RuntimeException` on validation failure

### 3. Transactions + Side Effects
- Wrap DB mutations in `DB::transaction()`
- Audit logging is a side effect of the Action
- Domain events are dispatched by the Action

### 4. Error Handling
- Use `HandlesActionErrors` trait for consistent try-catch-log-rethrow
- Business rule violations throw `RuntimeException` (caught by component → shown as flash)

### 5. Location
- `app/Actions/{Domain}/{Verb}{Noun}Action.php`
- Injected into Livewire component via constructor or method injection

## What NOT to do

```php
// ❌ WRONG: DB query + logic in Livewire component
class UserManager extends Component
{
    public function delete(User $user): void
    {
        if ($user->is_active) {
            throw new \Exception('Cannot delete active user');
        }
        DB::transaction(function () use ($user) {
            $user->delete();
            Log::info('User deleted');
        });
    }
}
```

```php
// ✅ CORRECT: Delegated to Action
class UserManager extends Component
{
    public function delete(User $user, DeleteUserAction $action): void
    {
        $action->execute($user);
        flash()->success(__('user.deleted'));
    }
}
```
