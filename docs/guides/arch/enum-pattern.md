# Enum Pattern — LabelEnum, StatusEnum, State Machines & Type Safety

## Description

This pattern governs how Internara defines **PHP 8 string-backed enums** for labels, state machines, and UI badges. It synthesizes global industry standards — **Finite State Machine** (FSM), **Type State Pattern** (compile-time state machines), **GoF State Pattern** (behavioral delegation), **State Transition Guards** (pre-conditions on transitions) — into enforceable rules tied to Internara's stack: `LabelEnum`, `StatusEnum`, `ColorableEnum` contracts, `UPPER_SNAKE` case names, lowercase string values, and `match()` exhaustive transitions.

Without it, status logic scatters across Models, Actions, and Views — impossible to know valid transitions, terminal states, or display colors without reading every caller. With it, every state machine is self-documenting, type-safe, testable, and discoverable by contract.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Every enum implements `LabelEnum`.** Every enum MUST have `label(): string` that returns a translated human-readable label via `__()`. This ensures all enum values are displayable to users without ad-hoc translation in views.

2. **`UPPER_SNAKE` case names, lowercase string values.** Case names: `DRAFT`, `PUBLISHED`, `REVISION_REQUIRED`. Backing values: `'draft'`, `'published'`, `'revision_required'`. Multi-word values use `snake_case`. Never `CamelCase` values.

3. **State machine enums implement `StatusEnum`.** Enums that represent lifecycle states MUST implement `StatusEnum` (extends `LabelEnum`), which mandates `isTerminal(): bool`, `canTransitionTo(StatusEnum $target): bool`, and `validTransitions(): array`. This is the **Finite State Machine** contract — every state knows its valid transitions.

4. **Terminal states return `[]` from `validTransitions()`.** A terminal state has no further transitions. The `match()` must be exhaustive — every case must appear. Return type is `list<static>`.

5. **Transition guards in Actions, not in Views.** Command Actions enforce `canTransitionTo()` before persisting. Views and Livewire components should NOT check transition validity — they delegate to the Action. This is **Defence in Depth** — the Action is the last gate before persistence.

6. **`->value` for Model defaults, never hardcoded strings.** Model `$attributes` and factory definitions MUST use `ExampleStatus::DRAFT->value`, never `'draft'`. This prevents string drift from enum definitions.

7. **Enum casting for status columns.** Status columns are cast to their enum class via `$casts`. This allows direct enum comparison: `$model->status === ExampleStatus::SUBMITTED`.

---

## How to Apply

### 1. Enum Architecture — Three-Tier Contracts

```
┌─────────────────────────────────────────────────┐
│                  LabelEnum                       │
│  label(): string                                │
│  ▲                                              │
│  │                                              │
│  ├── StatusEnum (state machines)                │
│  │   isTerminal(): bool                         │
│  │   canTransitionTo(StatusEnum): bool          │
│  │   validTransitions(): array                  │
│  │                                              │
│  └── ColorableEnum (UI badges)                  │
│      color(): string                            │
└─────────────────────────────────────────────────┘
```

| Contract | Mandate | Purpose |
|----------|---------|---------|
| `LabelEnum` | All enums | Human-readable label via `__()` translation |
| `StatusEnum` | Lifecycle enums | State machine transitions, terminal detection |
| `ColorableEnum` | UI badge enums | Tailwind/UI color per status |

### 2. LabelEnum Contract

```php
interface LabelEnum
{
    public function label(): string;
}
```

All `label()` implementations delegate to `__()` for i18n. Three styles: per-case `match()` (most common), dynamic key from value, plain value (no translation needed).

### 3. StatusEnum — Finite State Machine Contract

```php
interface StatusEnum extends LabelEnum
{
    public function isTerminal(): bool;
    public function canTransitionTo(self $target): bool;
    public function validTransitions(): array;
}
```

**Common `canTransitionTo` implementation:**

```php
public function canTransitionTo(StatusEnum $target): bool
{
    if (! ($target instanceof self)) {
        return false; // Type safety — reject cross-enum transitions
    }

    return in_array($target, $this->validTransitions(), true);
}
```

### 4. State Machine Patterns

Every status enum fits one of these patterns:

| Pattern | Description | Example |
|---------|-------------|---------|
| **Revision Loop** | Draft → Submitted → Revision Required → (back to Draft) | Logbook, assignment submissions |
| **Approval Pipeline** | Pending → Approved / Rejected | Registration, certificate approval |
| **Linear Progression** | Stage 1 → Stage 2 → Stage 3 → Complete (+ Cancel escape) | Internship enrollment |
| **Incident Lifecycle** | Reported → Investigating → Resolved → Closed | Bug reports, complaints |
| **Two-State (Binary)** | Initial → Terminal | Simple approve/reject |
| **All Terminal** | Every state is terminal — no transitions | Classification records |
| **Complex Lifecycle** | Multiple forward stages with cancellation at several points | Multi-step approval |
| **User Account** | Parallel paths through activation, verification, restriction, suspension | User lifecycle |

