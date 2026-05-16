# Reusable Components

Extract repeated UI patterns into shared Livewire components or Blade components.

## Candidates for Extraction

### 1. Record Manager (Table + Search + Pagination)

```php
// app/Livewire/Core/BaseRecordManager.php
abstract class BaseRecordManager extends Component
{
    use WithPagination, WithRecordSelection, WithSorting;

    public string $search = '';
    public array $filters = [];

    abstract protected function query(): Builder;
    abstract public function headers(): array;

    public function rows(): LengthAwarePaginator
    {
        $query = $this->query();
        if ($this->search) $query = $this->applySearch($query);
        if ($this->filters) $query = $this->applyFilters($query);
        return $query->paginate($this->perPage());
    }

    public function resetFilters(): void
    {
        $this->filters = [];
        $this->resetPage();
    }
}
```

Features provided: search, filters with reset, sorting, pagination, record selection, bulk actions, stats cards, extra menu, modal slot.

### 2. Confirm Dialog

`resources/views/components/ui/confirm.blade.php` — reusable confirmation modal using maryUI:

```blade
@props([
    'title' => __('common.actions.confirm_action'),
    'message' => '',
    'icon' => 'o-exclamation-triangle',
    'confirmText' => __('common.actions.confirm'),
    'cancelText' => __('common.actions.cancel'),
    'confirmClass' => 'btn-error',
])

<x-mary-modal wire:model="showConfirm" :title="$title" class="backdrop-blur-sm">
    <div class="flex items-start gap-4">
        <x-mary-icon :name="$icon" class="size-6 text-warning shrink-0 mt-0.5" />
        <p class="text-sm text-base-content/80">{{ $message }}</p>
    </div>
    <x-slot:actions>
        <x-mary-button :label="$cancelText" wire:click="$set('showConfirm', false)" class="btn-ghost btn-sm" />
        <x-mary-button :label="$confirmText" wire:click="confirmAction" :class="'btn-sm ' . $confirmClass" spinner="confirmAction" />
    </x-slot:actions>
</x-mary-modal>
```

Usage — component must have:
- `$showConfirm`, `$confirmMessage`, `$confirmType`, `$confirmTarget` properties
- `ask{Action}()` method to show the dialog
- `confirmAction()` method to execute the confirmed action

```blade
<x-ui::confirm
    wire:model="showConfirm"
    :message="$confirmMessage"
    confirmText="{{ __('common.actions.confirm') }}"
    cancelText="{{ __('common.actions.cancel') }}"
    :confirmClass="$confirmType === 'activate' ? 'btn-primary' : 'btn-error'"
/>
```

### 3. Selection Bar

`resources/views/components/ui/selection-bar.blade.php` — shows when records are selected:

```blade
<div x-data="{}" class="..." x-show="$wire.selectedIds.length > 0" x-cloak>
    <div class="flex items-center gap-3">
        {{ $slot }}
        <x-mary-button :label="__('common.actions.cancel')" wire:click="clearSelection" class="btn-sm btn-ghost" />
    </div>
    <p class="text-sm">
        <span x-text="$wire.selectedIds.length"></span>
        <span>{{ __('common.actions.x_selected') }}</span>
    </p>
</div>
```

### 4. CsvHandler (Support)

`app/Support/CsvHandler.php` — base class for all CSV operations:

```php
final class CsvHandler
{
    public function export(Collection $items, array $headers, callable $rowMapper, string $filename): StreamedResponse;
    public function downloadTemplate(array $headers, array $exampleRow, string $filename): StreamedResponse;
    public function import(string $filePath, callable $rowProcessor): array;
}
```

- `export()`: accepts headers + callback to map each item to a row array
- `downloadTemplate()`: accepts headers + example row
- `import()`: callback returns `null` (skip empty), `'skipped'` (duplicate), or `'created'` (inserted)

### 5. LangChecker (Support)

`app/Support/LangChecker.php` — extends Laravel `Translator`, warns on missing translation keys (debug mode only):

```php
final class LangChecker extends Translator
{
    public function get($key, ...): string|array
    {
        $result = parent::get($key, ...);
        if (is_string($result) && $result === $key) {
            Log::warning("Missing translation key: {$key}", [
                'called_in' => $caller['file'] ?? 'unknown',
            ]);
        }
        return $result;
    }
}
```

Registered in `AppServiceProvider::register()`:

```php
if ($this->app->hasDebugModeEnabled()) {
    $this->app->extend('translator', fn ($t) => tap(
        new LangChecker($t->getLoader(), $t->getLocale()),
        fn ($c) => $c->setFallback($t->getFallback()),
    ));
}
```

### 6. Record Selection Concern

`app/Livewire/Concerns/WithRecordSelection.php`:

```php
trait WithRecordSelection
{
    public array $selectedIds = [];

    public function clearSelection(): void { $this->selectedIds = []; }
    public function toggleSelectAll(): void { /* select/deselect current page */ }
}
```

### 7. Sorting Concern

`app/Livewire/Concerns/WithSorting.php`:

```php
trait WithSorting
{
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    protected function applySorting(Builder $query): Builder
    {
        return $query->orderBy($this->sortBy['column'], $this->sortBy['direction']);
    }
}
```

### 8. Exception Hierarchy

```
RuntimeException (SPL)
├── DomainException (abstract)
│   └── RejectedException (concrete — throw this in Actions)
└── AppException (abstract)
    ├── ActionException (abstract)
    ├── InfrastructureException (abstract)
    └── PresentationException (abstract)
```

- Actions throw `RejectedException` for business rule violations
- Component catches `RejectedException` → converts to flash message
- Infrastructure errors throw `InfrastructureException` (not user-facing)
- Never throw bare `RuntimeException` directly

## When to Extract

Extract when you see the same pattern **3+ times**:

| Pattern | Extraction Point |
|---|---|
| Table with search + pagination | `BaseRecordManager` (already exists) |
| Create/Edit modal | `BaseRecordManager` modal slot |
| File upload with hover overlay | `ui::avatar-upload` Blade component |
| Color picker with presets | Keep inline (unique to settings) |
| Confirmation dialog | `x-ui::confirm` component |
| Bulk action bar | `x-ui::selection-bar` component |
| CSV import/export | `CsvHandler` support class |
| Missing translation detection | `LangChecker` (debug mode) |
| Record selection logic | `WithRecordSelection` concern (already exists) |
| Sorting logic | `WithSorting` concern (already exists) |
