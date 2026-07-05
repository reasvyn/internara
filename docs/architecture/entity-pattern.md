# Entity Pattern — Entity-Model Separation & Purity Rules

> **Last updated:** 2026-06-13 **Changes:** sync — fix broken link to architecture.md

## Description

Rules for Entity-Model separation: Entity purity, bridge pattern, business rule extraction, and
testing without database.

## Table of Contents

1. [Philosophy & Rationale](#1-philosophy--rationale)
2. [Entity Contract (BaseEntity)](#2-entity-contract-baseentity)
3. [Model Responsibilities](#3-model-responsibilities)
4. [Bridge Pattern (fromModel + as{Entity})](#4-bridge-pattern-frommodel--asentity)
5. [Entity Purity Rules](#5-entity-purity-rules)
6. [Pragmatic Framework Dependencies](#6-pragmatic-framework-dependencies)
7. [Immutability & with()](#7-immutability--with)
8. [equals() Value Semantics](#8-equals-value-semantics)
9. [Serialization (toArray, jsonSerialize)](#9-serialization-toarray-jsonserialize)
10. [Static Factory Methods](#10-static-factory-methods)
11. [Common Entity Patterns](#11-common-entity-patterns)

---

## 1. Philosophy & Rationale

Eloquent models in Laravel mix persistence (database queries, relationships, scopes) with business
logic (capability checks, status queries, permission gating). This coupling has two negative
effects:

1. **Business logic cannot be tested without a database** — every test requires factories,
   migrations, and database setup, making tests slow and brittle.
2. **Schema changes ripple through business logic** — renaming a column breaks inline rules
   scattered across Models, Actions, and Controllers.

The Entity-Model Separation pattern addresses this by splitting concerns into two class types:

| Concern        | Class  | Responsibilities                                                        |
| -------------- | ------ | ----------------------------------------------------------------------- |
| Data access    | Model  | Relationships, scopes, casts, attributes, factory config, entity bridge |
| Business rules | Entity | Capability checks, state queries, date logic, policy decisions          |

Entities are `final readonly` snapshots of state extracted from a Model at a point in time. They
answer business questions — _can this user log in?_, _is this registration window open?_, _can this
record be deleted?_ — without touching the database.

This separation follows the Single Responsibility Principle: a Model changes because the data model
changes; an Entity changes because the business requirement changes. These are different forces that
should not drive changes in the same class.

### Relationship to DTOs

| Aspect              | Entity (BaseEntity)            | DTO (BaseData)                           |
| ------------------- | ------------------------------ | ---------------------------------------- |
| Purpose             | Business rules, state queries  | Data transfer, input/output contracts    |
| Mutation            | Never                          | Never                                    |
| Framework deps      | Pragmatic — allowed            | Pragmatic — allowed                      |
| `fromModel()`       | Yes — persistence bridge       | Optional                                 |
| Property visibility | `private` (expose via methods) | `public`                                 |
| Used by             | Actions, Policies, Livewire    | Actions (input), Livewire (form mapping) |

---

## 2. Entity Contract (BaseEntity)

Every entity extends `BaseEntity`, an `abstract readonly class` implementing `JsonSerializable`. It
provides five built-in capabilities:

```php
abstract readonly class BaseEntity implements JsonSerializable
{
    // Mandatory factory — every entity must implement this
    abstract public static function fromModel(Model $model): static;

    // Optional: construct from an associative array (used by with())
    public static function fromArray(array $data): static;

    // Serialize to array (recursive for nested entities)
    public function toArray(): array;

    // JsonSerializable — delegates to toArray()
    public function jsonSerialize(): array;

    // Value equality comparison
    public function equals(self $other): bool;

    // Immutable "setter" — returns new instance with one property changed
    public function with(string $property, mixed $value): static;
}
```

The contract mandates only `fromModel()`. The remaining methods are inherited and provide consistent
serialization, comparison, and mutation semantics across all entities.

---

## 3. Model Responsibilities

Models are strictly **data access objects**. They define:

- **Relationships** — `hasMany()`, `belongsTo()`, `morphMany()`, etc.
- **Scopes** — `scopeActive()`, `scopeRecent()`, query-building helpers
- **Casts and attributes** — `#[Cast]`, `#[Appends]`, `#[Fillable]`, `$casts`
- **Media collections** — Spatie MediaLibrary `registerMediaCollections()`
- **Factory configuration** — `newFactory()`, `HasFactory`
- **Entity bridge** — `as{EntityName}()` accessor methods

### What Models Must NOT Contain

Business rules of any kind are forbidden on Models:

| ❌ Don't              | ✅ Do instead                                |
| --------------------- | -------------------------------------------- |
| `canLogin()`          | `$user->asRole()->allowsLogin()`             |
| `isActive()`          | `$entity->asState()->isActive()`             |
| `canBeDeleted()`      | `$entity->asState()->canBeDeleted()`         |
| `hasAvailableSlots()` | `$entity->asCapacity()->hasAvailableSlots()` |
| `isExpired()`         | `$entity->asPeriod()->isAfterWindow()`       |
| `canTransitionTo()`   | Delegate to the status enum directly         |

### Permitted Convenience Methods

Pure formatting helpers that only transform existing data without business logic are acceptable:

```php
public function initials(): string
{
    $words = explode(' ', trim($this->name));

    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1).substr(end($words), 0, 1));
    }

    return strtoupper(substr($this->name, 0, 2));
}
```

The litmus test: _"Would this method still make sense if I swapped the database for an API?"_ If yes
(relationships, scopes, casts), keep it on the Model. If no (business decisions), move it to the
Entity.

---

## 4. Bridge Pattern (fromModel + as{Entity})

The bridge connects the framework-persistent world (Models) with the pure business-rule world
(Entities). It has two halves:

### 4.1 Static Factory: `fromModel(Model $model): static`

Every entity implements this to extract its needed state from a Model:

```php
final readonly class SomeEntity extends BaseEntity
{
    public static function fromModel(Model $model): static
    {
        $related = $model->relationLoaded('related') ? $model->related : null;

        return new self(
            status: $model->status,
            startDate: $model->start_date,
            endDate: $model->end_date,
            relatedStart: $related?->start_date,
            relatedEnd: $related?->end_date,
        );
    }
}
```

The `fromModel()` method is the **only place** where Eloquent field access happens. It extracts
values, not the Model itself — the entity receives primitives, enums, and Carbon instances.

### 4.2 Named Accessor: `as{EntityName}(): EntityType`

Models expose entities via specific named methods that describe the **business role**:

```php
public function asSomeRole(): SomeEntity
{
    return SomeEntity::fromModel($this);
}
```

#### Naming Convention

The accessor name describes the role, not the class. A model may expose **multiple entities** for
different business roles — for example, one entity for registration window logic and another for
deletion safety.

#### Anti-Pattern: Generic Accessor

```php
// ❌ Wrong — generic name reveals nothing
public function entity(): SomeEntity

// ✅ Correct — specific name communicates the role
public function asSomeRole(): SomeEntity
```

### 4.3 Usage in Callers

Actions, Policies, and Livewire components access entities through the model:

```php
// Before (inline business logic in an Action)
if ($entity->status === 'active'
    && $entity->start_date <= now()
    && $entity->end_date >= now()
) { ... }

// After (centralized business rule)
if ($entity->asSomeRole()->isActive()) { ... }
```

---

## 5. Entity Purity Rules

Entities follow strict purity rules to remain testable and predictable:

### 5.1 `final readonly` Class

```php
final readonly class SomeEntity extends BaseEntity
```

- **`final`** — no inheritance. Every entity is a leaf class. Composition over inheritance.
- **`readonly`** — all properties are implicitly readonly. State is set once in the constructor and
  never changes. PHP 8.4 enforces this at the language level.

### 5.2 Private Typed Properties

All state is passed through the constructor as `private` typed properties. Expose via getter
methods, never public properties:

```php
final readonly class SomeEntity extends BaseEntity
{
    public function __construct(
        private ?string $status,
        private ?Carbon $startDate,
        private ?Carbon $endDate,
        private bool $hasRelated,
    ) {}

    public function isActive(): bool { ... }
    public function canBeApproved(): bool { ... }
}
```

Simple getters for entity-owned state are acceptable:

```php
public function status(): SomeStatus
{
    return $this->status;
}
```

### 5.3 Zero I/O, Zero Persistence

Entities must never:

- Execute database queries
- Make HTTP requests
- Write to files or caches
- Dispatch events or notifications
- Access the service container
- Use facades (`\DB`, `\Cache`, `\Event`, etc.)

The single allowed framework import is `Illuminate\Database\Eloquent\Model` — and only in the
`fromModel()` parameter type hint.

### 5.4 Entity Method Contracts

Entity methods return **business answers**, not raw data:

| Return Type | Examples                                                                                |
| ----------- | --------------------------------------------------------------------------------------- |
| `bool`      | `canLogin()`, `isTerminal()`, `requiresAction()`, `canTransitionTo()`, `canBeDeleted()` |
| `int`       | `daysRemaining()`, `totalDuration()`, `availableSlots()`                                |
| `string`    | `scoreBand()` — computed business categorization                                        |
| Enum        | `status()` — entity-owned typed state                                                   |

Methods like `toArray()` and `jsonSerialize()` are exempt — they are serialization concerns provided
by the base class.

---

## 6. Pragmatic Framework Dependencies

The project explicitly chooses **pragmatism over purity**. Framework dependencies are allowed in
entities when they serve business logic without introducing testability costs. `Carbon\Carbon` is
permitted for date math, `Illuminate\Database\Eloquent\Model` for `fromModel()` parameter hints, and
enum types for status machine logic. All other framework access (Eloquent queries, facades, service
container, HTTP, file system) remains off-limits.

---

## 7. Immutability & with()

Entities are **immutable** — once constructed, their state never changes. This eliminates entire
classes of bugs (accidental mutation) and makes business rules predictable: given the same state, an
entity method always returns the same answer.

### The `with()` Method

When you need a modified copy of an entity, use the `with()` method inherited from `BaseEntity`:

```php
public function with(string $property, mixed $value): static
{
    $data = $this->toArray();
    $data[$property] = $value;

    return static::fromArray($data);
}
```

This returns a **new instance** with the specified property changed:

```php
$current = new SomeEntity(
    status: 'pending',
    startDate: $startDate,
    endDate: $endDate,
    hasRelated: false,
);

$updated = $current->with('hasRelated', true);

$current->hasRelated; // false — unchanged
$updated->hasRelated; // true — new instance
```

### When to Use `with()`

Use `with()` in tests when you need an entity with slightly different state for edge-case testing.
Avoid using it in production code — entities are snapshots, and modifying them mid-workflow may
indicate that the `fromModel()` bridge should extract more state or that a new entity type is
needed.

---

## 8. equals() Value Semantics

`BaseEntity` provides value equality via `equals()`:

```php
public function equals(self $other): bool
{
    return $this === $other || $this->toArray() === $other->toArray();
}
```

Two entities are equal if:

1. They are the same object instance (`===`), **or**
2. Their serialized arrays are identical

This enables comparison without worrying about object identity:

```php
$a = new SomeEntity(status: Status::ACTIVE, isLocked: false, setupRequired: false);

$b = new SomeEntity(status: Status::ACTIVE, isLocked: false, setupRequired: false);

$a->equals($b); // true — same values
$a === $b; // false — different instances
```

---

## 9. Serialization (toArray, jsonSerialize)

### toArray()

The base class provides recursive array serialization. It handles nested entities,
`JsonSerializable` sub-objects (like Carbon), and arrays recursively. All private constructor
properties are included in the output using reflection.

### jsonSerialize()

Delegates to `toArray()`, enabling direct use with `json_encode()`:

```php
return response()->json($someEntity); // Works because of JsonSerializable
```

---

## 10. Static Factory Methods

Beyond `fromModel()`, entities may expose additional static factories for specialized construction
patterns.

### Settings-Backed Entities

Some entities are not backed by a single model row but by the application's settings store. These
entities provide a `get()` static factory that reads from settings rather than a model:

```php
final readonly class SettingsBackedEntity extends BaseEntity
{
    public static function fromModel(Model $model): static
    {
        return self::get(); // Ignores the model parameter
    }

    public static function get(): static
    {
        // Reads from application settings store
    }
}
```

### Generation Factories

Entities that represent tokens or generated values may have creation factories. These static
factories perform persistence as a deliberate exception — the entity acts as both a factory and a
value object.

### User-Specific Factories

Some entities expose factories scoped to a specific domain object:

```php
public static function forUser(User $user): self
{
    return self::fromModel($user);
}
```

---

## 11. Common Entity Patterns

### 11.1 State Entity Pattern

The most common pattern. Entity holds status + related boolean flags and provides capability checks
such as `canBeDeleted()`, `canBeApproved()`, `canBeEdited()`.

### 11.2 Period Entity Pattern

Entity holds date ranges and answers temporal queries such as `isAcceptingRegistrations()`,
`isBeforeWindow()`, `isWithinPeriod()`.

### 11.3 Capacity Entity Pattern

Entity holds numeric constraints and answers availability queries such as `isFull()`,
`availableSlots()`, `hasAvailableSlots()`.

### 11.4 Business Role Pattern

Entity represents a user's business role with role-specific rules such as `allowsLogin()`,
`isSuspended()`, `requiresSetup()`, `canTransitionTo()`.

### 11.5 Settings-Backed Entity Pattern

Entity is not backed by a single model row but by the application settings store. `fromModel()`
delegates to `get()` which reads from a settings facade.

### 11.6 Token Entity Pattern

Entity represents a generated token with validation logic such as `isTokenExpired()`,
`hasExceededMaxAttempts()`, and generation factories.

### 11.7 Delegation Entity Pattern

Entity is a thin wrapper that delegates to an enum's methods. Constructor holds a single status
enum; methods delegate to the enum for answers like `isVerified()`, `canBeEdited()`.
