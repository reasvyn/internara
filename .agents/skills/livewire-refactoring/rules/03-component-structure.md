# Component Structure (Refactoring)

## What It Enforces

After extracting Actions and Entities, the Livewire component should contain only: UI state properties, form validation, the confirm dialog pattern, read-only queries for render, and authorization in `boot()`. Nothing else.

## Why It Matters

A well-structured component is easy to audit for security (auth in `boot()`), easy to test (all logic is in injectable Actions), and easy to understand (public properties describe the complete UI state). The component becomes a thin coordination layer between the browser and the domain.

## When It Applies

### Allowed in Components
- UI state: public properties for form data, modal visibility, search, selection
- Form validation: `$this->validate()` for UX only (Action re-validates)
- Confirm dialog: `askAction()` sets target → `confirmAction()` calls injected Action
- Read-only queries in `render()` for searchable, paginated lists
- Authorization in `boot()` via role check or Gate
- Flash messages via PHPFlasher (`flash()->success()`, not maryUI Toast)

### NOT Allowed
- Inline DB mutations (`DB::transaction()`, `Model::create()`)
- Inline business rules (`if ($year->is_active)`)
- Side effects (`Log::info()`, `event(new ...)`)
- Static helper methods (`public static function formatSomething()`)
- Bare `wire:confirm` for destructive actions (use the two-step pattern)
- maryUI Toast methods (`$this->success()`, `$this->error()`)

Exceptions: The render query is an exception to the "no business logic" rule because it's presentation logic — fetching data to display.
