# Livewire Component Patterns

> **Last updated:** 2026-06-10
>
> **Audience:** Developers building or maintaining Livewire components in Internara.
>
> **Purpose:** This document is the authoritative reference for how Livewire components are
> structured, how they communicate with the backend, and the patterns they must follow. It covers
> the Thin Component Rule, auto-discovery, CRUD tables via `BaseRecordManager`, Action injection,
> Form Objects, confirmation dialogs, flash messages, concerns, testing, and common pitfalls.

---

## 1. Thin Component Rule

Livewire components handle **only** UI state and delegation. Business logic, business rules, and
side effects belong in lower layers.

### Allowed in Components

- **UI state:** public properties for form bindings, modal visibility, search input, selection
  state
- **UX validation:** `$this->validate()` for inline feedback (the Action re-validates
  authoritatively)
- **Delegation:** calling Actions via method injection
- **Read-only queries:** searchable, paginated, filtered queries in `render()` — these are
  presentation logic
- **Authorization:** role or Gate checks in `boot()`
- **Flash messages:** `flash()->success()` / `flash()->error()` via PHPFlasher

### NOT Allowed

- Inline DB mutations (`Model::create()`, `DB::transaction()`, `Model::update()`)
- Inline business rules (`if ($model->status === 'x')`, date comparisons)
- Side effects (`Log::info()`, `event(new ...)`, `Notification::send()`)
- Static helper methods (`public static function formatSomething()`)
- Bare `wire:confirm` for destructive actions (use the two-step pattern)
- maryUI Toast methods (`$this->success()`, `$this->error()`)

### Why

Thin components are easy to audit (auth in `boot()`), easy to test (logic is in injectable
Actions), and easy to understand (public properties describe the complete UI state). The component
becomes a thin coordination layer between the browser and the module.

---

## 2. Component Directory Structure

Components follow the same two-tier path convention as all code. The view directory **must exactly
mirror** the `app/` module structure (see `docs/architecture.md` §Views Structure).

### Submodule-Specific Components

```
app/{Module}/{SubModule}/Livewire/{Name}.php
resources/views/{module}/{submodule}/{component-name}.blade.php
tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php
```

**Example:** `app/Academics/AcademicYear/Livewire/AcademicYearManager.php` with view at
`resources/views/academics/academic-year/academic-year-manager.blade.php`.

### Cross-Submodule Components (within a module)

```
app/{Module}/Livewire/{Name}.php
resources/views/{module}/{component-name}.blade.php
```

**Example:** `app/Assessment/Livewire/AssessmentGrading.php` with view at
`resources/views/assessment/assessment-grading.blade.php`.

### View Name Resolution

The `render()` method must return a `view()` call with the full dot notation. The component's
namespace determines the view path:

| Component namespace | `view()` call | File path |
|---|---|---|
| `App\Auth\Login\Livewire\Login` | `view('auth.login')` | `resources/views/auth/login.blade.php` |
| `App\Academics\AcademicYear\Livewire\AcademicYearManager` | `view('academics.academic-year.academic-year-manager')` | `resources/views/academics/academic-year/academic-year-manager.blade.php` |
| `App\Assessment\Livewire\AssessmentGrading` | `view('assessment.assessment-grading')` | `resources/views/assessment/assessment-grading.blade.php` |
| `App\User\UserManagement\Livewire\UserManager` | `view('user.user-management.user-manager')` | `resources/views/user/user-management/user-manager.blade.php` |

**Rules:**
1. Submodule components: `view('{module}.{submodule}.{component-name}')` — maps to `resources/views/{module}/{submodule}/{component-name}.blade.php`.
2. Module-root components: `view('{module}.{component-name}')` — maps to `resources/views/{module}/{component-name}.blade.php`.
3. Avoid redundant nesting: when the component name matches the submodule name, flatten to `{module}.{submodule}` (e.g., `auth.login` not `auth.login.login`).
4. The `view()` call must match the actual file location. Any mismatch between the view reference and the file path is a bug.

### Shared Cross-Module Components

```
app/Livewire/{Name}.php
resources/views/livewire/{component-name}.blade.php
```

**Example:** `app/Settings/Livewire/ThemeSwitcher.php` with view at
`resources/views/settings/livewire/theme-switcher.blade.php`.

### Form Objects

```
app/{Module}/Livewire/Forms/{Name}Form.php
```

