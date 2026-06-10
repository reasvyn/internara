# Entity-Model Separation Pattern

> **Last updated:** 2026-06-10
>
> **Audience:** Architects and developers working with business logic in the Internara codebase.
> **Prerequisites:** Familiarity with [ADR-004: Entity-Model Separation](../adr/adr-entity-model-separation.md)
> and the [12-Layer Architecture](architecture.md).

---

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
11. [Entity Extraction Workflow](#11-entity-extraction-workflow)
12. [Testing Entities (No DB)](#12-testing-entities-no-db)
13. [Common Entity Patterns in the Codebase](#13-common-entity-patterns-in-the-codebase)

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

| Concern | Class | Responsibilities |
|---------|-------|-----------------|
| Data access | Model | Relationships, scopes, casts, attributes, factory config, entity bridge |
| Business rules | Entity | Capability checks, state queries, date logic, policy decisions |

Entities are `final readonly` snapshots of state extracted from a Model at a point in time. They
answer business questions — *can this user log in?*, *is this registration window open?*, *can this
record be deleted?* — without touching the database.

This separation follows the Single Responsibility Principle: a Model changes because the data model
changes; an Entity changes because the business requirement changes. These are different forces that
should not drive changes in the same class.

### Relationship to DTOs

| Aspect | Entity (BaseEntity) | DTO (BaseData) |
|--------|---------------------|----------------|
| Purpose | Business rules, state queries | Data transfer, input/output contracts |
| Mutation | Never | Never |
| Framework deps | Pragmatic — allowed | Pragmatic — allowed |
| `fromModel()` | Yes — persistence bridge | Optional |
| Property visibility | `private` (expose via methods) | `public` |
| Used by | Actions, Policies, Livewire | Actions (input), Livewire (form mapping) |

---

## 2. Entity Contract (BaseEntity)

Every entity extends `App\Core\Entities\BaseEntity`, which is an `abstract readonly class`
implementing `JsonSerializable`. It provides five built-in capabilities:

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

The contract mandates only `fromModel()`. The remaining methods are inherited and provide
consistent serialization, comparison, and mutation semantics across all entities.

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

| ❌ Don't | ✅ Do instead |
|----------|--------------|
| `canLogin()` | `$user->asApprentice()->allowsLogin()` |
| `isActive()` | `$registration->asRegistrationState()->isActive()` |
| `canBeDeleted()` | `$year->asAcademicYearState()->canBeDeleted()` |
| `hasAvailableSlots()` | `$placement->asPlacementCapacity()->hasAvailableSlots()` |
| `isExpired()` | `$period->asInternshipPeriod()->isAfterRegistrationWindow()` |
| `canTransitionTo()` | Delegate to the status enum directly |

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

The litmus test: *"Would this method still make sense if I swapped the database for an API?"* If yes
(relationships, scopes, casts), keep it on the Model. If no (business decisions), move it to the
Entity.

---

## 4. Bridge Pattern (fromModel + as{Entity})

The bridge connects the framework-persistent world (Models) with the pure business-rule world
(Entities). It has two halves:

### 4.1 Static Factory: `fromModel(Model $model): static`

Every entity implements this to extract its needed state from a Model:

```php
// app/Program/Internship/Entities/InternshipPeriod.php
final readonly class InternshipPeriod extends BaseEntity
{
    public static function fromModel(Model $model): static
    {
        $academicYear = $model->relationLoaded('academicYear')
            ? $model->academicYear
            : null;

        return new self(
            status: $model->status,
            registrationStartDate: $model->registration_start_date,
            registrationEndDate: $model->registration_end_date,
            academicYearStart: $academicYear?->start_date,
            academicYearEnd: $academicYear?->end_date,
        );
    }
}
```

The `fromModel()` method is the **only place** where Eloquent field access happens. It extracts
values, not the Model itself — the entity receives primitives, enums, and Carbon instances.

### 4.2 Named Accessor: `as{EntityName}(): EntityType`

Models expose entities via specific named methods that describe the **business role**:

```php
// app/Program/Internship/Models/Internship.php
public function asInternshipPeriod(): InternshipPeriod
{
    return InternshipPeriod::fromModel($this);
}
```

#### Naming Convention

The accessor name describes the role, not the class:

| Model | Role | Accessor | Entity |
|-------|------|----------|--------|
| `User` | Apprentice | `asApprentice()` | `Apprentice` |
| `Internship` | Period | `asInternshipPeriod()` | `InternshipPeriod` |
| `Internship` | State | `asInternshipState()` | `InternshipState` |
| `Placement` | Capacity | `asPlacementCapacity()` | `PlacementCapacity` |
| `Placement` | State | `asPlacementState()` | `PlacementState` |
| `Registration` | State | `asRegistrationState()` | `RegistrationState` |
| `AcademicYear` | State | `asAcademicYearState()` | `AcademicYearState` |

A model may expose **multiple entities** for different business roles — `Internship` exposes both
`asInternshipPeriod()` (registration window logic) and `asInternshipState()` (deletion safety).

#### Anti-Pattern: Generic Accessor

```php
// ❌ Wrong — generic name reveals nothing
public function entity(): InternshipPeriod

// ✅ Correct — specific name communicates the role
public function asInternshipPeriod(): InternshipPeriod
```

### 4.3 Usage in Callers

Actions, Policies, and Livewire components access entities through the model:

```php
// Before (inline business logic in an Action)
if ($internship->status === 'active'
    && $internship->registration_start_date <= now()
    && $internship->registration_end_date >= now()
) { ... }

// After (centralized business rule)
if ($internship->asInternshipPeriod()->isAcceptingRegistrations()) { ... }
```

---

## 5. Entity Purity Rules

Entities follow strict purity rules to remain testable and predictable:

### 5.1 `final readonly` Class

```php
final readonly class Apprentice extends BaseEntity
```

- **`final`** — no inheritance. Every entity is a leaf class. Composition over inheritance.
- **`readonly`** — all properties are implicitly readonly. State is set once in the constructor and
  never changes. PHP 8.4 enforces this at the language level.

### 5.2 Private Typed Properties

All state is passed through the constructor as `private` typed properties. Expose via getter
methods, never public properties:

```php
final readonly class RegistrationState extends BaseEntity
{
    public function __construct(
        private ?string $status,
        private ?Carbon $startDate,
        private ?Carbon $endDate,
        private bool $hasPlacement,
    ) {}

    public function isActive(): bool { ... }
    public function canBeApproved(): bool { ... }
}
```

Simple getters for entity-owned state are acceptable:

```php
public function status(): AccountStatus
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

| Return Type | Examples |
|-------------|----------|
| `bool` | `canLogin()`, `isTerminal()`, `requiresAction()`, `canTransitionTo()`, `canBeDeleted()` |
| `int` | `daysRemaining()`, `totalDuration()`, `availableSlots()` |
| `string` | `scoreBand()` — computed business categorization |
| Enum | `status()` — entity-owned typed state |

Methods like `toArray()` and `jsonSerialize()` are exempt — they are serialization concerns
provided by the base class.

---

## 6. Pragmatic Framework Dependencies

The project explicitly chooses **pragmatism over purity**. Framework dependencies are allowed in
entities when they serve business logic without introducing testability costs:

### Allowed Dependencies

| Dependency | Why | Example |
|------------|-----|---------|
| `Carbon\Carbon` | Date math is a business concern | `$now->between($start, $end)` |
| `Illuminate\Database\Eloquent\Model` | `fromModel()` parameter | `public static function fromModel(Model $model): static` |
| Enum types | Status machine logic | `AccountStatus::SUSPENDED` |

### Usage Example

```php
final readonly class InternshipPeriod extends BaseEntity
{
    public function isAcceptingRegistrations(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        if (! $this->status?->isAcceptingRegistrations()) {
            return false;
        }

        if ($this->registrationStartDate !== null && $now->lt($this->registrationStartDate)) {
            return false;
        }

        if ($this->registrationEndDate !== null && $now->gt($this->registrationEndDate)) {
            return false;
        }

        return true;
    }
}
```

### What Remains Off-Limits

- Eloquent queries (`Model::where()`, `->save()`, `->update()`)
- Facades (`\DB::`, `\Cache::`, `\Event::`)
- Service Container (`app()`, `resolve()`)
- HTTP/request classes
- File system or storage operations

---

## 7. Immutability & with()

Entities are **immutable** — once constructed, their state never changes. This eliminates entire
classes of bugs (accidental mutation) and makes business rules predictable: given the same state,
an entity method always returns the same answer.

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
$current = new RegistrationState(
    status: 'pending',
    startDate: $startDate,
    endDate: $endDate,
    hasPlacement: false,
);

$updated = $current->with('hasPlacement', true);

$current->hasPlacement; // false — unchanged
$updated->hasPlacement;  // true — new instance
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
$a = new Apprentice(
    status: AccountStatus::ACTIVE,
    isLocked: false,
    setupRequired: false,
);

$b = new Apprentice(
    status: AccountStatus::ACTIVE,
    isLocked: false,
    setupRequired: false,
);

$a->equals($b); // true — same values
$a === $b;      // false — different instances
```

---

## 9. Serialization (toArray, jsonSerialize)

### toArray()

The base class provides recursive array serialization:

```php
public function toArray(): array
{
    $data = [];

    foreach (get_object_vars($this) as $key => $value) {
        $data[$key] = match (true) {
            $value instanceof self => $value->toArray(),
            $value instanceof JsonSerializable => $value->jsonSerialize(),
            is_array($value) => array_map(
                fn (mixed $item) => $item instanceof self ? $item->toArray() : $item,
                $value,
            ),
            default => $value,
        };
    }

    return $data;
}
```

This handles nested entities, `JsonSerializable` sub-objects (like Carbon), and arrays
recursively. For example, `InternshipPeriod` serializes to:

```php
[
    'status' => 'active',
    'registrationStartDate' => Carbon('2026-09-01'),
    'registrationEndDate' => Carbon('2026-10-15'),
    'academicYearStart' => Carbon('2026-01-01'),
    'academicYearEnd' => Carbon('2026-12-31'),
]
```

### jsonSerialize()

Delegates to `toArray()`, enabling direct use with `json_encode()`:

```php
$period = $internship->asInternshipPeriod();
return response()->json($period); // Works because of JsonSerializable
```

---

## 10. Static Factory Methods

Beyond `fromModel()`, entities may expose additional static factories for specialized construction
patterns.

### 10.1 Settings-Backed Entities

Some entities are not backed by a single model row but by the application's settings store. These
entities provide a `get()` static factory:

```php
final readonly class SetupEntity extends BaseEntity
{
    public static function fromModel(Model $model): static
    {
        return self::get(); // Ignores the model parameter
    }

    public static function get(): static
    {
        $values = Settings::get([
            'setup.is_installed',
            'setup.install_token',
            'setup.token_expires_at',
            'setup.completed_steps',
            'setup.install_recovery_key',
            'setup.token_version',
            'setup.updated_at',
        ]);

        return new self(
            dbInstalled: (bool) ($values['setup.is_installed'] ?? false),
            setupToken: $values['setup.install_token'],
            tokenExpiresAt: isset($values['setup.token_expires_at'])
                ? Carbon::parse($values['setup.token_expires_at'])
                : null,
            completedSteps: $values['setup.completed_steps'] ?? [],
            // ...
        );
    }
}
```

Similarly, `SchoolEntity` reads from settings rather than a single model:

```php
final readonly class SchoolEntity extends BaseEntity
{
    public static function fromModel(Model $model): static
    {
        return self::get();
    }

    public static function get(): self
    {
        $values = Settings::get([
            'school.name',
            'school.institutional_code',
            'school.email',
            // ...
        ]);

        return new self(
            name: (string) ($values['school.name'] ?? ''),
            // ...
        );
    }
}
```

### 10.2 Generation Factories

Entities that represent tokens or generated values may have creation factories:

```php
final readonly class ActivationToken extends BaseEntity
{
    public static function generate(User $user, array $options = []): self
    {
        $raw = bin2hex(random_bytes(32));
        $ttlDays = $options['ttl_days'] ?? 30;

        $token = ApiToken::create([
            'user_id' => $user->id,
            'token' => Hash::make($raw),
            'token_type' => 'activation',
            'name' => $options['name'] ?? 'Account Activation',
            'expires_at' => now()->addDays($ttlDays),
            'attempts' => 0,
        ]);

        return new self(
            plainText: $raw,
            tokenId: $token->id,
            expiresAt: now()->addDays($ttlDays),
        );
    }
}
```

Note that `generate()` is a static factory that performs persistence (storing the token), not a
pure factory. This is a deliberate exception for the token generation pattern — the entity acts as
both a factory and a value object.

### 10.3 User-Specific Factories

```php
final readonly class AccountActivation extends BaseEntity
{
    public static function forUser(User $user): self
    {
        return self::fromModel($user);
    }
}
```

---

## 11. Entity Extraction Workflow

When business logic conditionals accumulate in a Model, Action, or Livewire component, extract an
entity using this workflow:

### Step 1: Identify Business Rules

Look for:
- Boolean capability checks: `if ($year->is_active && !$year->internships()->exists())`
- State transition logic: `if ($registration->status === 'pending' && $registration->placement_id)`
- Date calculations with status checks: `if ($internship->status === 'active' && $internship->end_date < now())`
- Multi-field conditionals that answer a single business question

### Step 2: Create the Entity Class

Create `app/{Module}/{SubModule}/Entities/{Name}.php`:

```php
<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Entities;

use App\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class AcademicYearState extends BaseEntity
{
    public function __construct(
        private bool $isActive,
        private bool $hasRelatedRecords = false,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            isActive: (bool) ($model->is_active ?? false),
            hasRelatedRecords: $model->internships()->exists(),
        );
    }

    public function canBeDeleted(): bool
    {
        return !$this->isActive && !$this->hasRelatedRecords;
    }
}
```

### Step 3: Add the Named Accessor to the Model

```php
// app/Academics/AcademicYear/Models/AcademicYear.php
use App\Academics\AcademicYear\Entities\AcademicYearState;

public function asAcademicYearState(): AcademicYearState
{
    return AcademicYearState::fromModel($this);
}
```

### Step 4: Update Callers

Replace scattered conditionals with the named method:

```php
// Before
if (!$academicYear->is_active || $academicYear->internships()->exists()) {
    throw new \RuntimeException('Cannot delete this academic year.');
}

// After
if (!$academicYear->asAcademicYearState()->canBeDeleted()) {
    throw new \RuntimeException('Cannot delete this academic year.');
}
```

### Step 5: Write Unit Tests

```php
// tests/Unit/Academics/AcademicYear/Entities/AcademicYearStateTest.php
use App\Academics\AcademicYear\Entities\AcademicYearState;

describe('AcademicYearState', function () {
    it('returns canBeDeleted when inactive and no related records', function () {
        $state = new AcademicYearState(isActive: false, hasRelatedRecords: false);

        expect($state->canBeDeleted())->toBeTrue();
    });

    it('prevents deletion when active', function () {
        $state = new AcademicYearState(isActive: true, hasRelatedRecords: false);

        expect($state->canBeDeleted())->toBeFalse();
    });
});
```

---

## 12. Testing Entities (No DB)

Entity tests are pure unit tests — no database, no migrations, no factories.

### Test Location

```
tests/Unit/{Module}/{SubModule}/Entities/{Name}Test.php
```

### Key Principles

1. **No `LazilyRefreshDatabase`** — entities hold no database state
2. **Construct directly** — `new Apprentice(status: ..., isLocked: false, setupRequired: false)`
3. **Test all business paths** — every capability check, every state query
4. **Use `with()` for variations** — create modified copies without re-specifying all constructor args

### Example Test

```php
describe('InternshipPeriod', function () {
    it('accepts registrations when status permits and within window', function () {
        $period = new InternshipPeriod(
            status: InternshipStatus::ACTIVE,
            registrationStartDate: Carbon::yesterday(),
            registrationEndDate: Carbon::tomorrow(),
        );

        expect($period->isAcceptingRegistrations())->toBeTrue();
    });

    it('rejects registrations when before window opens', function () {
        $period = new InternshipPeriod(
            status: InternshipStatus::ACTIVE,
            registrationStartDate: Carbon::tomorrow()->addDay(),
            registrationEndDate: Carbon::tomorrow()->addDays(30),
        );

        expect($period->isAcceptingRegistrations(Carbon::now()))->toBeFalse();
    });

    it('rejects registrations when status forbids it', function () {
        $period = new InternshipPeriod(
            status: InternshipStatus::COMPLETED,
            registrationStartDate: Carbon::yesterday(),
            registrationEndDate: Carbon::tomorrow(),
        );

        expect($period->isAcceptingRegistrations())->toBeFalse();
    });

    it('evaluates without database', function () {
        // No RefreshDatabase trait needed
        // No factories called
        // No migrations run
        // This test executes in < 1ms
        $period = new InternshipPeriod(status: InternshipStatus::PUBLISHED);

        expect($period->isAcceptingRegistrations())->toBeTrue();
    });
});
```

### Test Matrix for the Complete Table

| Test Category | Entity | What to Test |
|---------------|--------|-------------|
| Capability checks | All entities | Each boolean method — true and false paths |
| Date logic | `InternshipPeriod`, `RegistrationState`, `AssignmentRules` | Edge cases: null dates, boundary values |
| Enum transitions | `Apprentice` | Each `canTransitionTo()` target |
| Value equality | All entities | `equals()` — same values, different values |
| Serialization | All entities | `toArray()` output structure |
| Immutability | All entities | `with()` returns new instance, original unchanged |

---

## 13. Common Entity Patterns in the Codebase

### 13.1 State Entity Pattern

The most common pattern. Entity holds status + related boolean flags and provides capability checks.

```php
// Constructor: status enum + boolean flags
// Methods: canBeDeleted(), canBeApproved(), canBeEdited(), etc.
```

Examples: `AcademicYearState`, `RegistrationState`, `CompanyState`, `InternshipGroupState`,
`SubmissionState`, `LogbookState`, `DepartmentState`, `PlacementState`, `PartnershipState`.

### 13.2 Period Entity Pattern

Entity holds date ranges and answers temporal queries.

```php
// Constructor: status enum + date range fields
// Methods: isAcceptingRegistrations(), isBeforeRegistrationWindow(), isWithinAcademicYear()
```

Example: `InternshipPeriod`.

### 13.3 Capacity Entity Pattern

Entity holds numeric constraints and answers availability queries.

```php
// Constructor: quota integers
// Methods: isFull(), availableSlots(), hasAvailableSlots()
```

Example: `PlacementCapacity`.

### 13.4 Business Role Pattern

Entity represents a user's business role with role-specific rules.

```php
// Constructor: status + role-specific flags
// Methods: allowsLogin(), isSuspended(), requiresSetup(), canTransitionTo()
```

Example: `Apprentice`.

### 13.5 Settings-Backed Entity Pattern

Entity is not backed by a single model row but by the application settings store.

```php
// fromModel() delegates to get()
// get() reads from Settings facade
```

Examples: `SetupEntity`, `SchoolEntity`.

### 13.6 Token Entity Pattern

Entity represents a generated token with validation logic.

```php
// Constructor: token fields + expiry
// Methods: isTokenExpired(), hasExceededMaxAttempts()
// Factories: generate(), forUser()
```

Examples: `ActivationToken`, `AccountActivation`.

### 13.7 Evaluation Entity Pattern

Entity holds computed scores and provides categorization.

```php
// Constructor: scores + feedback
// Methods: averageCriterionScore(), scoreBand(), isValid()
```

Example: `EvaluationResult`.

### 13.8 Delegation Entity Pattern

Entity is a thin wrapper that delegates to an enum's methods.

```php
// Constructor: single status enum
// Methods: delegates to enum: isVerified(), canBeEdited()
```

Examples: `SupervisionStatus`, `LogbookState`.

### 13.9 Complete Entity & Accessor Table

| Entity | Module/Submodule | File | Model Accessor | Constructor State |
|--------|-----------------|------|----------------|-------------------|
| `AcademicYearState` | Academics/AcademicYear | `Entities/AcademicYearState.php` | `AcademicYear::asAcademicYearState()` | `isActive: bool`, `hasRelatedRecords: bool` |
| `SchoolEntity` | Academics/School | `Entities/SchoolEntity.php` | Settings-backed (no model accessor) | `name`, `institutionalCode`, `email`, `address`, `phone`, `website`, `principalName` |
| `DepartmentState` | Academics/Department | `Entities/DepartmentState.php` | `Department::asDepartmentState()` | (single-status) |
| `EvaluationResult` | Evaluation | `Entities/EvaluationResult.php` | `Assessment::asAssessmentResult()` | `category: EvaluationCategory`, `overallScore: float`, `criteriaScores: array`, `feedback: ?string` |
| `ActivationToken` | Auth/ApiTokens | `Entities/ActivationToken.php` | `ApiToken::asActivationToken()` | `plainText: string`, `tokenId: string`, `expiresAt: Carbon` |
| `AccountActivation` | Auth/Account | `Entities/AccountActivation.php` | — (uses `forUser()`) | `isActivated: bool`, `tokenExpiresAt: ?Carbon`, `tokenIsValid: bool`, `attempts: int` |
| `RecoveryCodeState` | Auth/AccountRecovery | `Entities/RecoveryCodeState.php` | — | — |
| `SuperAdminIntegrityRules` | Auth/SuperAdmin | `Entities/SuperAdminIntegrityRules.php` | — | — |
| `AssessmentResult` | Assessment | `Entities/AssessmentResult.php` | `Assessment::asAssessmentResult()` | — |
| `RegistrationState` | Enrollment/Registration | `Entities/RegistrationState.php` | `Registration::asRegistrationState()` | `status: ?string`, `startDate: ?Carbon`, `endDate: ?Carbon`, `hasPlacement: bool` |
| `PlacementCapacity` | Enrollment/Placement | `Entities/PlacementCapacity.php` | `Placement::asPlacementCapacity()` | `quota: int`, `filledQuota: int` |
| `PlacementState` | Enrollment/Placement | `Entities/PlacementState.php` | `Placement::asPlacementState()` | — |
| `CompanyState` | Partners/Company | `Entities/CompanyState.php` | `Company::asCompanyState()` | `placementCount: int`, `partnershipCount: int` |
| `PartnershipState` | Partners/Partnership | `Entities/PartnershipState.php` | `Partnership::asPartnershipState()` | — |
| `InternshipPeriod` | Program/Internship | `Entities/InternshipPeriod.php` | `Internship::asInternshipPeriod()` | `status: ?InternshipStatus`, `registrationStartDate: ?Carbon`, `registrationEndDate: ?Carbon`, `academicYearStart: ?Carbon`, `academicYearEnd: ?Carbon` |
| `InternshipState` | Program/Internship | `Entities/InternshipState.php` | `Internship::asInternshipState()` | `placementCount: int`, `registrationCount: int` |
| `InternshipGroupState` | Program/InternshipGroup | `Entities/InternshipGroupState.php` | `InternshipGroup::asInternshipGroupState()` | — |
| `Apprentice` | User | `Entities/Apprentice.php` | `User::asApprentice()` | `status: AccountStatus`, `isLocked: bool`, `setupRequired: bool` |
| `AssignmentRules` | Assignment | `Entities/AssignmentRules.php` | `Assignment::asAssignmentRules()` | `isMandatory: bool`, `dueDate: ?Carbon` |
| `SubmissionState` | Assignment/Submission | `Entities/SubmissionState.php` | `Submission::asSubmissionState()` | `status: SubmissionStatus` |
| `LogbookState` | Journals/Logbook | `Entities/LogbookState.php` | `Logbook::asLogbookState()` | `status: LogbookStatus` |
| `ScheduleStatus` | Journals/Schedule | *(entity name may differ)* | `Schedule::asScheduleStatus()` | — |
| `AbsenceRequestStatusEntity` | Journals/AbsenceRequest | *(entity name may differ)* | `AbsenceRequest::asAbsenceRequestStatus()` | — |
| `AttendanceStatusEntity` | Journals/Attendance | *(entity name may differ)* | `Attendance::asAttendanceStatus()` | — |
| `SupervisionStatus` | Guidance/SupervisionLog | `Entities/SupervisionStatus.php` | `SupervisionLog::asSupervisionStatus()` | `status: ?SupervisionLogStatus` |
| `SettingEntity` | Settings | `Entities/SettingEntity.php` | `Setting::asSetting()` | `key: string`, `value: mixed`, `type: ?string`, `group: ?string` |
| `SetupEntity` | Setup | `Entities/SetupEntity.php` | Settings-backed | `dbInstalled: bool`, `setupToken: ?string`, `tokenExpiresAt: ?Carbon`, `completedSteps: array`, `recoveryKey: ?string`, `updatedAt: ?Carbon`, `tokenVersion: int` |

> Note: Entities marked with `—` for constructor state were not inspected for this document; refer
> to the source file for the exact constructor signature.

### 13.10 Verification Checklist

When creating or reviewing an entity, verify:

- [ ] `final readonly` class extending `BaseEntity`?
- [ ] `declare(strict_types=1)` at the top?
- [ ] `fromModel(Model $model): static` factory method present?
- [ ] Zero Eloquent/Facade imports except `Illuminate\Database\Eloquent\Model` in `fromModel()`?
- [ ] Named accessor on the Model (`as{EntityName}()`) — not a generic `entity()`?
- [ ] Business rules moved out of Model and Action into Entity methods?
- [ ] Unit tests written without `RefreshDatabase`/`LazilyRefreshDatabase`?
- [ ] All constructor properties are `private` (not `public`)?
- [ ] Methods return business answers (`bool`, `int`, enum), not raw field access?

---

## References

- **Base class**: `app/Core/Entities/BaseEntity.php`
- **ADR**: `docs/adr/adr-entity-model-separation.md` (ADR-004)
- **Conventions**: `docs/conventions.md` (§5 Models, §7 Entities)
- **Architecture**: `docs/architecture.md` (Layer 6 — Domain Rules)
- **Testing**: `docs/architecture/testing-pattern.md`
- **Entity refactoring skill**: `.agents/skills/entity-refactoring/SKILL.md`
- **Entity refactoring rules**:
  - `.agents/skills/entity-refactoring/rules/01-model-responsibilities.md`
  - `.agents/skills/entity-refactoring/rules/02-entity-purity.md`
  - `.agents/skills/entity-refactoring/rules/03-bridge-pattern.md`
  - `.agents/skills/entity-refactoring/rules/04-business-rules.md`
  - `.agents/skills/entity-refactoring/rules/05-testing-separation.md`
