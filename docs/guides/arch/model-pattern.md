# Model Pattern — Active Record, Persistence & Entity Bridges

## Description

This pattern governs how Internara defines **Eloquent Models** — the persistence layer. It synthesizes global industry standards — **Active Record Pattern** (Martin Fowler, *Patterns of Enterprise Application Architecture*), **UUID v7** (RFC 9562, time-ordered identifiers), **Laravel Eloquent conventions** (relationships, scopes, casts, mass assignment) — into enforceable rules tied to Internara's stack: `BaseModel`/`BaseAuthenticatable`, `HasUuids`, `#[Fillable]` PHP 8 attribute, Spatie MediaLibrary, and the `as{Entity}()` Bridge Pattern.

Without it, Models accumulate business logic (Anemic Domain Model), key conventions drift, and the persistence layer becomes coupled to business requirements. With it, Models are thin data access objects — relationships, scopes, casts, media — while business rules live on Entities.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Models are data access objects only.** Models define relationships, scopes, casts, attributes, media collections, factory config, and the entity bridge accessor. They MUST NOT contain `canLogin()`, `isActive()`, `canBeDeleted()`, `hasAvailableSlots()`, `isExpired()`, `canTransitionTo()`, or any business decision method. Business rules live on Entities (see `entity-pattern.md`). This is the **SRP** (Robert C. Martin): a Model changes because the data model changes; an Entity changes because the business requirement changes.

