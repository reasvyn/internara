# Entity Pattern — Domain Entities, Rich Domain Model & Purity Rules

## Description

This pattern governs how Internara separates **domain business rules** (Entity) from **persistence concerns** (Model). It synthesizes global industry standards — **Domain-Driven Design Entity** (Eric Evans), **Rich Domain Model vs Anemic Domain Model** (Martin Fowler), **Aggregates & Consistency Boundaries** (Vaughn Vernon), **Value Object** (Fowler/Evans), **Persistence Ignorance** (Vladimir Khorikov) — into enforceable rules tied to Internara's stack: `final readonly` Entities extending `BaseEntity`, Eloquent Models extending `BaseModel`, and the `fromModel()`/`as{Entity}()` Bridge Pattern.

Without it, business logic scatters across Models, Livewire components, and Actions — the **Anemic Domain Model** anti-pattern (Fowler): objects with state but no behavior, where all logic lives in procedural Services. With it, Entities are pure, testable without a database, and changing a business rule never touches persistence code.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Entity purity — zero I/O, zero framework dependencies.** Entities MUST NOT execute database queries, make HTTP requests, write to files/caches, dispatch events, access the service container, or use facades. The single allowed framework import is `Illuminate\Database\Eloquent\Model` — and only in the `fromModel()` parameter type hint. This enforces **Persistence Ignorance** (Khorikov) and **Domain Model Purity** — the domain layer has no out-of-process dependencies.

2. **`final readonly` — no inheritance, immutable state.** Every entity MUST be `final readonly class {Name} extends BaseEntity`. `final` prevents inheritance (composition over inheritance). `readonly` enforces immutability at the language level — state is set once in the constructor and never changes. This mirrors the **Value Object** concept (Evans/Fowler): "defined solely by the state of its attributes, with no conceptual identity."

3. **Private typed properties, exposed via methods.** All entity state is `private` constructor-promoted properties. Expose via getter methods, never public properties. Business rules live as methods on the Entity (`canLogin()`, `isActive()`, `canBeDeleted()`). This makes the Entity a **Rich Domain Model** (Fowler) — objects containing both state and behavior, not an Anemic Domain Model.

4. **`fromModel()` is the only Eloquent access point.** The `fromModel(Model $model): static` factory method is the **only place** where Eloquent field access happens. It extracts values (primitives, enums, Carbon) — never the Model itself. This is the **Bridge Pattern** — connecting the persistence world (Eloquent) with the pure business-rule world (Entity).

5. **Business rules live on Entities, not Models, not Actions, not Livewire.** Capability checks (`canLogin()`, `canBeDeleted()`), state queries (`isActive()`, `isExpired()`), date logic (`isWithinPeriod()`), and policy decisions (`allowsLogin()`) MUST live on Entity methods. Models are data access objects. Actions orchestrate. Livewire renders. Only Entities decide.

6. **No business rules on Models.** Models define relationships, scopes, casts, attributes, media collections, factory config, and the entity bridge accessor. They MUST NOT contain `canLogin()`, `isActive()`, `canBeDeleted()`, `hasAvailableSlots()`, `isExpired()`, `canTransitionTo()`, or any business decision method. This is the **SRP** (Robert C. Martin): a Model changes because the data model changes; an Entity changes because the business requirement changes.

7. **Multiple entities per model are normal.** A single Model may expose multiple entities for different business roles — e.g., `asRegistrationState()`, `asCapacityState()`, `asPeriodState()`. Each entity answers a specific set of business questions. Do not force one Entity to carry all business rules.

---

## How to Apply

### 1. Entity-Model Separation — Rich Domain Model (Fowler)

The Anemic Domain Model anti-pattern (Fowler, 2003): "domain objects contain state but no behavior — they are bags of getters and setters. All business logic is extracted into procedural Service classes. This is the exact opposite of OOP."

