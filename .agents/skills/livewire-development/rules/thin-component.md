# Thin Component — Delegation and Boundaries

> **Last updated:** 2026-08-25 **Changes:** sync — flash()-> → TallstackUI $this->toast()->send() (FB792 0.15.0)

Livewire components are the presentation layer: they own UI state, UX validation, and the rendering
of an Action's result — and nothing else. "Thin" is not a style preference; it is the architectural
rule that keeps mutations inside Command Actions (C1), transactions owned, and business rules in the
Entity. A fat component is the single most common architecture violation in this codebase, and the
arch-guard scanner (`scan_violations.py`, C1) exists because of it.

---

## The Thin Component Doctrine

**What it enforces:** A Livewire component contains ONLY:

- Public properties for UI state (form bindings, modal visibility, search/filter)
- Validation rules for UX feedback
- Delegation to Actions via method parameter injection
- Read-only queries (or via Read Actions)
- Authorization checks via Policies

It NEVER contains:

- `Model::create/update/delete/save` — delegate to a Command Action
- `DB::transaction()` — owned by the Action
- `event()` or `dispatch()` — the Action's `$this->dispatchEvent()` owns side effects
- Business rules on record state — delegate to the Entity
- `app()->make()` / `new Action()` — inject via method parameter

**Why it matters:** Each forbidden item bypasses a safety net. Direct `Model::create()` in a
component skips the Action's transaction, audit log, and domain validation; a transaction in the
component splits transaction ownership across layers; inline business rules duplicate Entity logic
and drift from it; service-locator style breaks the DI contract (C2). The component should be so thin
that a code review can verify it in seconds.

**How to apply:** When a component starts accumulating logic, ask where each piece belongs:
state → the component, mutation → a Command Action, query complexity → a Read Action, rule evaluation
→ the Entity, async side effect → an Event. Route each accordingly and keep the component as wiring.

**Pitfalls to avoid:**

- A "small" `Model::save()` for one field — C1 has no size threshold.
- `app()->make(Action::class)` or `new Action()` in a component to avoid method injection — C2.
- Copy-pasting a rule check (`if ($model->status === 'confirmed')`) into the component instead of
  `$model->asEntity()->rule()`.

**Verification:** `scan_violations.py` reports no C1/C2 findings in the component; the component's
methods are state handling, `render()`, and Action-delegating handlers.

---

## Action Delegation via Method Injection

**What it enforces:** Actions are injected as method parameters of the handler, never constructed in
the component. `RejectedException` is caught specifically, before `Throwable`. The component passes
the resolved data to the Action and branches on the returned `ActionResponse`.

**Why it matters:** Method injection is the project's DI style (C2) — the container resolves the
Action's dependencies automatically and the component never references a service locator. Catching
`RejectedException` first routes business errors (a quota full, a duplicate) to the user's message,
while the generic `Throwable` catch presents a safe fallback; reversing the order swallows business
messages into the generic path or leaks infrastructure errors.

**How to apply:** Follow the canonical handler shape:

```php
public function save(CreateUserAction $action): void
{
    $this->form->validate();
    $result = $action->execute($this->form->toArray());

    if ($result->failed()) {
        $this->toast()->error()->send($result->message);
        return;
    }

    $this->resetForm();
    $this->toast()->success()->send($result->message);
    $this->redirect('/users');
}
```

And the exception order that never changes:

```php
try {
    $action->execute($data);
} catch (RejectedException $e) {
    $this->toast()->error()->send($e->getMessage());
} catch (\Throwable $e) {
    $this->toast()->error()->send(__('common.error'));
}
```

Pass a DTO or typed scalars to the Action — never a raw `array` of 3+ parameters (C7, DTO-for-3+
params).

**Pitfalls to avoid:**

- Constructor injection of Actions — this is reserved for non-Action dependencies like Form Objects.
- `catch (\Throwable $e)` placed before `catch (RejectedException $e)` — unreachable code plus
  swallowed business messages.
- Passing `$this->all()` or an unbounded request array to an Action (D5 spirit) — resolve the shape
  as a DTO.

