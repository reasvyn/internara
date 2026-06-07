---
name: livewire-refactoring
---

# Livewire Refactoring Skill

## When to Activate

Apply this skill when refactoring Livewire components that have grown too large, when extracting
business logic from components into proper layers, or when enforcing the Action-Oriented MVC pattern
on existing code. Activate when you see inline DB calls, business rule conditionals, static helpers,
or repeated UI patterns inside Livewire components.

## Core Principles

### The Problem Space

Livewire components naturally accumulate four categories of misplaced code:

- Business logic (DB writes, validation) that belongs in Actions
- Business rules (canX checks, state transitions) that belong in Entities
- Static utilities (formatting, generation, parsing) that belong in Support
- Repeated UI patterns (confirm dialogs, modals) that belong in shared components

### Layer Separation

Every Livewire component must be thin by design. The component owns UI state (form data, modal
visibility, search input, selection). Actions own validation, persistence, and side effects.
Entities own business rule answers. Support owns static helpers.

The refactoring pattern is always: identify misplaced code → extract to the correct layer → inject
as a dependency → call from the component.

## Refactoring Workflow

### Step 1: Identify Inline Business Logic

Scan for `Model::create/update/delete`, `DB::transaction()`, `Validator::make()`, `Mail::send()`,
`Notification::send()` in the component. These all belong in Actions.

### Step 2: Extract to Action

Move the operation to `app/{Module}/Actions/{Verb}{Subject}Action.php`. The Action extends
BaseAction, has a single `execute()` method, validates input, wraps persistence in
`$this->transaction()`, and emits side effects. The component receives the Action via method
injection.

### Step 3: Identify Inline Business Rules

Scan for `if ($model->status === 'x')`, date comparisons, or multi-field conditionals. These belong
in Entities as named boolean methods.

### Step 4: Extract to Entity

Create `app/{Module}/Entities/{Name}State.php` as a `final readonly` class extending BaseEntity. Add
a `fromModel()` factory and a named accessor on the Model. Call the Entity method from the Action or
component.

### Step 5: Extract Repeated UI

Extract confirm dialogs into a shared `<x-ui::confirm>` component. Use BaseRecordManager for table
patterns. Move selection and sorting logic into traits.

## Verification Before Finalizing

- Are there zero `Model::create/update/delete` calls in the component?
- Are there zero `DB::` calls in the component?
- Are there zero inline business rule conditionals?
- Are there zero `Mail`/`Notification` dispatches?
- Do all Actions have a single `execute()` with typed parameters?
- Do Actions throw `RejectedException` (not `RuntimeException`)?
- Does the component catch `RejectedException` and flash a message?
- Are confirm dialogs using the `askAction` → `confirmAction` pattern?
- Are Entities `final readonly` with zero framework imports?
- Are static helpers moved to Support?
