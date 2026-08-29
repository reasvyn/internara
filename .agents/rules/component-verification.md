# Component Verification — Ensuring Thin, Safe, Complete Components

> **Last updated:** 2026-08-25 **Changes:** sync — flash() → TallstackUI toast, maryUI toast methods → removed (FB792 0.15.0)

Verification is the gate that catches every violation the earlier rules define. It runs per component
as the final step of the build (and again before commit), because each check below corresponds to a
specific defect that otherwise ships silently — a mutation leaking into the component, a swallowed
business error, an unannounced flash, an unlocalized label. The checks are listed with the failure
each prevents, not as decoration.

---

## Thin Component Boundaries

**What it enforces:** The component contains no persistence, transactions, side effects, service
locators, or inline business rules:

- No `Model::create/update/delete/save` — delegates to Command Actions (C1)
- No `DB::transaction()` / `DB::beginTransaction()` — the Action owns transactions
- No `app()->make()` / `resolve()` / `new Action()` — method parameter injection (C2)
- No inline business rules (`if ($status === 'x')`) — the Entity owns rules
- No side effects: `event()`, `Notification::send()`, `Log::info()` — the Action dispatches events
- No legacy toast APIs (`flash()->`, maryUI `$this->success()` / `$this->error()`) — use TallstackUI `$this->toast()->send()` (Interactions) + `ActionResponse`

**Why it matters:** Each forbidden item moves a responsibility out of its architectural owner. A
component that creates a model bypasses the Action's transaction and audit log; one that fires events
or notifications bypasses the Action's dispatch lifecycle; toasts called directly create UI effects
that a controller or test cannot assert against the ActionResponse contract. Together these are the
"fat component" pattern `scan_violations.py` exists to flag (C1/C2), and they are the leading source
of untraceable mutations in the codebase.

**How to apply:** During review (own or peer), scan the component for the six patterns by name. For
each occurrence, route the work to its owner: mutation → Command Action, complex query → Read Action,
rule evaluation → Entity, async side effect → Action event dispatch, UX feedback → TallstackUI
`$this->toast()->send()` + `ActionResponse`.

**Pitfalls to avoid:**

- Accepting a `Model::update()` in a component "because the change is trivial" — C1 is absolute.
- `Log::info()` in a component for debugging that never gets removed (D2).
- Treating `$this->success()` as a toast equivalent — it is a removed maryUI method; use `$this->toast()->error()->send()` (TallstackUI Interactions).

**Verification:** `scan_violations.py` reports no C1/C2 findings in the component; a grep of the
component finds no `Model::create/update/delete/save`, no `DB::transaction`, no `new XxxAction`,
no `app()->make`, and no inline state checks on `->status`/`->role`.

---

## Action Injection Correctness

**What it enforces:** Actions are injected via method parameters (not constructors). `RejectedException`
is caught before `Throwable`. Business errors surface `$e->getMessage()`; infrastructure errors
surface a generic localized message. Actions receive a DTO or typed scalars — never a raw `array` of
3+ parameters.

**Why it matters:** Constructor injection of Actions breaks the method-parameter DI contract (C2)
and makes components impossible to call with alternate Actions in tests. Catching `Throwable` first
(or at all without `RejectedException`) swallows domain messages into a generic error, so the user
never learns the quota is full — they just see "something went wrong". Raw arrays skip the DTO's
named contract (C7) and make the Action's input unverifiable.

**How to apply:** Every handler signature lists its Actions as parameters. The catch order is fixed:

```php
try {
    $action->execute($dto);
} catch (RejectedException $e) {
    $this->toast()->error()->send($e->getMessage());
} catch (\Throwable $e) {
    $this->toast()->error()->send(__('common.error'));
}
```

Pass `$this->form->toArray()` or an explicitly built DTO — not `$this->all()`.

**Pitfalls to avoid:**

- `catch (\Throwable)` above `catch (RejectedException)` — dead code plus swallowed business errors.
- A constructor parameter typed to an Action class.
- Passing an unbounded request/all array into an Action (D5 spirit) — resolve the exact shape.

**Verification:** Grep shows no Action types in constructors; catch blocks order
`RejectedException` before `Throwable`; every Action call passes a DTO or resolved typed data.

---

## Form Object Compliance

**What it enforces:** Forms with 5+ fields are extracted to a Form Object extending `Livewire\Form`.
The Form Object prepares data only — it never calls an Action directly. Validation rules exist (in
the component or the Form Object) for every managed input.

