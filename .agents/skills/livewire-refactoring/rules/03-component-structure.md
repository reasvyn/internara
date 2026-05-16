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

### ✅ Confirm Dialog State
```php
public bool $showConfirm = false;
public string $confirmMessage = '';
public string $confirmType = '';
public ?string $confirmTarget = null;
```

Use `ask{Action}()` to set confirm state, `confirmAction()` to execute:

```php
public function askDelete(string $id): void
{
    $record = Model::findOrFail($id);
    $this->confirmTarget = $id;
    $this->confirmType = 'delete';
    $this->confirmMessage = __('domain.confirm_delete', ['name' => $record->name]);
    $this->showConfirm = true;
}

public function confirmAction(DeleteAction $deleteAction): void
{
    if ($this->confirmTarget === null) return;

    try {
        match ($this->confirmType) {
            'delete' => $this->executeDelete($this->confirmTarget, $deleteAction),
            default => null,
        };
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }

    $this->showConfirm = false;
    $this->confirmTarget = null;
    $this->confirmType = '';
}
```

### ✅ Form Binding + Validation
```php
public function save(CreateAction $action): void
{
    $this->validate();
    try {
        $action->execute($this->formData);
        flash()->success(__('created'));
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }
}
```

### ✅ Query for Render (read-only, no mutations)
```php
public function render()
{
    return view('livewire.manager', [
        'records' => Model::query()
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

### ✅ CSV Import/Export via CsvHandler
```php
use App\Support\CsvHandler;

public function import(CsvHandler $csv): void
{
    $this->validate(['importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

    $result = $csv->import($this->importFile->getRealPath(), function (array $row) {
        // null = skip row, 'skipped' = count as duplicate, 'created' = inserted
    });

    flash()->success(__('domain.import_summary', ['created' => $result['created'], 'skipped' => $result['skipped']]));
}

public function export(CsvHandler $csv)
{
    return $csv->export($items, ['col'], fn ($i) => [$i->col], 'export.csv')->send();
}

public function downloadTemplate(CsvHandler $csv)
{
    return $csv->downloadTemplate(['col'], [__('example')], 'template.csv')->send();
}
```

### ✅ Notifications
Use PHPFlasher (`flash()`) for all user-facing messages. Never use maryUI Toast (`$this->success()`, `$this->error()`, etc.).

```php
// ✅ CORRECT: PHPFlasher
flash()->success(__('user.created'));
flash()->error(__('user.delete_blocked'));
flash()->warning(__('common.actions.no_records_selected'));
flash()->info(__('Password reset'));

// ❌ WRONG: maryUI Toast
$this->success(__('user.created'));    // NO
$this->error('Failed');                 // NO
```

### ✅ Authorization
```php
public function boot(): void
{
    abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin']), 403);
}
```

Use Gate for granular permission checks:
```php
public function save(..., CreateAction $action): void
{
    Gate::authorize('create', Model::class);
    $action->execute($this->formData);
}
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

### ❌ Inline CSV parsing/streaming
```php
// WRONG: should use CsvHandler
$handle = fopen(...);
fputcsv(...);
fclose(...);
return response()->streamDownload(...);

// ✅ CORRECT: delegate to CsvHandler
return $csv->export($items, $headers, $rowMapper)->send();
```

### ❌ Bare `wire:confirm` for destructive actions
```php
// WRONG: browser native confirm popup
wire:confirm="{{ __('domain.confirm_delete') }}"

// ✅ CORRECT: use confirm dialog component
wire:click="askDelete('{{ $id }}')"
```
Then in Blade:
```blade
<x-ui::confirm
    wire:model="showConfirm"
    :message="$confirmMessage"
    confirmText="{{ __('common.actions.confirm') }}"
    cancelText="{{ __('common.actions.cancel') }}"
    :confirmClass="$confirmType === 'activate' ? 'btn-primary' : 'btn-error'"
/>
```
