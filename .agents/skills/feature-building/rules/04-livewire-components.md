# Livewire Components

Livewire components handle UI state, form binding, and delegation to Actions.

## Location

```
app/Livewire/{Domain}/{Name}.php
resources/views/livewire/{domain}/{name}.blade.php
```

## Structure

```php
<?php

declare(strict_types=1);

namespace App\Livewire\User;

use App\Actions\User\CreateUserAction;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public array $formData = [];

    public function boot(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin']), 403);
    }

    public function save(CreateUserAction $action): void
    {
        $this->validate();
        $action->execute($this->formData);
        $this->showModal = false;
        flash()->success(__('user.created'));
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.user.user-manager', [
            'users' => User::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->paginate(10),
        ]);
    }
}
```

## Rules

| Aspect | Rule |
|---|---|
| **Business logic** | ❌ Delegate to Actions |
| **Business rules** | ❌ Delegate to Entities |
| **DB mutations** | ❌ Never directly in component |
| **Side effects** | ❌ Audit, events in Actions |
| **Auth** | `boot()` for role checks, Gate for granular |
| **State** | Typed public properties |
| **Layout** | `#[Layout('layouts::app')]` attribute |
| **File uploads** | `use Livewire\WithFileUploads` |
| **Flash messages** | `flash()->success()` (PHPFlasher) — never maryUI Toast |
| **Render queries** | Read-only, paginated, searchable |

## Reusable Patterns

| Pattern | Base |
|---|---|
| CRUD table | Extend `BaseRecordManager` |
| Create/Edit modal | `trait HasCrudModal` or inline state |