### 5. Transition Canonical Form

```php
public function validTransitions(): array
{
    return match ($this) {
        self::STATE_A => [self::STATE_B, self::STATE_C],
        self::STATE_B => [self::STATE_D],
        self::STATE_C => [], // Terminal
        self::STATE_D => [], // Terminal
    };
}
```

Rules: Terminal states return `[]`. All valid destinations listed explicitly — no wildcards. `match()` is exhaustive. Return type `list<static>`.

### 6. Guarding Transitions in Actions

```php
class SubmitAction extends BaseCommandAction
{
    public function execute(Model $entry, array $data): Model
    {
        $target = TargetStatus::TARGET_STATE;

        if (!$entry->status->canTransitionTo($target)) {
            throw new RejectedException(
                __('Cannot transition from :current to :target', [
                    'current' => $entry->status->label(),
                    'target' => $target->label(),
                ]),
            );
        }

        return $this->transaction(function () use ($entry, $data) {
            // ...
        });
    }
}
```

### 7. Business Logic on Enums

Boolean methods that answer questions about the current state. Prefixed with `is`, `has`, `can`, `requires`, or `allows`:

| Prefix | Semantics | Example |
|--------|-----------|---------|
| `is` | Boolean state query | `isTerminal()`, `isActive()` |
| `has` | Feature/attribute presence | `hasProperty()` |
| `can` | Permission or ability | `canTransitionTo()` |
| `requires` | Prerequisite needed | `requiresAttachment()` |
| `allows` | Permission granted | `allowsLogin()` |

### 8. ColorableEnum — UI Badge Colors

Optional contract for enums displayed as UI badges:

```php
interface ColorableEnum
{
    public function color(): string;
}
```

Returns a Tailwind color keyword: `primary`, `success`, `warning`, `error`, `info`.

### 9. Model Defaults & Casting

```php
// ✅ Correct — uses enum value
protected $attributes = [
    'status' => ExampleStatus::DRAFT->value,
];

// ❌ Wrong — hardcoded string drifts from enum
protected $attributes = [
    'status' => 'draft',
];

// Enum casting
protected $casts = [
    'status' => ExampleStatus::class,
];
```

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `'status' => 'draft'` hardcoded in `$attributes` | `'status' => ExampleStatus::DRAFT->value` | String drift from enum definition |
| `$model->status === 'active'` string comparison | `$model->status === ExampleStatus::ACTIVE` enum comparison | No type safety, no IDE support |
| `if ($model->status == 'active')` loose comparison | `if ($model->status === StatusEnum::ACTIVE)` strict | Loose comparison bypasses enum type |
| Status enum without `StatusEnum` contract | `implements StatusEnum` | No `canTransitionTo()`, no terminal detection |
| Transition check in Livewire `if ($model->status->canTransitionTo(...))` | Delegate to Action — Action checks and throws | Business logic in UI layer |
| `validTransitions()` with wildcard/default case | Exhaustive `match()` — every case listed | Missing transitions, incomplete FSM |
| `case DRAFT = 'Draft'` (CamelCase value) | `case DRAFT = 'draft'` (lowercase value) | Value convention violation |
| `case draft = 'draft'` (lowercase case name) | `case DRAFT = 'draft'` (UPPER_SNAKE name) | PHP enum naming convention |
| `return ['active', 'pending']` string array | `return [self::ACTIVE, self::PENDING]` enum array | No type safety, string drift |
| Business logic in Model `$this->isActive()` | Entity method `$entity->asState()->isActive()` | Anemic Domain Model |

---

## Quick References

- `entity-pattern.md` — Entity delegates to StatusEnum for state machine logic
- `action-pattern.md` — Actions enforce `canTransitionTo()` before persisting
- `data-pattern.md` — DTOs carry enum values as typed properties
- `modular-pattern.md` §6 Enum Patterns — architecture contracts
- [Wikipedia — State Pattern](https://en.wikipedia.org/wiki/State_pattern) — GoF behavioral delegation
- [GeeksforGeeks — State Design Pattern](https://www.geeksforgeeks.org/system-design/state-design-pattern/) — FSM implementation
- [Rust FAQ — Typestate Pattern](https://www.rustfaq.org/en/how-to-use-the-typestate-pattern-for-compile-time-state-machines/) — compile-time state machines
- [Wikipedia — Finite State Machine](https://en.wikipedia.org/wiki/Finite-state_machine) — mathematical model
- [PHP Enums — Manual](https://www.php.net/manual/en/language.enumerations.php) — PHP 8.1 string-backed enums
