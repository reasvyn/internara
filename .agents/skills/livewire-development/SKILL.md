---
name: livewire-development
description: Apply this skill for any task involving Livewire — building new components, debugging reactivity, handling file uploads, implementing real-time validation, managing CRUD tables, or migrating existing components. Activates whenever you see wire: directives, Livewire classes, or Alpine.js integration in Blade templates.
---

# Livewire Development Skill

## When to Activate

Apply this skill for any task involving Livewire — building new components, debugging reactivity, handling file uploads, implementing real-time validation, managing CRUD tables, or migrating existing components.

## Thin Component Rule

Livewire components handle **only**:
- UI state: form bindings, modal visibility, search input, selection state
- UX validation: inline validation (the Action re-validates authoritatively)
- Delegation: calling Actions via method injection
- Flash messages: success/error feedback via `flash()` (PHPFlasher)

They must **NOT** contain:
- Business logic: no `Model::create()`, `DB::transaction()`, direct mutations
- Business rules: no `if ($status === 'x')` — delegate to Entities
- Side effects: no logging, event dispatching, notification sending — those belong in Actions

## Key References

- **BaseRecordManager**: `app/Core/Livewire/BaseRecordManager.php` — CRUD base class with pagination, search, sort, selection, bulk/mass actions
- **WithRecordSelection**: `app/Core/Livewire/Concerns/WithRecordSelection.php`
- **WithSorting**: `app/Core/Livewire/Concerns/WithSorting.php`
- **Architecture**: `docs/architecture.md#data-flow`
- **Livewire Pattern**: `docs/architecture/livewire-pattern.md`

## Component Location & Auto-Discovery

Components are auto-discovered by `AppServiceProvider` from `app/{Module}/Livewire/` and `app/Livewire/`.

| Scope | Directory | Alias Pattern | Example |
|-------|-----------|---------------|---------|
| Submodule | `app/{Module}/{SubModule}/Livewire/` | `{kebab-module}.{kebab-submodule}.{kebab-name}` | `admin.user.user-manager` |
| Cross-submodule | `app/{Module}/Livewire/` | `{kebab-module}.{kebab-name}` | `user.profile-editor` |
| Shared | `app/Livewire/` | `{kebab-component-name}` | `livewire.lang-switcher` |

Views mirror the app structure:

| Scope | View Path |
|-------|-----------|
| Submodule | `resources/views/{module}/{submodule}/{component-name}.blade.php` |
| Root | `resources/views/{module}/{component-name}.blade.php` |
| Shared | `resources/views/livewire/{component-name}.blade.php` |

## BaseRecordManager (CRUD Tables)

All CRUD table components extend `BaseRecordManager`. Implement two abstract methods:

```php
class UserManager extends BaseRecordManager
{
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('users.name')],
            ['key' => 'email', 'label' => __('users.email')],
            ['key' => 'status', 'label' => __('users.status')],
        ];
    }

    protected function query(): Builder
    {
        return User::query();
    }
}
```

The `rows()` method automatically applies search, filters, sorting, and pagination. Override `applySearch()` and `applyFilters()` for custom behavior.

### Built-in Features

- `$search` — resets page on update via `updatedSearch()`
- `$filters` — arbitrary filter state, resets via `resetFilters()`
- `$perPage` — options: 10, 25, 50, 100
- `$selectedIds` — selection state from `WithRecordSelection`
- `performBulkAction(string $name, callable $callback)` — iterates selected IDs
- `performMassAction(string $name, callable $callback)` — applies to entire filtered query

## Confirmation Dialog Pattern

Destructive operations use explicit two-step confirmation:

```php
public ?string $actionTarget = null;
public bool $confirmingAction = false;

public function askAction(string $id): void
{
    $this->actionTarget = $id;
    $this->confirmingAction = true;
}

public function confirmAction(CreateUserAction $action): void
{
    try {
        $action->execute($this->actionTarget);
        flash()->success(__('users.created'));
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }

    $this->confirmingAction = false;
    $this->actionTarget = null;
}
```

## Form Objects

Complex forms extract into `app/{Module}/Livewire/Forms/{Name}Form.php`:

```php
class AcademicYearForm extends Form
{
    public string $name = '';
    public string $startDate = '';
    public string $endDate = '';

    public function rules(): array { ... }

    public function toArray(): array { ... }
}
```

## Verification

- No inline `Model::create/update/delete`, `DB::`, `Mail::`, `Log::` calls?
- Actions injected via method parameter, not resolved manually?
- `RejectedException` caught and displayed as flash message?
- `wire:key` on all `@foreach` loops?
- `#[Computed]` attribute for computed properties?
- Bulk/mass operations use `performBulkAction` / `performMassAction`?
