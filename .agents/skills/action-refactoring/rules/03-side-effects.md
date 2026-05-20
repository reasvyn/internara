# Side Effects

All side effects belong in the Action — never in the Livewire component.

## Side Effect Types

| Side Effect | Tool | Method |
|---|---|---|
| Audit logging | `$this->log()` on `BaseAction` | `$this->log('user_created', $user, [...])` |
| Domain events | `Event::dispatch()` | `event(new UserCreated($user))` |
| Notifications | `Notification::send()` | `$user->notify(new WelcomeNotification(...))` |
| Cache invalidation | `Cache::forget()` | `Cache::forget('settings.all')` |
| Flash messages | ❌ NOT here | Belongs in the Livewire component |

## Transaction Boundary

Wrap ALL mutations and side effects in `$this->transaction()`:

```php
declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateUserAction extends BaseAction
{
    public function execute(array $data): User
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        return $this->transaction(function () use ($validated) {
            $user = User::create($validated);

            $this->log('user_created', $user, [
                'email' => $user->email,
                'roles' => $data['roles'] ?? [],
            ]);

            event(new UserCreated($user));

            return $user;
        });
    }
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

// ✅ Side effects inside Action's transaction
class CreateUserAction extends BaseAction
{
    public function execute(array $data): User
    {
        return $this->transaction(function () use ($data) {
            $user = User::create($data);
            $this->log('user_created', $user, ['email' => $user->email]); // ✅ In Action
            event(new UserCreated($user)); // ✅ In Action
            return $user;
        });
    }
}
```

## Flash Messages

Flash messages belong in the Livewire component, not in the Action. The Action returns a result; the component decides what message to show.

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
