# Enum Pattern Reference — LabelEnum, StatusEnum & State Machine Patterns

> **Last updated:** 2026-06-13
> **Changes:** sync — fix shared enum path (app/Enums → app/Core/Enums)
## Description

Enum contracts (LabelEnum, StatusEnum), state machine patterns, case naming conventions, business logic methods, and testing strategies.

## 1. Enum Architecture Overview

All enums in Internara are PHP 8 `string`-backed enums. Every enum **must** implement
`LabelEnum`. State machine enums additionally implement `StatusEnum`. UI badge enums optionally
implement `ColorableEnum`.

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

### Three-Tier Contract Hierarchy

| Contract | Mandate | Purpose |
|----------|---------|---------|
| `LabelEnum` | All enums | Human-readable label via `__()` translation |
| `StatusEnum` | Lifecycle enums | State machine transitions, terminal detection |
| `ColorableEnum` | UI badge enums | Tailwind/UI color per status |

### File Location Rules

- **Module-specific enum** → `app/{Module}/{SubModule}/Enums/{Name}.php`
- **Cross-submodule enum** → `app/{Module}/Enums/{Name}.php` (inside a module's root `Enums/`)
- **Shared enum** → `app/Core/Enums/{Name}.php` (used by 2+ modules)
- **Test** → `tests/Unit/{Module}/{SubModule}/Enums/{Name}Test.php`

---

## 2. LabelEnum Contract

Every enum in the codebase implements `LabelEnum` (`app/Core/Contracts/LabelEnum.php`):

```php
interface LabelEnum
{
    public function label(): string;
}
```

All `label()` implementations delegate to `__()` for i18n. Two translation styles are used:

### Per-case `match()` (most common)

```php
public function label(): string
{
    return match ($this) {
        self::STATE_A => __('State A'),
        self::STATE_B => __('State B'),
        self::STATE_C => __('State C'),
    };
}
```

### Dynamic key from value

```php
public function label(): string
{
    return __("module.enum.{$this->value}");
}
```

Used when the enum has many cases or when translations are maintained in structured lang files.

### Plain value (no translation needed)

For enums whose label is the raw value (e.g. blood type):

```php
public function label(): string
{
    return $this->value;
}
```

---

## 3. StatusEnum Contract

State machine enums implement `StatusEnum` (`app/Core/Contracts/StatusEnum.php`), which extends
`LabelEnum`:

```php
interface StatusEnum extends LabelEnum
{
    public function isTerminal(): bool;
    public function canTransitionTo(self $target): bool;
    public function validTransitions(): array;
}
```

### Common `canTransitionTo` Implementation

Almost all status enums share the same guard implementation:

```php
public function canTransitionTo(StatusEnum $target): bool
{
    if (! ($target instanceof self)) {
        return false;
    }

    return in_array($target, $this->validTransitions(), true);
}
```

This pattern:
1. Rejects cross-enum transitions (type safety)
2. Delegates valid targets to `validTransitions()`
3. Uses strict `in_array()` — no implicit enum-to-string coercion

### Optional: Extra Terminal Guard

Some enums add an explicit terminal check before delegating to valid transitions:

```php
public function canTransitionTo(StatusEnum $target): bool
{
    if (! ($target instanceof self)) {
        return false;
    }
    if ($this->isTerminal()) {
        return false;
    }

    return in_array($target, $this->validTransitions(), true);
}
```

This is a belt-and-suspenders approach — `validTransitions()` already returns `[]` for terminal
states, but the explicit guard makes the intent clearer.

---

## 4. ColorableEnum Contract

Optional contract for enums displayed as UI badges:

```php
interface ColorableEnum
{
    public function color(): string;
}
```

Returns a Tailwind color keyword used by UI components for badge rendering. Supported colors:
`primary`, `success`, `warning`, `error`, `info`.

---

## 5. Case Convention

### `UPPER_SNAKE` case names, lowercase string values

```php
enum ExampleStatus: string implements StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

### Value Convention Table

| Convention | Rule | Example |
|------------|------|---------|
| Case name | `UPPER_SNAKE` | `REVISION_REQUIRED` |
| Backing value | lowercase | `'revision_required'` |
| Multi-word value | `snake_case` | `'multi_word_value'` |

### Edge Cases

```php
// Short values (single character)
case A = 'a';
case B = 'b';

// Values that differ from case name
case SUPER_ADMIN = 'superadmin';     // condensed, not 'super_admin'
```

---

## 6. Business Logic on Enums

Business logic methods live **directly on the enum class**. This is a deliberate choice — enums are
the natural home for behavior that depends solely on the enum's value.

### Domain Query Methods

Boolean methods that answer questions about the current state. Prefixed with `is`, `has`, `can`,
`requires`, or `allows`:

| Prefix | Semantics | Example |
|--------|-----------|---------|
| `is` | Boolean state query | `isTerminal()`, `isActive()` |
| `has` | Feature/attribute presence | `hasProperty()` |
| `can` | Permission or ability | `canTransitionTo()` |
| `requires` | Prerequisite needed | `requiresAttachment()` |
| `allows` | Permission granted | `allowsLogin()` |

---

## 7. State Machine Patterns

State machines fall into several generic pattern categories. Every status enum fits one of these.

### Revision Loop (Iterative Review)

A draft state feeds into a review cycle where submissions can be returned for revision. Used by
workflow entities with an iterative review cycle. Typical states: a draft state, a submitted state,
a revision-required state (returning to draft), and one or more terminal states.

### Approval Pipeline

A single pending state branches into two terminal outcomes: approval and rejection. Used for any
request that must be reviewed and either accepted or denied. All enums using this pattern share
identical transition logic — only the labels differ.

### Linear Progression with Cancellation

States move forward through defined stages, with a cancellation escape at each step. Each forward
state can transition to the next stage or to a terminal cancelled state.

### Incident Lifecycle

A reported state moves through investigation to resolution and finally closure, with the option
to skip directly from reporting to resolution.

### Two-State (Binary)

A single forward transition from an initial state to a single terminal state, with no other
branches.

### All Terminal (No Transitions)

Every state is terminal — records are recorded directly with their final classification and never
transition.

### Complex Lifecycle

Multiple forward stages with a cancellation escape at several points. A non-terminal verified or
review state may exist between submission and final completion.

### User Account Lifecycle (Most Complex)

Multiple parallel paths through activation, verification, restriction, suspension, and archival,
with cycles (e.g. suspension → reactivation) and multiple terminal states.

### Transition Canonical Form

Every `StatusEnum` follows this exact structure:

```php
public function validTransitions(): array
{
    return match ($this) {
        self::STATE_A => [self::STATE_B, self::STATE_C],
        self::STATE_B => [self::STATE_D],
        self::STATE_C => [],
        self::STATE_D => [],
    };
}
```

Rules:
- Terminal states return `[]` (empty array, no further transitions).
- All valid destinations are listed explicitly — no wildcards.
- `match()` is exhaustive: every case must appear.
- Return type `list<static>` — the list of valid target enum cases.

### Guarding Transitions in Actions

Command Actions enforce `canTransitionTo()` before persisting:

```php
class SubmitAction extends BaseAction
{
    public function execute(Model $entry, array $data): Model
    {
        $target = TargetStatus::TARGET_STATE;

        if (! $entry->status->canTransitionTo($target)) {
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

---

## 8. Model Defaults

Model `$attributes` must use `->value`, never hardcoded strings:

```php
// ✅ Correct
protected $attributes = [
    'status' => ExampleStatus::DRAFT->value,
];

// ❌ Wrong — hardcoded string drifts from enum
protected $attributes = [
    'status' => 'draft',
];
```

Factory definitions follow the same rule:

```php
public function definition(): array
{
    return [
        'status' => ExampleStatus::DRAFT->value,
    ];
}

public function published(): static
{
    return $this->state(
        fn (array $attrs) => ['status' => ExampleStatus::PUBLISHED->value],
    );
}
```

### Enum Casting on Models

Models cast status columns to enum via `$casts`:

```php
protected $casts = [
    'status' => ExampleStatus::class,
];
```

This allows direct enum comparison on the model:

```php
$entry->status === ExampleStatus::SUBMITTED
$entry->status->canTransitionTo(ExampleStatus::VERIFIED)
$entry->status->label()
```
