# Livewire Component Patterns — Thin Components, Injection & Forms

> **Last updated:** 2026-06-13
> **Changes:** sync — update confirmation dialog pattern to reference shared <x-core::ui.confirm> component
## Description

Thin component rule, auto-discovery, CRUD tables via BaseRecordManager, Action injection, Form Objects, and common pitfalls.

## 1. Thin Component Rule

Livewire components handle **only** UI state and delegation. Business logic, business rules, and
side effects belong in lower layers.

### Allowed in Components

- **UI state:** public properties for form bindings, modal visibility, search input, selection state
- **UX validation:** `$this->validate()` for inline feedback (the Action re-validates authoritatively)
- **Delegation:** calling Actions via method injection
- **Read-only queries:** searchable, paginated, filtered queries in `render()` — these are presentation logic
- **Authorization:** role or Gate checks in `boot()`
- **Flash messages:** `flash()->success()` / `flash()->error()` via PHPFlasher

### NOT Allowed

- Inline DB mutations (`Model::create()`, `DB::transaction()`, `Model::update()`)
- Inline business rules for WRITE decisions (`if ($model->status === 'x')` before calling Action)
- **Raw array (3+ keys) passed to Actions** — build a DTO (`BaseData::from()`) from validated form data
- Side effects (`Log::info()`, `event(new ...)`, `Notification::send()`)
- Static helper methods (`public static function formatSomething()`)
- Bare `wire:confirm` for destructive actions (use the two-step pattern)
- maryUI Toast methods (`$this->success()`, `$this->error()`)

**Allowed (read-only UI decisions):** `$model->asEntity()->canX()` to conditionally show/hide UI
elements (e.g., disable a delete button when `! $entity->canBeDeleted()`). For WRITE decisions
(e.g., "can this be approved?"), the check must go through an Action.

### Why

Thin components are easy to audit (auth in `boot()`), easy to test (logic is in injectable Actions),
and easy to understand (public properties describe the complete UI state). The component becomes a
thin coordination layer between the browser and the module.

---

## 2. Component Directory Structure

Components follow the same two-tier path convention as all code. The view directory **must exactly
mirror** the `app/` module structure.

### Submodule-Specific Components

```
app/{Module}/{SubModule}/Livewire/{Name}.php
resources/views/{module}/{submodule}/{component-name}.blade.php
tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php
```

### Cross-Submodule Components (within a module)

```
app/{Module}/Livewire/{Name}.php
resources/views/{module}/{component-name}.blade.php
```

### View Name Resolution Rules

1. Submodule components: `view('{module}.{submodule}.{component-name}')` — maps to `resources/views/{module}/{submodule}/{component-name}.blade.php`.
2. Module-root components: `view('{module}.{component-name}')` — maps to `resources/views/{module}/{component-name}.blade.php`.
3. Avoid redundant nesting: when the component name matches the submodule name, flatten to `{module}.{submodule}`.
4. The `view()` call must match the actual file location. Any mismatch is a bug.

### Shared Cross-Module Components

```
app/Core/Livewire/{Name}.php
resources/views/livewire/{component-name}.blade.php
```

### Form Objects

```
app/{Module}/Livewire/Forms/{Name}Form.php
```

---

## 3. Auto-Discovery & Alias Conventions

Components are auto-discovered by `AppServiceProvider::discoverLivewireComponents()`. The method
scans all PHP files under `app/` in any `Livewire/` directory (excluding `Concerns/` and `Traits/`),
checks they subclass `Livewire\Component`, and registers them with a kebab-case alias.

### Alias Patterns

| Scope | Pattern | Example |
|-------|---------|---------|
| Submodule | `{kebab-module}.{kebab-submodule}.{kebab-name}` | `{module}.{submodule}.{name}` |
| Cross-submodule | `{kebab-module}.{kebab-name}` | `{module}.{name}` |
| Shared | `{kebab-component-name}` | `{component-name}` |

### How the Alias Is Computed

```
$module = $parts[0];
$submodule = $parts[1] !== $directory ? $parts[1] ?? '' : '';
$alias = $submodule
    ? Str::kebab($module).'.'.Str::kebab($submodule).'.'.Str::kebab($className)
    : Str::kebab($module).'.'.Str::kebab($className);
```

