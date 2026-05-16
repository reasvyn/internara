---
name: livewire-refactoring
description: "Apply when refactoring Livewire components to follow the Action-Oriented MVC pattern. This skill ensures components stay thin, business logic moves to Actions, business rules move to Entities, static utilities move to Support, and reusable UI patterns are extracted into shared components."
license: MIT
metadata:
  author: internara
---

# Livewire Refactoring: Action-Oriented MVC

Architecture blueprint for keeping Livewire components thin and maintainable.

## Layer Responsibilities

```
┌─────────────────────────────────────────────────────┐
│                 Livewire Component                   │
│  UI state, form binding, confirm dialog dispatch,    │
│  delegating to Actions, CSV via CsvHandler           │
├─────────────────────────────────────────────────────┤
│                   Action Layer                        │
│  Business logic: validation, persistence,            │
│  side effects (audit, events, notifications)         │
│  RejectedException on business rule violations       │
├─────────────────────────────────────────────────────┤
│                   Entity Layer                        │
│  Business rules: state transitions, capability       │
│  checks — pure PHP, no framework dependencies        │
├─────────────────────────────────────────────────────┤
│                   Support Layer                       │
│  Static utilities: helpers, formatters,              │
│  reusable algorithms (CsvHandler, LangChecker)       │
└─────────────────────────────────────────────────────┘
```

## Rules

| # | Rule | File |
|---|------|------|
| 1 | [Action Pattern](rules/01-action-pattern.md) | Actions handle validation + persistence + side effects |
| 2 | [Entity Rules](rules/02-entity-rules.md) | Business rules in pure PHP entities, testable without DB |
| 3 | [Component Structure](rules/03-component-structure.md) | Livewire components only handle UI state and delegation |
| 4 | [Reusable Components](rules/04-reusable-components.md) | Extract repeated UI patterns into shared components |

## Quick Reference

### Livewire Component (allowed)

```php
class InternshipManager extends BaseRecordManager
{
    use WithFileUploads;

    public bool $showModal = false;
    public bool $showConfirm = false;
    public string $confirmMessage = '';
    public string $confirmType = '';
    public ?string $confirmTarget = null;
    public array $formData = [];
    public $importFile;

    public function askDelete(string $id): void
    {
        $year = Internship::findOrFail($id);
        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('internship.confirm_delete', ['name' => $internship->name]);
        $this->showConfirm = true;
    }

    public function confirmAction(
        DeleteInternshipAction $deleteAction,
        BatchUpdateInternshipStatusAction $batchAction,
    ): void {
        try {
            match ($this->confirmType) {
                'delete' => $this->executeDelete(...),
                'delete_selected' => $this->executeDeleteSelected(...),
                'close_filtered' => $this->executeCloseFiltered(...),
                default => null,
            };
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    public function import(CsvHandler $csv): void
    {
        $this->validate(['importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $result = $csv->import($this->importFile->getRealPath(), function (array $row) {
            // return null to skip, 'skipped' for duplicate, 'created' on success
        });

        flash()->success(__('internship.import_summary', [
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ]));
    }

    public function export(CsvHandler $csv)
    {
        return $csv->export($items, ['col1', 'col2'],
            fn ($i) => [$i->col1, $i->col2],
            'export.csv',
        )->send();
    }

    public function downloadTemplate(CsvHandler $csv)
    {
        return $csv->downloadTemplate(
            ['col1', 'col2'],
            [__('example.col1'), __('example.col2')],
            'template.csv',
        )->send();
    }

    public function save(CreateAction $create, UpdateAction $update): void
    {
        $this->validate([...]);
        try {
            $update->execute($internship, $this->formData);
            flash()->success(__('saved'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.manager', [
            'records' => $this->query()->paginate(10),
        ]);
    }
}
```

### Action (business logic)

```php
class DeleteInternshipAction
{
    public function execute(Internship $internship): void
    {
        if (! $internship->asInternshipState()->canBeDeleted()) {
            throw new RejectedException('Cannot delete internship with active data.');
        }

        DB::transaction(function () use ($internship) {
            $internship->delete();
            $this->logAudit->execute(...);
        });
    }
}
```

### Entity (business rules)

```php
final readonly class InternshipState extends BaseEntity
{
    public function __construct(
        private bool $hasActivePlacements,
        private bool $hasRegistrations,
    ) {}

    public function canBeDeleted(): bool
    {
        return ! $this->hasActivePlacements && ! $this->hasRegistrations;
    }
}
```

### Blade: Confirm Dialog

```blade
<x-ui::confirm
    wire:model="showConfirm"
    :message="$confirmMessage"
    confirmText="{{ __('common.actions.confirm') }}"
    cancelText="{{ __('common.actions.cancel') }}"
    :confirmClass="$confirmType === 'activate' ? 'btn-primary' : 'btn-error'"
/>
```

## Verification Checklist

- [ ] Component has no inline `DB::` or `Model::create/update/delete`
- [ ] Component has no inline `Validator::make()` — rules in Action or FormRequest
- [ ] Component has no inline business rule checks — moved to Entity
- [ ] Static helpers/extraction logic moved to Support (e.g. `CsvHandler`)
- [ ] Repeated modal/table/list patterns extracted to shared component
- [ ] All Actions have a single `execute()` method
- [ ] Actions throw `RejectedException` for business rule violations, not bare `RuntimeException`
- [ ] Component catches `RejectedException` → shown as flash, not uncaught exception
- [ ] Confirmation dialogs use `askAction()` + `confirmAction()` pattern, not `wire:confirm`
- [ ] All Entities are `final readonly` and extend `BaseEntity`
- [ ] Entities have zero framework imports (no Eloquent, no Facades)
- [ ] Translation keys use domain-key convention, never hardcoded strings
- [ ] Missing translation keys are detected via `LangChecker` in debug mode
