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
- Throw `RejectedException` on validation failure

### 3. Transactions + Side Effects
- Wrap DB mutations in `DB::transaction()`
- Audit logging is a side effect of the Action
- Domain events are dispatched by the Action

### 4. Error Handling
- Business rule violations throw `RejectedException` (extends `DomainException` → `RuntimeException`)
- Component catches `RejectedException` → shown as flash message
- Never throw bare `RuntimeException` from Actions — always use `RejectedException`
- Use `HandlesActionErrors` trait for wrapping non-business errors (infrastructure failures)

```php
class DeleteAcademicYearAction
{
    public function execute(AcademicYear $year): void
    {
        $state = $year->asAcademicYearState();

        if (! $state->canBeDeleted()) {
            throw new RejectedException(
                $state->isActive()
                    ? __('academic_year.cannot_delete_active', ['name' => $year->name])
                    : __('academic_year.cannot_delete_has_data', ['name' => $year->name])
            );
        }

        DB::transaction(function () use ($year) { ... });
    }
}
```

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
// ❌ WRONG: Bare RuntimeException instead of RejectedException
throw new RuntimeException('Cannot delete active academic year.');

// ✅ CORRECT: Use RejectedException
throw new RejectedException('Cannot delete active academic year.');
```

```php
// ✅ CORRECT: Delegated to Action
class UserManager extends Component
{
    public function delete(User $user, DeleteUserAction $action): void
    {
        try {
            $action->execute($user);
            flash()->success(__('user.deleted'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }
    }
}
```
