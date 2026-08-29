# Livewire Component Patterns — Thin Components, Injection & Forms

## Description

Thin component rule, auto-discovery, CRUD tables via BaseRecordManager, Action injection, Form
Objects, and common pitfalls. Grounded in **Thin Controller** (MVC), **Presentation Model** (PoEAA),
**MVVM**, and **Separation of Concerns** — all mapped to Internara's Livewire 4.3 + TallstackUI stack.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Thin Component Rule (C1).** Livewire components handle **only** UI state and delegation. No `Model::create/update/delete`, no `DB::` queries, no `DB::transaction()`, no business logic, no `Log::info()`, no `event(new ...)`, no `Notification::send()`. All mutations go through Actions. This is C1 (invariant from `docs/conventions.md` §9).

2. **No raw request to create/update (D5).** Never pass `$request->all()` directly to an Action. Build a DTO (`BaseData::from()`) from validated form data. This is D5.

3. **No inline cache keys (C4).** Never write `Cache::remember('key', ...)` in Livewire. Cache logic belongs in Read Actions.

4. **Blade contains no business logic.** Blade files are pure presentation. No `@php` blocks with calculations, no `@if` with complex PHP expressions. Every derived value must be computed in the Livewire component and exposed as ready-to-render public properties.

5. **Action injection via method parameters.** Actions are injected as method parameters — never resolved with `app()->make()` or `new Action()` inside the component body. Laravel's container resolves the Action from the method signature.

6. **DTO for 3+ params (C7).** When passing 3+ values to an Action, build a DTO from validated form data. Never pass raw arrays with 3+ keys.

7. **Two-step confirmation for destructive actions.** Never use bare `wire:confirm`. Use `ask{Action}()` → `confirm{Action}()` pattern with `<x-core::ui.confirm />`.

---

## How to Apply

### 1. Thin Controller (MVC)

The MVC pattern requires controllers to be thin — delegating business logic to service/model layers. In Internara, Livewire components ARE the controllers. They handle UI state (form bindings, modal visibility, search) and delegate all business operations to Actions. This makes them easy to audit, test, and understand.

### 2. Presentation Model (PoEAA)

The Presentation Model pattern separates UI behavior from UI rendering. The Livewire component IS the Presentation Model — it computes derived values (`$completionRate`, `$isSuperAdmin`, `$pipelineStages`) and exposes them as public properties. Blade consumes these pre-computed values without any logic.

**Reference:** [PoEAA — Presentation Model](https://martinfowler.com/eaaDev/PresentationModel.html)

### 3. MVVM Alignment

Livewire components align with MVVM: the component class is the ViewModel (properties + methods), the Blade template is the View, and the backing data (Actions, Entities, Models) is the Model. This is a natural fit — Livewire's reactive binding (`wire:model`) updates the ViewModel, which re-renders the View.

### 4. Separation of Concerns

Each layer has a single concern:

| Layer | Concern |
|-------|---------|
| **Livewire Component** | UI state, delegation, toast messages |
| **Blade Template** | Presentation ({{ }}, @foreach, @if with precomputed booleans) |
| **Action** | Business operation, transaction, logging, event dispatch |
| **Entity** | Business invariants, rules, state transitions |
| **DTO** | Typed data contract between layers |

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `Model::create()` in Livewire method | Inject and call `CreateAction` | C1 — mutation in Livewire |
| `$this->validate()` without Action re-validation | Action re-validates authoritatively | Validation only in UI |
| `app()->make(CreateAction::class)` | Inject via method parameter | Manual resolution |
| Raw array `[3+ keys]` passed to Action | Build `BaseData::from($validated)` | C7 — no DTO for 3+ params |
| `Cache::remember('stats', ...)` in Livewire | Move to Read Action | C4 — inline cache key |
| Business rule in Blade (`@if ($user->role === 'admin' && $stats['x'] > 0)`) | Expose `public bool $isSuperAdmin` in component | Business logic in Blade |
| `wire:confirm="Are you sure?"` on delete button | Two-step `askDelete()`/`confirmDelete()` | No user feedback on failure |
| `public static function formatSomething()` in Livewire | Move to Support class | Static method in component |
| Blade `@php $total = $a / $b; @endphp` | Compute in component, expose as property | Calculation in Blade |
| Inline `Notification::send()` in component | Dispatch from Command Action | Side effect in UI layer |

---

## Quick References

- `docs/conventions.md` §8-9 Livewire Conventions — C1, C4, C7, D5
- `docs/guides/arch/action-pattern.md` — Action Triad and injection
- `docs/guides/arch/data-pattern.md` — DTO contract
- `docs/guides/arch/entity-pattern.md` — Entity boundary
- `resources/views/ui/components/confirm.blade.php` — shared confirmation component
- [Livewire — Documentation](https://livewire.laravel.com/docs) — Livewire 4.x
- [TallStackUI — Components](https://tallstackui.com/docs) — x-ts-* components
- [PoEAA — Presentation Model](https://martinfowler.com/eaaDev/PresentationModel.html) — UI logic separation
- [MVC Pattern](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) — separation of concerns
- [Thin Controller](https://www.martinfowler.com/eaaCatalog/tableModule.html) — MVC best practice
