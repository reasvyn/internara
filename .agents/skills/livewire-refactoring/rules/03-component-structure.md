# Component Structure

Livewire components only handle UI state, form binding, and delegation to Actions.

## Allowed in Components

### ✅ UI State
```php
public string $search = '';
public bool $showModal = false;
public array $formData = [];
public ?string $selectedPreset = null;
```

### ✅ Form Binding + Validation
```php
public function save(CreateUserAction $action): void
{
    $this->validate();  // Livewire inline validation for UX
    $action->execute($this->formData);
    flash()->success(__('user.created'));
}
```

### ✅ Query for Render (read-only, no mutations)
```php
public function render()
{
    return view('livewire.user.user-manager', [
        'users' => User::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->paginate(10),
    ]);
}
```

### ✅ File Uploads (Livewire-specific)
```php
use Livewire\WithFileUploads;

class ProfileEditor extends Component
{
    use WithFileUploads;

    public $avatar;

    public function save(UpdateProfileAction $action): void
    {
        $this->validate();
        $action->execute($this->user, $this->data, avatar: $this->avatar);
        flash()->success(__('profile.saved'));
    }
}
```

### ✅ Notifications
Use PHPFlasher (`flash()`) for all user-facing messages. Never use maryUI Toast (`$this->success()`, `$this->error()`, etc.).

```php
// ✅ CORRECT: PHPFlasher
flash()->success(__('user.created'));
flash()->error(__('user.delete_blocked'));
flash()->warning(__('No records selected.'));
flash()->info(__('Password reset'));

// ❌ WRONG: maryUI Toast
$this->success(__('user.created'));    // NO
$this->error('Failed');                 // NO
```

## NOT Allowed in Components

### ❌ Inline DB mutations
```php
// WRONG: should be in Action
DB::transaction(function () { ... });
User::create([...]);
$user->update([...]);
```

### ❌ Inline business rules
```php
// WRONG: should be in Entity
if ($user->is_active && !$user->locked_at) { ... }
if ($year->is_active) throw ...;
```

### ❌ Logic with side effects outside Actions
```php
// WRONG: audit logging should be in Action
Log::info(...);
event(new UserCreated(...));
```

### ❌ Static helper methods
```php
// WRONG: should be in Support class
public function formatSomething(...) { ... }
public static function helperMethod(...) { ... }
```

## Authorization

Use `boot()` for role checks:

```php
public function boot(): void
{
    abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin']), 403);
}
```

Use Gate for granular permission checks:

```php
public function save(..., CreateUserAction $action): void
{
    Gate::authorize('create', User::class);
    $action->execute($this->formData);
}
```
