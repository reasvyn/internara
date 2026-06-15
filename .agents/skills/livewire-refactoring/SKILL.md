---
name: livewire-refactoring
description: Apply this skill when refactoring Livewire components that have grown too large, when extracting business logic from components into proper layers, or when enforcing the Action-Oriented MVC pattern on existing code. Activates when you see inline DB calls, business rule conditionals, static helpers, or repeated UI patterns inside Livewire components.
---

# Livewire Refactoring Skill

## When to Activate

Apply this skill when refactoring Livewire components that have grown too large, when extracting business logic into proper layers, or when enforcing the Action-Oriented MVC pattern. Activate on seeing inline DB calls, business rule conditionals, static helpers, or repeated UI patterns inside components.

## The Problem Space

Livewire components naturally accumulate four categories of misplaced code:

| Category | Symptom | Belongs In |
|----------|---------|------------|
| Business logic | `Model::create()`, `DB::transaction()` | Action |
| Business rules | `if ($status === 'x')`, date comparisons | Entity |
| Static utilities | Formatting, generation, parsing | Support class |
| Repeated UI patterns | Confirm dialogs, modals | Shared component or trait |

## Key References

- **Actions**: `app/Core/Actions/BaseAction.php` — `transaction()`, `log()`, `HandlesActionErrors`
- **Entities**: `app/Core/Entities/BaseEntity.php` — `final readonly`, `fromModel()` bridge
- **BaseRecordManager**: `app/Core/Livewire/BaseRecordManager.php` — CRUD base class
- **Livewire Pattern**: `docs/architecture/livewire-pattern.md`
- **Action Pattern**: `docs/architecture/action-pattern.md`

## Refactoring Workflow

### Step 1 — Extract Business Logic to Action

Scan for `Model::create/update/delete`, `DB::transaction()`, `Validator::make()`, `Mail::send()`, `Notification::send()`.

1. Create `app/{Module}/{SubModule}/Actions/{Verb}{Subject}Action.php`
2. Extend `BaseAction`, single `execute()` method
3. Move validation to Action input
4. Wrap persistence in `$this->transaction()`
5. Add `$this->log()` and event dispatch
6. Inject via method parameter in the component

```php
// Before — in Livewire
public function save(): void
{
    $this->validate();
    $report = Report::create(['user_id' => auth()->id(), ...]);
    Log::info('report_created', ['id' => $report->id]);
    flash()->success(__('reports.created'));
}

// After — delegated to Action
public function save(CreateReportAction $action): void
{
    $this->validate();
    try {
        $action->execute($this->form->toArray());
        flash()->success(__('reports.created'));
    } catch (RejectedException $e) {
        flash()->error($e->getMessage());
    }
}
```

### Step 2 — Extract Business Rules to Entity

Scan for `if ($model->status === 'x')`, date comparisons, multi-field conditionals.

1. Create `app/{Module}/{SubModule}/Entities/{Name}.php` — `final readonly`, extends `BaseEntity`
2. Extract state via `fromModel()` factory
3. Add boolean methods (`isActive()`, `allowsLogin()`, `isExpired()`)
4. Add named accessor on Model (`asApprentice(): Apprentice`)
5. Replace inline checks with `$model->asEntity()->method()`

### Step 3 — Extract Repeated UI Patterns

- Confirm dialogs → shared `<x-ui::confirm>` component
- CRUD tables → `BaseRecordManager`
- Selection/sorting → reuse existing traits from `app/Core/Livewire/Concerns/`

### Step 4 — Extract Complex Forms

Forms with 5+ fields or conditional validation → `app/{Module}/Livewire/Forms/{Name}Form.php` extending `Livewire\Form`.

### Step 5 — Extract Static Utilities

`Support/` classes for formatting, parsing, generation. No Eloquent, no framework dependencies (use `Services/` when framework deps are needed).

## Verification

- Zero `Model::create/update/delete` in the component?
- Zero `DB::` calls in the component?
- Zero inline business rule conditionals?
- Zero `Mail`/`Notification` dispatches?
- All Actions have single `execute()` with typed parameters?
- Actions throw `RejectedException` (not `RuntimeException`)?
- Component catches `RejectedException` and flashes message?
- Confirm dialogs use `askAction` → `confirmAction` pattern?
- Entities are `final readonly` with `fromModel()`?
- Static helpers moved to `Support/`?