| Concern | Entity (Rich Domain Model) | Model (Active Record / Data Access) |
|---------|--------------------------|-------------------------------------|
| **Purpose** | Business rules, state queries, capability checks | Persistence, relationships, scopes, casts |
| **Change trigger** | Business requirement changes | Data model / schema changes |
| **Testability** | Pure unit tests — no database, no migrations | Feature tests — requires DB, factories |
| **Framework deps** | `final readonly` — no Eloquent queries, no facades | Eloquent: queries, relationships, scopes |
| **Relationships** | Receives pre-loaded data via `fromModel()` | Defines `hasMany()`, `belongsTo()`, etc. |

### 2. The Entity Contract (BaseEntity)

Every entity extends `BaseEntity`, an `abstract readonly class` implementing `JsonSerializable`:

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

    // Value equality comparison (Evans/Fowler: structural comparison)
    public function equals(self $other): bool;

    // Immutable "setter" — returns new instance with one property changed
    public function with(string $property, mixed $value): static;
}
```

### 3. Bridge Pattern — fromModel + as{Entity}

The Bridge connects the framework-persistent world (Models) with the pure business-rule world (Entities). Two halves:

**Static Factory: `fromModel(Model $model): static`**

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

**Named Accessor: `as{EntityName}(): EntityType`**

```php
// ✅ Correct — specific name communicates the role
public function asSomeRole(): SomeEntity
{
    return SomeEntity::fromModel($this);
}

// ❌ Wrong — generic name reveals nothing
public function entity(): SomeEntity
```

The accessor name describes the **business role**, not the class. A Model may expose **multiple entities** for different business roles.

### 4. What Models MUST NOT Contain

| Don't | Do Instead |
|-------|-----------|
| `canLogin()` | `$user->asRole()->allowsLogin()` |
| `isActive()` | `$entity->asState()->isActive()` |
| `canBeDeleted()` | `$entity->asState()->canBeDeleted()` |
| `hasAvailableSlots()` | `$entity->asCapacity()->hasAvailableSlots()` |
| `isExpired()` | `$entity->asPeriod()->isAfterWindow()` |
| `canTransitionTo()` | Delegate to the status enum directly |

**Litmus test:** _"Would this method still make sense if I swapped the database for an API?"_ If yes (relationships, scopes, casts), keep it on the Model. If no (business decisions), move it to the Entity.

### 5. Immutability & Value Semantics — Value Object (Evans/Fowler)

Entities are **immutable** — once constructed, their state never changes. This eliminates entire classes of bugs (accidental mutation) and makes business rules predictable: given the same state, an entity method always returns the same answer.

**`with()` — Immutable Copy:** When you need a modified copy, use `with()` which returns a **new instance**:

```php
$current = new SomeEntity(status: 'pending', startDate: $startDate, endDate: $endDate, hasRelated: false);
$updated = $current->with('hasRelated', true);