---

## 3. Auto-Discovery & Alias Conventions

Components are auto-discovered by `AppServiceProvider::discoverLivewireComponents()`. The method
scans all PHP files under `app/` in any `Livewire/` directory (excluding `Concerns/` and
`Traits/`), checks they subclass `Livewire\Component`, and registers them with a kebab-case alias.

### Alias Patterns

| Scope | Pattern | Example |
|-------|---------|---------|
| Submodule | `{kebab-module}.{kebab-submodule}.{kebab-name}` | `admin.user.user-manager` |
| Cross-submodule | `{kebab-module}.{kebab-name}` | `user.profile-editor` |
| Shared | `{kebab-component-name}` | `livewire.lang-switcher` |

### How the Alias Is Computed

```php
// Given: app/User/UserManagement/Livewire/UserManager.php
// parts = ['app', 'SysAdmin', 'UserManagement', 'Livewire', 'UserManager.php']
// module = 'SysAdmin', submodule = 'UserManagement', className = 'UserManager'
// alias = 'user.user-management.user-manager'

$module = $parts[0];
$submodule = $parts[1] !== $directory ? $parts[1] ?? '' : '';
$alias = $submodule
    ? Str::kebab($module).'.'.Str::kebab($submodule).'.'.Str::kebab($className)
    : Str::kebab($module).'.'.Str::kebab($className);
```

### Caching

The discovered component map is cached for 86,400 seconds (1 day) under
`config('cache-keys.module_livewire')`. Clear the cache after adding a new component:

```bash
php artisan cache:forget module_livewire
```

---

## 4. BaseRecordManager Pattern (CRUD Tables)

All CRUD table components extend `BaseRecordManager` (at
`app/Core/Livewire/BaseRecordManager.php`). This base class provides search, filter, sorting,
pagination, selection, and bulk/mass actions out of the box.

### Abstract Contract

Subclasses must implement two methods:

```php
abstract public function headers(): array;
abstract protected function query(): Builder;
```

### Example

```php
class StudentManager extends BaseRecordManager
{
    use AuthorizesRequests, WithFileUploads;

    public bool $userModal = false;

    public StudentForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('user.student.name'), 'sortable' => true],
            ['key' => 'username', 'label' => __('user.student.username')],
            ['key' => 'profile.department.name', 'label' => __('user.student.department')],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return User::query()
            ->role(RoleEnum::STUDENT->value)
            ->with(['profile.department']);
    }
}
```

### Built-in State & Methods

| Property / Method | Purpose |
|---|---|
| `$search` | Resets page on update via `updatedSearch()` |
| `$filters` | Arbitrary filter state; resets via `resetFilters()` |
| `$perPage` | Page size; options controlled by `perPageOptions()` (default: 10, 25, 50, 100) |
| `$selectedIds` | Selection state from `WithRecordSelection` |
| `rows()` | Returns `LengthAwarePaginator` with search/filter/sort/pagination applied |
| `performBulkAction(string, callable)` | Iterates selected IDs with optional transaction |
| `performMassAction(string, callable)` | Applies callback to entire filtered query |

### Override Points

```php
protected function perPageOptions(): array        // Custom page size options
protected function applySearch(Builder): Builder  // Custom search logic
protected function applyFilters(Builder): Builder // Custom filter logic
protected function applySorting(Builder): Builder // Custom sort logic (rare)
```

### Concrete Subclasses (10 total)

- `AcademicYearManager`, `DepartmentManager`
- `PlacementIndex`, `PlacementChangeManager`
- `CompanyManager`, `PartnershipManager`
- `AdminManager`, `SupervisorManager`, `StudentManager`, `UserManager`

---

## 5. Action Injection via Method Parameters

Actions are injected as method parameters — never resolved manually with `app()` or `new` inside
the component body. Laravel's container resolves the Action from the method signature.

### Create / Update Pattern

```php
public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
{
    $this->form->validate();

    if ($this->form->id) {
        $user = User::findOrFail($this->form->id);
        $updateAction->execute($user, [...data...]);
        flash()->success(__('user.manager.success_updated'));
    } else {
        $user = $createAction->execute([...data...]);
        $this->redirect(route('sysadmin.users.account-slip', $user));
        return;
    }

    $this->userModal = false;
}
```

### Delete with Confirmation

