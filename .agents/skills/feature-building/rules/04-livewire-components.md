# Livewire Components

## What It Enforces

Livewire components handle UI state and delegation only. They must not contain business logic (Model
CRUD, DB transactions), business rules (status checks, date logic), or side effects (logging,
events). All of those belong in Actions and Entities.

## Why It Matters

Thin components are easier to maintain, test, and reason about. A Livewire component that only
manages UI state has clear boundaries: public properties for state, method injection for Actions,
`boot()` for authorization, and `render()` for the view. Logic that crosses these boundaries is
harder to test and more likely to contain bugs.

## When It Applies

Every Livewire component must follow these rules:

- Business logic: delegate to Actions via method injection
- Business rules: delegate to Action (NEVER access Entity methods directly)
- DB mutations: never directly in the component
- Side effects: audit, events in Actions only
- Data input: pass validated data as DTOs (`BaseData`) to Actions, never raw arrays
- Authorization: `boot()` for role checks, Gate for granular
- UI state: typed public properties
- Layout: `#[Layout('layouts::app')]` attribute
- File uploads: `use Livewire\WithFileUploads`
- Flash messages: `flash()->success()` (PHPFlasher, not maryUI Toast)
- Render queries: read-only, paginated, searchable

Exceptions: Read-only queries for `render()` (with search/filter/pagination) are acceptable in
components because they are presentation logic, not business logic.