2. **UUID v7 (RFC 9562) as primary keys.** All tables use time-ordered UUID v7 via `HasUuids`. UUID v7 places a 48-bit Unix millisecond timestamp in leading bits, preserving B-tree insertion locality — new rows land at the end of the index, avoiding random page splits (v4's problem). This is enforced by `BaseModel` and `BaseAuthenticatable`.

3. **`#[Fillable]` attribute, not `$fillable` property.** Mass assignment protection uses PHP 8 `#[Fillable]` attribute, not the traditional `$fillable` property. Multi-line syntax required for multiple values. The traditional property is not used anywhere in the codebase.

4. **Entity bridge via named accessors.** Models expose entities through `as{EntityName}(): EntityType` methods — never generic `entity()`. A Model may expose **multiple entities** for different business roles. The accessor name describes the business role, not the class.

5. **Singular/plural relationship naming.** `BelongsTo`/`HasOne` = singular (`user()`, `academicYear()`). `HasMany`/`BelongsToMany` = plural (`users()`, `registrations()`). `MorphTo` = singular. `MorphMany` = Plural. Always define the inverse.

6. **Foreign keys with explicit `onDelete`/`onUpdate`.** All foreign key columns use `foreignUuid()->constrained()` with explicit `onDelete()`/`onUpdate()` behavior. No mixed key types. This is **D6** (invariant from `docs/conventions.md` §7).

7. **Enum casting for status columns.** Status columns are cast to their enum class via `$casts` property. The column stores the enum's `value` (lowercase string), and Eloquent hydrates it back into the enum instance. Never compare with hardcoded strings.

---

## How to Apply

### 1. Active Record Pattern — What It Is

Models extend Eloquent's **Active Record** implementation (Fowler, PoEAA): "an object that wraps a single database row, encapsulates data access, and adds domain logic on that data." Eloquent is the canonical PHP implementation — `User::find($id)` returns a `User` instance that can `$user->save()`, `$user->delete()`, and define relationships.

**Internara's adaptation:** Eloquent is a fine ORM and a poor domain model. Keep Eloquent for what it is good at — query building, relationships, migrations, mass assignment, soft deletes — but push business logic to Entities. The Model is the persistence adapter; the Entity is the domain.

### 2. BaseModel Contract

| Concern | Implementation |
|---------|---------------|
| UUID primary key | `use HasUuids;` (Laravel's trait, generates UUID v7 per RFC 9562) |
| Non-incrementing | Inherits `$incrementing = false` from `HasUuids` |
| String key type | Inherits `$keyType = 'string'` from `HasUuids` |
| Common scopes | `scopeActive()`, `scopeInactive()`, `scopeRecent()`, `scopeCreatedAfter()`, `scopeCreatedBefore()`, `scopeOrdered()` |

### 3. BaseAuthenticatable

The `User` model cannot extend `BaseModel` because Laravel's authentication requires `Illuminate\Foundation\Auth\User`. `BaseAuthenticatable` bridges this gap — same UUID and scope conventions applied to the authenticatable base. `User` re-applies `HasUuids` explicitly (harmless — PHP traits are idempotent).

### 4. UUID v7 — Time-Ordered Primary Keys

UUID v7 (RFC 9562) places a 48-bit Unix millisecond timestamp in leading bits, followed by version/variant markers and random/counter bits. Values sort roughly in creation order.

**Why v7 over v4:**
- v4 has 122 random bits — zero ordering, causes **B-tree index fragmentation** on large tables
- v7 is time-ordered — new rows land at the end of the B-tree, yielding sequential writes and better index performance
- RFC 9562 explicitly recommends v7 over v1/v6 for new systems

**Pitfalls:** Timestamp exposure (anyone holding the ID can decode creation time to millisecond precision). Same-millisecond ordering is random — use a separate sequence column if needed.

### 5. Relationship Naming Convention

| Type | Method Name | Example |
|------|------------|---------|
| `BelongsTo` / `HasOne` | Singular | `user()`, `academicYear()` |
| `HasMany` / `BelongsToMany` | Plural | `users()`, `registrations()` |
| `MorphTo` | Singular | `verifiable()` |
| `MorphMany` | Plural | `comments()` |

### 6. Entity Accessor Pattern — Bridge to Domain

Models expose entities via named accessors using `as{EntityName}()`. This is the **Bridge Pattern** — connecting persistence (Eloquent) with domain (Entity):

```php
// ✅ Correct — specific name communicates the role
public function asSomeRole(): SomeEntity
{
    return SomeEntity::fromModel($this);
}

// ❌ Wrong — generic name reveals nothing
public function entity(): SomeEntity
```

A model may expose multiple entity accessors for different domain concepts.

### 7. Scope Pattern — Reusable Query Fragments

Scopes encapsulate common WHERE conditions. Base scopes inherited from `BaseModel`: `active()`, `inactive()`, `recent()`, `createdAfter()`, `createdBefore()`, `ordered()`.

Rules: Scope method returns `Builder`. Parameters are explicit and typed. Scopes are the **only** query logic on models. Complex query assembly belongs in Read Actions.

### 8. Casts Convention

Use `$casts` property for static configurations. Enum casts use the enum class FQCN. Custom casts for complex transformations. Method-based `casts()` only when dynamic.

### 9. Media Library Integration

File uploads use [spatie/laravel-medialibrary](https://spatie.be/docs/laravel-medialibrary). Models implement `HasMedia` and use `InteractsWithMedia`. Avatar/media collections use `singleFile()`.

### 10. Factory Convention

Every model has a factory in `database/factories/`. Factory states use fluent methods. States never duplicate the full definition.

### 11. Testing Models

Do NOT test Eloquent relationships directly — the framework is trusted. Do NOT test query scopes in isolation — test through Actions or Livewire. DO test custom accessors, mutators, computed properties, and custom casts.

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `$model->canLogin()` / `$model->isActive()` on Model | `$model->asRole()->allowsLogin()` / `$entity->asState()->isActive()` | Anemic Domain Model — business logic on data access object |
| `protected $fillable = [...]` property | `#[Fillable([...])]` PHP 8 attribute | D4 — legacy mass assignment syntax |
| `$model->entity()` generic accessor | `$model->asSomeRole()` named accessor | Role-specific naming missing |
| UUID v4 (random) primary keys | UUID v7 (time-ordered) via `HasUuids` | Index fragmentation, poor B-tree locality |
| `foreignUuid('user_id')` without `onDelete` | `foreignUuid('user_id')->constrained()->onDelete('cascade')` | D6 — missing referential action |
| `'status' => 'draft'` hardcoded in `$attributes` | `'status' => ExampleStatus::DRAFT->value` | String drift from enum definition |
| Complex query in Livewire `mount()` | Read Action or Model scope | Business logic leaked to UI layer |
| `$model->doSomething()` business method on Model | Entity method called from Action | Business rules on persistence layer |
| No `scopeOrdered()` — inline `orderBy('created_at', 'desc')` | Reusable scope | Query duplication, no consistency |
| Mixed UUID v4 and UUID v7 in same database | All tables use UUID v7 | Inconsistent key strategy |

---

## Quick References

- `entity-pattern.md` — Entity contract, Bridge Pattern, purity rules
- `action-pattern.md` — Actions call Entity methods, never Model business methods
- `modular-pattern.md` §8 Model Patterns, §15 Migration & Database — architecture contracts
- `docs/conventions.md` §7 Database Conventions — D6, migrations, seeders
- [Martin Fowler — Active Record (PoEAA)](https://martinfowler.com/eaaCatalog/activeRecord.html) — object that wraps a row, encapsulates data access
- [RFC 9562 — UUID v7](https://www.rfc-editor.org/rfc/rfc9562.html) — time-ordered UUIDs, B-tree locality
- [Laravel — Eloquent ORM](https://laravel.com/docs/eloquent) — relationships, scopes, casts, mutators
- [Laravel — HasUuids](https://laravel.com/docs/eloquent#uuids-and-ulids) — UUID v7 generation
- [Spatie — Laravel Medialibrary](https://spatie.be/docs/laravel-medialibrary) — file uploads, conversions
- [Wendell Adriel — Eloquent Active Record](https://wendelladriel.com/blog/understanding-laravel-eloquents-active-record-pattern) — deep dive on Eloquent's pattern