**Why it matters:** A 5+-field form inline bloats the component past readability and makes the thin
rule unreachable. A Form Object that also executes Actions blurs the data-prep/mutation boundary and
makes the component's call flow undiscoverable — the Action is invoked from a second location the
reviewer must hunt for. Without validation rules anywhere, malformed input reaches the Action
unchecked and UX feedback is silent.

**How to apply:** Confirm the component uses `$this->form` bound to a `Livewire\Form` subclass for
fields beyond the 4-input threshold, that `validate()` covers all rules, and that Action calls happen
only in the component — never inside the form class.

**Pitfalls to avoid:**

- A Form Object extending anything other than `Livewire\Form` (no validation/reset lifecycle).
- Action invocation inside `toArray()` or a form method "for convenience".
- A component whose `mount()`-time properties are never validated anywhere.

**Verification:** Every 5+-field component has a Form Object in
`app/{Module}/{SubModule}/Livewire/Forms/` extending `Livewire\Form`; no Action type is
imported/used inside the form; every input has a validation rule.

---

## Read-Only Entity Access

**What it enforces:** Entities are used in the component only for READ-ONLY UI decisions
(show/hide a button, present a label). WRITE decisions still go through a Command Action.

**Why it matters:** Entity consultation in the UI is how views stay faithful to domain rules without
duplicating them. The write path must remain Action-owned because that is where the transaction,
logging, and enforcement re-check run. A component that freely calls `asEntity()` and then mutates
directly has crossed into C1 territory.

**How to apply:** `asEntity()->canBeDeleted()` drives `@disabled`/`v-if`-style rendering; actual
deletion calls `DeletePlacementAction`. The rule re-check inside the Action is the enforcement; the
UI check is affordance.

**Pitfalls to avoid:**

- Mutating after a component-side Entity check without an Action.
- Using an Entity method that performs I/O — Entities are pure value objects (C5).

**Verification:** Every `asEntity()` call in the component feeds UI state; every mutation call site is
an Action `execute()`.

---

## Destructive Patterns — Blocked Before Commit

**What it enforces:** These shapes are rejected outright:

- `wire:confirm` for destructive operations without a two-step confirmation — use the shared
  confirm component (`rules/localization.md` §Confirmation Dialogs)
- Form Object extending anything other than `Livewire\Form`
- Action injection in a constructor (must be a method parameter)
- `$this->all()` passed directly to an Action
- A component with no test in `tests/`
- Any user-facing string not routed through `__()` (D3)
- A component whose dynamic regions, modals, or icon-only buttons violate the accessibility rules

**Why it matters:** Each pattern is a known failure with a known replacement. Blocking by name at
review is faster and more reliable than rediscovering each failure in QA — `wire:confirm` alone
deletes data with one tap where the product requires confirmation; an untested component is an
unverifiable component; an unlocalized string breaks the bilingual contract.

**How to apply:** Run the SKILL.md verification checklist as a gate:

- No `Model::create/update/delete` in the component
- No `DB::transaction()` in the component
- No `app()->make()` or `new Action()` — method injection
- `RejectedException` caught before `Throwable`
- Form Objects used for 5+ fields
- Validation rules defined (component or Form Object)
- Component test exists in `tests/`
- All user-facing strings use `__()` for localization
- Focus management correct (modal open/close, wire:navigate)
- Dynamic content wrapped in `aria-live` containers
- Icon-only buttons include `aria-label`
- Form inputs have associated labels (TallstackUI `label` prop)
- Status labels use `LabelEnum::label()` (not hardcoded text)

**Pitfalls to avoid:**

- Fixing a destructive pattern at commit time instead of during the build — the failing check is
  evidence the build order's per-verification step was skipped.
- Letting "it works in the browser" override the checklist — browser success is a subset of these
  gates, not a replacement.

**Verification:** `scan_violations.py`, `scan_security.py`, `scan_conventions.py` (D3/D4), and
`scan_class_contracts.py` report the component's module clean; the component's Pest test exists and
passes; the full checklist above is green before commit.

---

## References

| Topic                              | Asset                                         |
| ----------------------------------- | --------------------------------------------- |
| Thin component & injection rules    | `rules/thin-component.md` (this skill)        |
| Component structure & Form Objects  | `rules/component-structure.md` (this skill)   |
| Accessibility                       | `rules/accessibility.md` (this skill)         |
| Localization                        | `rules/localization.md` (this skill)          |
| Testing components                  | `docs/guides/arch/testing-pattern.md`        |
| Arch-guard quality gate             | `.agents/skills/arch-guard/SKILL.md`          |