**Verification:** Every handler signature injects its Action as a parameter; `RejectedException`
appears before `Throwable` in every try/catch; no `new XxxAction` appears in `app/*/Livewire`.

---

## Read-Only Entity Access for UI Decisions

**What it enforces:** Entities may be used in the component for READ-ONLY UI decisions — showing or
hiding a button, choosing a label — by calling a pure rule method. WRITE decisions still go through
an Action.

**Why it matters:** Entity rules are the single source of truth for business state (`isFull()`,
`canBeDeleted()`). Letting the UI consult them keeps the view faithful to the domain without
duplicating logic. The boundary is write-side: the Entity answers "can we?", the Action performs the
mutation; conflating them reintroduces inline rules on the write path.

**How to apply:**

```php
public function canDelete(): bool
{
    return $this->record->asEntity()->canBeDeleted();
}
```

Use this in the blade to render a disabled or hidden delete control. When the user actually deletes,
call the Command Action — the Entity rule is re-checked inside the Action's transaction.

**Pitfalls to avoid:**

- Performing the mutation after a component-side Entity check without an Action — checks in the UI
  are advisory; enforcement lives in the Action.
- Re-implementing `canBeDeleted()` inline in Blade when the Entity already owns it.

**Verification:** All `asEntity()` calls in components drive UI state; every write path routes
through a Command Action.

---

## Blade View Contains No Business or UI Logic

**What it enforces:** Blade files are pure presentation. No business logic or UI computation of any
size may live in `.blade.php`. All derived state — percentages, ratios, stage/funnel assembly, `max()`,
conditional branching that depends on business rules — must be computed in the Livewire component
and exposed as ready-to-render public properties or `#[Computed]` getters. Lightweight client-side
interactivity belongs in Alpine.js (`x-data`), not in Blade `@php` blocks. `@if` / `@elseif` / `@else`
with inline PHP expressions should be avoided — Blade should only gate on a precomputed boolean
(`@if ($isSuperAdmin)`) when needed, and UI-only toggles should prefer Alpine `x-show` / `x-if` over server `@if`. `@if` is not strictly forbidden, but should be avoided where possible.

**Why it matters:** Blade `@php` blocks with calculations bypass testability, duplicate logic, and
hide business rules in an untestable template. Moving computation to Livewire makes it typed,
testable, and traceable to the spec; Blade becomes a trivial binding layer that can be reviewed for
accessibility and localization only.

**How to apply:**

- In the Livewire component (`mount()` or `#[Computed]`): compute `$completionRate = $total > 0 ? round(($completed/$total)*100) : 0`, assemble `$pipelineStages`, calculate `$maxV`, `$absorption`, etc., and expose as `public int` / `public array`.
- In Blade: bind directly — `{{ $completionRate }}%`, `@foreach ($pipelineStages as $stage)`.
- Alpine.js owns toggles, dropdowns, and local filtering (`x-data="{ open: false }"`); it never re-implements server business rules.
- Review gate: any Blade file containing `@php` with business-affecting arithmetic or array assembly is a blocking issue. See `docs/conventions.md` §14 and `docs/architecture/livewire-pattern.md` §1.1.

**Pitfalls to avoid:**

- A "tiny" `@php $rate = round(($a/$b)*100)` in Blade — no size threshold, move it to Livewire.
- Assembling `$stages` or `$filters` arrays in Blade instead of the component.
- Using Blade `@if` to compute a value rather than to read an already-computed boolean.

**Verification:** No `@php` blocks with calculations in `resources/views/**/*.blade.php`; every derived value rendered in Blade is a public property or computed getter of its Livewire component.

---

## References

| Topic                       | Asset                                        |
| --------------------------- | -------------------------------------------- |
| Thin Component Rule summary | `livewire-development/SKILL.md` §Thin Component Rule |
| Blade presentation rule     | `docs/conventions.md` §14, `docs/architecture/livewire-pattern.md` §1.1 |
| Action delegation           | `docs/architecture/action-pattern.md`        |
| Entity contracts            | `docs/architecture/entity-pattern.md`        |
| Critical invariants (C1, C2, C7) | `AGENTS.md` §Critical Invariants         |