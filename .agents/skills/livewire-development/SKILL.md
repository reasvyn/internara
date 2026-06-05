# Livewire Development Skill

## When to Activate

Apply this skill for any task involving Livewire — building new components, debugging reactivity, handling file uploads, implementing real-time validation, managing CRUD tables, or migrating existing components. Activate whenever you see `wire:` directives, Livewire-specific classes, or Alpine.js integration in Blade templates.

## Core Principles

### Thin Component Rule

Livewire components are the UI layer only. They handle:
- UI state: form bindings, modal visibility, search input, selection state
- Form validation: UX-level inline validation (the Action re-validates authoritatively)
- Delegation: calling Actions via dependency injection in method signatures
- Flash messages: success/error feedback using PHPFlasher

They must NOT contain:
- Business logic: no `Model::create()`, `DB::transaction()`, or direct mutations
- Business rules: no `if ($status === 'x')` checks — delegate to Entities
- Side effects: no logging, event dispatching, or notification sending — those belong in Actions

### Component Location and Discovery

Components live in `app/Domain/{Domain}/Livewire/` and are auto-discovered by AppServiceProvider. The alias pattern is `{kebab-domain}.{kebab-class-name}`. Views are at `resources/views/{domain}/{component-name}.blade.php`. CRUD table components extend `BaseRecordManager` which provides pagination, search, sort, and selection.

## Architecture Patterns

### Confirmation Dialog Pattern

Destructive operations follow an explicit two-step pattern: `askAction()` sets the target and shows a confirmation modal; `confirmAction()` receives the injected Action, calls it within a try/catch, and handles RejectedException with a flash message. This replaces bare `wire:confirm` directives.

### BaseRecordManager

All CRUD table components extend BaseRecordManager. This abstract class provides pagination, search, multi-select, sorting, and bulk actions. Subclasses implement `headers()` (column definitions) and `query()` (base Builder). The `rows()` method applies search, filters, and sorting automatically.

## Verification Before Finalizing

- No inline DB mutations or Model CRUD in the component?
- No inline business rule checks (status comparisons, date logic)?
- No side effects (logs, events, notifications)?
- Are Actions injected via method parameter, not resolved manually?
- Is authorization handled in `boot()` or via Policy, not per-method?
- Are wire:key attributes present on @foreach loops?
- Is `RejectedException` caught and displayed as a flash message?
