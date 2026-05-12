# Side Effects

All side effects belong in the Action — never in the Livewire component.

## Types of Side Effects

| Side Effect | Tool | Example |
|---|---|---|
| Audit logging | `LogAuditAction` | `$logAudit->execute(action: 'user_created', ...)` |
| Domain events | `Event::dispatch()` | `event(new UserCreated($user))` |
| Notifications | `Notification::send()` | `$user->notify(new WelcomeNotification($password))` |
| Cache invalidation | `Cache::forget()` | `Cache::forget('settings.all')` |
| Flash messages | ❌ NOT here | Belongs in the Livewire component |

## Transaction Boundary

Wrap ALL mutations + side effects in a single `DB::transaction()`:

```php
public function execute(array $data): User
{
    $validated = Validator::validate($data, [...]);

    return DB::transaction(function () use ($validated) {
        $user = User::create($validated);

        // Side effects inside the transaction
        $this->logAudit->execute(
            action: 'user_created',
            subjectType: User::class,
            subjectId: $user->id,
            payload: ['email' => $user->email],
            module: 'User',
        );

        event(new UserCreated($user));

        return $user;
    });
}
```

## What NOT to do

```php
// ❌ Side effects in Livewire component
class UserManager extends Component
{
    public function save(CreateUserAction $action): void
    {
        $user = $action->execute($this->formData);
        Log::info('User created'); // Side effect in component!
        event(new UserCreated($user)); // Side effect in component!
    }
}
```

```php
// ✅ Side effects in Action
class CreateUserAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);
            $this->logAudit->execute(...);  // In Action
            event(new UserCreated($user));   // In Action
            return $user;
        });
    }
}
```

## Flash Messages

Flash messages (`flash()->success()`) belong in the Livewire component, not in the Action. The Action returns a result, the component decides what message to show.

```php
// ✅ CORRECT: Component decides the message
class UserManager extends Component
{
    public function save(CreateUserAction $action): void
    {
        $action->execute($this->formData);
        flash()->success(__('user.created'));
    }
}
```