$current->hasRelated; // false — unchanged
$updated->hasRelated; // true — new instance
```

**`equals()` — Value Equality:** Two entities are equal if they are the same instance OR their serialized arrays are identical (structural comparison, per Fowler's Value Object).

### 6. Entity Method Contracts

Entity methods return **business answers**, not raw data:

| Return Type | Examples |
|------------|---------|
| `bool` | `canLogin()`, `isTerminal()`, `requiresAction()`, `canTransitionTo()`, `canBeDeleted()` |
| `int` | `daysRemaining()`, `totalDuration()`, `availableSlots()` |
| `string` | `scoreBand()` — computed business categorization |
| Enum | `status()` — entity-owned typed state |

### 7. Pragmatic Framework Dependencies

The project explicitly chooses **pragmatism over purity** (Khorikov's DDD Trilemma — you cannot have all three of encapsulation, purity, and performance). `Carbon\Carbon` is permitted for date math, `Illuminate\Database\Eloquent\Model` for `fromModel()` parameter hints, and enum types for status machine logic. All other framework access (Eloquent queries, facades, service container, HTTP, file system) remains off-limits.

### 8. Common Entity Patterns

| Pattern | Purpose | Example |
|---------|---------|---------|
| **State Entity** | Status + boolean capability checks | `canBeDeleted()`, `canBeApproved()` |
| **Period Entity** | Date ranges, temporal queries | `isAcceptingRegistrations()`, `isWithinPeriod()` |
| **Capacity Entity** | Numeric constraints, availability | `isFull()`, `availableSlots()` |
| **Business Role** | User role with role-specific rules | `allowsLogin()`, `isSuspended()` |
| **Settings-Backed** | Entity from settings store, not a model | `get()` reads from Settings facade |
| **Token Entity** | Generated token with validation | `isTokenExpired()`, `hasExceededMaxAttempts()` |
| **Delegation Entity** | Thin wrapper delegating to enum | Constructor holds status enum, methods delegate |

### 9. Testing Entities — Pure Unit Tests

Entities are testable without database, migrations, or factories:

```php
test('active entity can be approved', function () {
    $entity = new SomeEntity(
        status: Status::ACTIVE,
        startDate: now()->subDays(10),
        endDate: now()->addDays(20),
    );

    expect($entity->canBeApproved())->toBeTrue();
});
```

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `$model->canLogin()` / `$model->isActive()` on Eloquent Model | `$model->asRole()->allowsLogin()` / `$entity->asState()->isActive()` | Anemic Domain Model (Fowler) — business logic on data access object |
| Entity with `DB::query()` / `Cache::get()` / `Http::get()` | Pure entity — zero I/O, zero framework deps | Domain Model Purity violation (Khorikov) |
| `public readonly class` (no `final`) | `final readonly class` | Allows inheritance, breaks Value Object semantics |
| Entity with public properties `public string $status` | Private properties + getter methods | Breaks encapsulation, no control over access |
| Generic accessor `$model->entity()` | Named accessor `$model->asSomeRole()` | Role-specific naming missing |
| Business logic in Livewire `mount()` without Entity | Entity method called from Livewire/Action | Business logic leaked to UI layer |
| `if ($model->status === 'active' && $model->start_date <= now())` inline | `$model->asState()->isActive()` | Inline business rule, no Entity extraction |
| Entity with `new \Carbon\Carbon()` (framework dep beyond allowed) | Accept `Carbon` as parameter, use in methods | Pragmatic deps boundary exceeded |
| Single Entity carrying all business rules for a Model | Multiple entities per business role (`asState()`, `asCapacity()`, `asPeriod()`) | SRP violation — one Entity, many responsibilities |
| `$this->save()` or `$this->delete()` inside Entity method | Entity returns domain event, Action persists | Entity does persistence (C5 violation) |

---

## Quick References

- `action-pattern.md` — Actions call Entity methods, throw `RejectedException` on violation
- `modular-pattern.md` §1.6 SRP & Modularity Rules, §5 Entity-Model Separation — architecture contracts
- `enum-pattern.md` — Entity delegates to StatusEnum for state machine logic
- `model-pattern.md` — Eloquent Model contract, `#[Fillable]`, relationships, scopes
- `policy-pattern.md` — Policies call Entity methods for authorization decisions
- [Eric Evans — Domain-Driven Design](https://www.domainlanguage.com/ddd/) — Entity identity, lifecycle, Value Object
- [Martin Fowler — Anemic Domain Model](https://martinfowler.com/bliki/AnemicDomainModel.html) — Rich vs Anemic
- [Martin Fowler — Domain Model (PoEAA)](https://martinfowler.com/eaaCatalog/domainModel.html) — model that puts business logic in objects
- [Vaughn Vernon — Aggregates](https://www.dddcommunity.org/library/vernon_2011/) — consistency boundaries, small aggregates
- [Martin Fowler — Value Object](https://martinfowler.com/bliki/ValueObject.html) — identity-less, immutable, structural equality
- [Vladimir Khorikov — Domain Model Purity](https://khorikov.org/posts/2021-08-02-purity-specification-pattern/) — Persistence Ignorance, DDD Trilemma
- [Microsoft — DDD-Oriented Microservice](https://learn.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/ddd-oriented-microservice) — POCO/POPO domain entities