```php
public function deleteUser(string $id, DeleteUserAction $deleteAction): void
{
    $user = User::findOrFail($id);

    if ($user->hasRole('super_admin')) {
        flash()->error(__('user.manager.cannot_delete_super_admin'));
        return;
    }

    try {
        $deleteAction->execute($user);
        flash()->success(__('user.manager.success_deleted'));
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }
}
```

### Bulk Action Pattern

```php
public function lockSelected(SetUserStatusAction $setStatus): void
{
    $this->performBulkAction(__('common.actions.lock'), function (string $id) use ($setStatus): void {
        $user = User::findOrFail($id);
        $setStatus->execute($user, AccountStatus::SUSPENDED, 'Batch lock by administrator');
    });
}
```

### Rules

- Never use `app()->make()` or `new Action()` inside the component
- Catch `RejectedException` (not `RuntimeException`) from Action calls
- Use `try`/`catch` for operations that can fail, with user-facing flash messages
- The Action is the single entry point — no inline `Model::create()` in the component

---

## 6. Form Object Pattern

Forms with 5+ fields or conditional validation are extracted into Form Objects.

### Location

```
app/{Module}/Livewire/Forms/{Name}Form.php
```

Extends `Livewire\Form` (Laravel's built-in class, not a custom base).

### Structure

```php
class AcademicYearForm extends Form
{
    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';

    public function rules(?string $excludeId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50',
                'unique:academic_years,name,'.($excludeId ?? 'NULL')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => __('validation.unique'),
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => false,
        ];
    }
}
```

### Usage in Component

```php
class AcademicYearManager extends BaseRecordManager
{
    public AcademicYearForm $form;

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->userModal = true;
    }

    public function save(CreateAcademicYearAction $action): void
    {
        $this->form->validate();
        $action->execute($this->form->toArray());
        flash()->success(__('academics.academic_year.created'));
        $this->userModal = false;
    }
}
```

### Rules

- Form Objects extend `Livewire\Form`, never `BaseAction`
- Naming: `{Entity}Form` — `UserForm`, `InternshipForm`, `AcademicYearForm`
- All form state, validation rules, and `toArray()` logic live inside the Form Object
- The component calls `$this->form->validate()` before dispatching to an Action
- Form Objects must NOT call Actions directly — they only prepare data

---

## 7. Confirmation Dialog Pattern

Destructive operations use a two-step confirmation dialog. Never use bare `wire:confirm` for
destructive actions — it does not provide user feedback on failure.

### State

```php
public ?string $actionTarget = null;
public bool $confirmingAction = false;
```

### Methods

```php
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

### Blade

```blade
<x-ui::confirm
    wire:model="confirmingAction"
    title="{{ __('common.confirm_delete') }}"
    message="{{ __('common.confirm_delete_message') }}"
    confirmText="{{ __('common.delete') }}"
    cancelText="{{ __('common.cancel') }}"
    wire:click="confirmAction"
/>
```

### Rules

- Always use `askAction()` → `confirmAction()` for destructive operations
- Always catch `RejectedException` and display the error via flash
- Always reset both `$confirmingAction` and `$actionTarget` in the confirm method

---

## 8. Flash Message Pattern

All user-facing feedback uses [PHPFlasher](https://php-flasher.io/) via the `flash()` helper.
maryUI Toast methods (`$this->success()`, `$this->error()`) must NOT be used.

### Success

```php
flash()->success(__('user.manager.success_updated'));
```

### Error

```php
flash()->error($e->getMessage());
```

### Warning

```php
flash()->warning(__('common.actions.no_records_selected'));
```

### Bulk Action Success

```php
flash()->success(
    __('common.actions.bulk_action_done', [
        'count' => count($this->selectedIds),
        'action' => $name,
    ]),
);
```

### Rules

- All user-facing strings use `__()` — never hardcode display text
- Catch `RejectedException` from Actions and display the error message
- Use `flash()->warning()` for edge cases (no records selected, no matching records)
- Never use `$this->success()` or `$this->error()` (maryUI Toast)

---

## 9. Concerns (WithSorting, WithRecordSelection)

### WithRecordSelection

File: `app/Core/Livewire/Concerns/WithRecordSelection.php`

```php
trait WithRecordSelection
{
    public array $selectedIds = [];

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function selectAll(array $ids): void
    {
        $this->selectedIds = $ids;
    }

    #[Computed]
    public function selected_count(): int
    {
        return count($this->selectedIds);
    }
}
```

Used automatically by `BaseRecordManager`. Call `clearSelection()` after any bulk/mass action.

### WithSorting

File: `app/Core/Livewire/Concerns/WithSorting.php`

```php
trait WithSorting
{
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    /** @var string[] */
    protected array $sortableColumns = ['id', 'name', 'created_at', 'updated_at'];

    protected function applySorting(Builder $query): Builder
    {
        $column = $this->sortBy['column'] ?? 'id';
        if (! in_array($column, $this->sortableColumns, true)) {
            $column = 'id';
        }

        $direction = $this->sortBy['direction'] ?? 'asc';
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return $query->orderBy($column, $direction);
    }
}
```

Used automatically by `BaseRecordManager`. Override `$sortableColumns` in the subclass or
configure per-column in the header array with `'sortable' => true`.

Both concerns are applied automatically in `BaseRecordManager`:

```php
abstract class BaseRecordManager extends Component
{
    use WithPagination, WithRecordSelection, WithSorting;
    // ...
}
```

---

## 10. Component Testing

Test files mirror the component structure:

```
tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php
```

### Testing a BaseRecordManager Component

```php
it('renders the user manager with paginated results', function () {
    User::factory()->count(5)->create();

    Livewire::test(UserManager::class)
        ->assertSet('perPage', 10)
        ->assertCount('rows', 5)
        ->assertSee(users()->first()->name);
});
```

### Testing Action Injection

```php
it('deletes a user via the confirm dialog pattern', function () {
    $user = User::factory()->create();

    Livewire::test(UserManager::class)
        ->call('askAction', $user->id)
        ->assertSet('confirmingAction', true)
        ->call('confirmAction')
        ->assertSet('confirmingAction', false)
        ->assertSet('actionTarget', null);

    assertModelExists($user->fresh()->deleted_at);
});
```

### Testing Flash Messages

```php
it('shows error when deleting super admin', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::test(UserManager::class)
        ->call('deleteUser', $admin->id)
        ->assertDispatched('flash-message');
});
```

### Key Practices

- Use `LazilyRefreshDatabase` (not `RefreshDatabase`)
- Use `assertModelExists()` over `assertDatabaseHas()`
- Test the UI state transitions (modal open/close, confirming state)
- Test the flash message dispatch on success/failure
- Test the Action is called with correct parameters (mock if needed)
- Do NOT test business logic in component tests — that belongs in Action tests

---

## 11. Common Pitfalls

### Inline DB Calls

❌ **Wrong:** `Model::create([...])` inside a component method.

✅ **Right:** Extract to an Action and inject it: `public function save(CreateAction $a)`.

### Bare `wire:confirm`

❌ **Wrong:** `<button wire:click="delete" wire:confirm="Are you sure?">`.

✅ **Right:** Use the two-step `askAction()` / `confirmAction()` pattern with a shared
`<x-ui::confirm>` component, so failures display error messages.

### maryUI Toast

❌ **Wrong:** `$this->success(__('user.created'))`.

✅ **Right:** `flash()->success(__('user.created'))`.

### Forgetting `wire:key` in Loops

Always add `wire:key="..."` on the outermost element inside `@foreach` loops:

```blade
@foreach ($rows as $row)
    <tr wire:key="{{ $row->id }}">
@endforeach
```

### Forgetting `updatedSearch` Page Reset

If you modify search behavior, ensure the page resets. `BaseRecordManager` already handles this:

```php
public function updatedSearch(): void
{
    $this->resetPage();
}
```

### Over-Relying on `Computed`

Use `#[Computed]` for expensive or derived values that should be cached for the request. Do NOT
use it for trivial getters or for values that change mid-request.

### Business Rules in Components

❌ **Wrong:** `if ($user->hasRole('super_admin'))` repeated across components.

✅ **Right:** Extract to an Entity method: `$user->asUserEntity()->isProtected()`.

### Skipping `RejectedException` Handling

Always wrap Action calls in `try`/`catch`. A bare `$action->execute()` that throws an uncaught
exception will show an ugly error page instead of a user-friendly flash message.

### Manual Resolution

❌ **Wrong:** `$action = app()->make(CreateAction::class)`.

✅ **Right:** Inject via method parameter: `public function save(CreateAction $action)`.