### Caching

The discovered component map is cached. Clear the cache after adding a new component:

```bash
php artisan cache:forget {cache-key}
```

---

## 4. BaseRecordManager Pattern (CRUD Tables)

All CRUD table components extend `BaseRecordManager`. This base class provides search, filter,
sorting, pagination, selection, and bulk/mass actions out of the box.

### Abstract Contract

Subclasses must implement two methods:

```php
abstract public function headers(): array;
abstract protected function query(): Builder;
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

---

## 5. Action Injection via Method Parameters

Actions are injected as method parameters — never resolved manually with `app()` or `new` inside
the component body. Laravel's container resolves the Action from the method signature.

### Create / Update Pattern

```php
public function save(Create{Entity}Action $createAction, Update{Entity}Action $updateAction): void
{
    $this->form->validate();

    // Build DTO from validated form data — crosses the UI→Business boundary
    $dto = {Entity}Data::from($this->form->toArray());

    if ($this->form->id) {
        $entity = {Entity}::findOrFail($this->form->id);
        $result = $updateAction->execute($entity, $dto);
        flash()->success($result->message);
    } else {
        $result = $createAction->execute($dto);
        flash()->success($result->message);
    }

    $this->modal = false;
}
```

### Delete with Confirmation

```php
public function delete{Entity}(string $id, Delete{Entity}Action $deleteAction): void
{
    $entity = {Entity}::findOrFail($id);

    try {
        $deleteAction->execute($entity);
        flash()->success(__('{module}.{entity}.success_deleted'));
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }
}
```

### Bulk Action Pattern

```php
public function {action}Selected(Set{Entity}StatusAction $action): void
{
    $this->performBulkAction(__('common.actions.{action}'), function (string $id) use ($action): void {
        $entity = {Entity}::findOrFail($id);
        $action->execute($entity, ...);
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
class {Entity}Form extends Form
{
    public string $name = '';
    public string $start_date = '';

    public function rules(?string $excludeId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.required'),
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'start_date' => $this->start_date,
        ];
    }
}
```

### Rules

- Form Objects extend `Livewire\Form`, never `BaseAction`
- Naming: `{Entity}Form` — `{Entity}Form` for the corresponding entity
- All form state, validation rules, and `toArray()` logic live inside the Form Object
- The component calls `$this->form->validate()` before dispatching to an Action
- Form Objects must NOT call Actions directly — they only prepare data

---

## 7. Confirmation Dialog Pattern

Destructive operations use a two-step confirmation dialog. Never use bare `wire:confirm` for
destructive actions — it does not provide user feedback on failure.

### Shared Component

A reusable confirmation modal is available at `resources/views/core/ui/confirm.blade.php`, namespaced
as `<x-core::ui.confirm />`. It accepts these props:

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | `string` | `__('common.actions.confirm_action')` | Modal heading |
| `message` | `string` | `''` | Body text explaining what will happen |
| `icon` | `string` | `o-exclamation-triangle` | maryUI icon name |
| `confirmText` | `string` | `__('common.actions.confirm')` | Confirm button label |
| `cancelText` | `string` | `__('common.actions.cancel')` | Cancel button label |
| `confirmClass` | `string` | `btn-error` | Tailwind class for the confirm button |

The component binds to `$showConfirm` via `wire:model` and calls `confirmAction` on confirmation.

### State

```php
public bool $showConfirm = false;
public ?string $actionTarget = null;
```

### Methods

```php
public function askDelete(string $id): void
{
    $this->actionTarget = $id;
    $this->showConfirm = true;
}

public function confirmDelete(Delete{Entity}Action $deleteAction): void
{
    try {
        $deleteAction->execute($this->actionTarget);
        flash()->success(__('{module}.{entity}.deleted'));
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }

    $this->showConfirm = false;
    $this->actionTarget = null;
}
```

### Blade

```blade
<x-mary-button label="{{ __('common.delete') }}" wire:click="askDelete('{{ $row->id }}')" class="btn-error btn-sm" />

<x-core::ui.confirm
    :title="__('{module}.confirm_delete_title')"
    :message="__('{module}.confirm_delete_message')"
    confirmText="{{ __('common.delete') }}"
/>
```

### Rules

- Always use `ask{Action}()` → `confirm{Action}()` for destructive operations
- Always catch `RejectedException` and display the error via flash
- Always reset both `$showConfirm` and `$actionTarget` in the confirm method
- Use the shared `<x-core::ui.confirm />` component instead of defining per-component modals

---

## 8. Flash Message Pattern

All user-facing feedback uses PHPFlasher via the `flash()` helper. maryUI Toast methods
(`$this->success()`, `$this->error()`) must NOT be used.

### Success

```php
flash()->success(__('{module}.{entity}.{action}_success'));
```

### Error

```php
flash()->error($e->getMessage());
```

### Warning

```php
flash()->warning(__('{module}.{context}.{warning_reason}'));
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

Both concerns are applied automatically in `BaseRecordManager`:

```php
abstract class BaseRecordManager extends Component
{
    use WithPagination, WithRecordSelection, WithSorting;
}
```

### WithRecordSelection

Provides `$selectedIds` array and methods `clearSelection()`, `selectAll(array $ids)`, and a
`selected_count` computed property. Used automatically by `BaseRecordManager`. Call
`clearSelection()` after any bulk/mass action.

### WithSorting

Provides `$sortBy` state (`['column' => 'id', 'direction' => 'asc']`), `$sortableColumns`
whitelist, and `applySorting(Builder)` logic. Used automatically by `BaseRecordManager`.
Override `$sortableColumns` in the subclass or configure per-column in the header array with
`'sortable' => true`.

---

## 10. Component Testing

Test files mirror the component structure:

```
tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php
```

### Key Practices

- Use `LazilyRefreshDatabase` (not `RefreshDatabase`)
- Use `assertModelExists()` over `assertDatabaseHas()`
- Test the UI state transitions (modal open/close, confirming state)
- Test the flash message dispatch on success/failure
- Test the Action is called with correct parameters (mock if needed)
- Do NOT test business logic in component tests — that belongs in Action tests

---

## 11. Guide Component Pattern (*-guide.blade.php)

Every page with a non-trivial workflow MUST include a `*-guide.blade.php` component that serves
as contextual help for the user. The pattern follows the setup wizard's guide component at
`resources/views/setup/components/setup-guide.blade.php`.

### Requirements

1. **Placement:** `resources/views/{module}/components/{page-name}-guide.blade.php`
2. **Trigger:** A fixed floating button (bottom-right, `z-50`) with a question mark icon
3. **Modal:** Uses `<x-mary-modal>` with step-by-step instructions for the current page
4. **Content:** Each guide must include:
   - An introductory sentence explaining the page's purpose
   - Numbered steps (1 through N) with a title and description per step
   - A tip section (warning icon) for best practices or common pitfalls
5. **Localization:** All strings use `__('{module}.guide.*')` translation keys

### Integration in the Parent Component

```blade
{{-- Toggle state --}}
public bool $showGuide = false;

{{-- In the Blade view --}}
@include('{module}.components.{page-name}-guide')
```

### Translation Keys

```
// resources/lang/{lang}/{module}.php
'guide' => [
    'title' => '...',
    'intro' => '...',
    'step1_title' => '...',
    'step1_desc' => '...',
    // ... through stepN
    'tip_title' => '...',
    'tip_desc' => '...',
],
```

### Pattern Reference

See `resources/views/setup/components/setup-guide.blade.php` for the canonical implementation.

---

## 12. Common Pitfalls (Concepts)

- **Inline DB calls** — extract to an Action and inject it
- **Bare `wire:confirm`** — use two-step `askAction()` / `confirmAction()` pattern instead
- **maryUI Toast** — use `flash()->success()` / `flash()->error()` instead
- **Forgetting `wire:key` in loops** — always add `wire:key` on the outermost element inside `@foreach`
- **Forgetting `updatedSearch` page reset** — `BaseRecordManager` already handles this
- **Over-relying on `#[Computed]`** — use for expensive/derived values only, not trivial getters
- **Business rules in components** — extract to Entity methods
- **Skipping `RejectedException` handling** — always wrap Action calls in `try`/`catch`
- **Manual resolution** — inject via method parameter, never `app()->make()`
