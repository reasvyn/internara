# Reusable Components

Extract repeated UI patterns into shared Livewire components or Blade components.

## Candidates for Extraction

### 1. Record Manager (Table + Search + Pagination)

If multiple components share the same pattern (table with search, filter, pagination, record selection), extract to a base class:

```php
// app/Livewire/Core/BaseRecordManager.php
abstract class BaseRecordManager extends Component
{
    use WithPagination;

    public string $search = '';
    public array $selected = [];
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    abstract protected function query(): Builder;
    abstract protected function headers(): array;

    public function render()
    {
        return view('livewire.core.base-record-manager', [
            'records' => $this->query()
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate(10),
        ]);
    }
}
```

### 2. CRUD Modal Pattern

If multiple pages need create/edit modals, extract a shared `Modal` trait or base class:

```php
trait HasCrudModal
{
    public bool $showModal = false;
    public array $formData = [];
    public ?string $editingId = null;

    public function resetForm(): void { ... }
    public function openCreate(): void { ... }
    public function openEdit(string $id): void { ... }
    public function closeModal(): void { ... }
}
```

### 3. Notification / Toast Pattern

Already handled by `Mary\Traits\Toast` and `flash()` helper. Use consistently.

### 4. Search + Filter Input Pattern

If the same search/filter UI appears repeatedly, extract a Blade component:

```blade
{{-- resources/views/components/ui/search-input.blade.php --}}
@props(['placeholder' => 'Search...'])
<div class="relative">
    <x-mary-input
        wire:model.live.debounce.300ms="search"
        placeholder="{{ $placeholder }}"
        clearable
    />
</div>
```

## When to Extract

Extract when you see the same pattern **3+ times**:

| Pattern | Extraction Point |
|---|---|
| Table with search + pagination | `BaseRecordManager` (already exists) |
| Create/Edit modal | `HasCrudModal` trait |
| File upload with hover overlay | `ui::avatar-upload` Blade component |
| Color picker with presets | Keep inline (unique to settings) |
| Date range picker | `ui::date-range` Blade component |
| Confirmation dialog | Use maryUI's `wire:confirm` |
| Bulk action bar | `BaseRecordManager` (already exists) |
