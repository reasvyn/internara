# Model Pattern Reference — Persistence, Relationships & Entity Bridges

> **Last updated:** 2026-06-10
> **Changes:** initial metadata — no content changes
## Description

Model conventions: BaseModel, UUID primary keys, #[Fillable] attribute, scopes, relationships, factories, and Entity bridge pattern.

## 1. Active Record Philosophy

Models extend Eloquent's Active Record implementation and are responsible for:

- **Persistence** — reading/writing database rows
- **Relationships** — defining and querying associations between tables
- **Attribute casting** — transforming raw column values into PHP types
- **Query scopes** — reusable query fragments
- **Media management** — file uploads and image conversions
- **Entity bridging** — exposing `as{Entity}()` accessors that delegate to pure domain objects

Business rules, invariant enforcement, and state-machine logic **do not** belong in models. They are
extracted into Entity classes (see [entity-pattern.md](entity-pattern.md)).

---

## 2. BaseModel Contract

All models (except User) extend `BaseModel`, which configures:

| Concern              | Implementation                                        |
| -------------------- | ----------------------------------------------------- |
| UUID primary key     | `use HasUuids;` (Laravel's trait, generates UUID v7)  |
| Non-incrementing     | Inherits `$incrementing = false` from `HasUuids`      |
| String key type      | Inherits `$keyType = 'string'` from `HasUuids`        |
| Common scopes        | `scopeActive()`, `scopeInactive()`, `scopeRecent()`, `scopeCreatedAfter()`, `scopeCreatedBefore()`, `scopeOrdered()` |

---

## 3. BaseAuthenticatable

The `User` model cannot extend `BaseModel` because Laravel's authentication system requires it to
extend `Illuminate\Foundation\Auth\User` (or `Authenticatable`). `BaseAuthenticatable` bridges this
gap by applying the same UUID and scope conventions to the authenticatable base.

The `User` model extends `BaseAuthenticatable` and re-applies `HasUuids` explicitly — this is
harmless (PHP traits are idempotent) and makes the UUID dependency visible without digging into the
parent class.

---

## 4. Model Directory Structure

Models live inside their owning module, following the two-tier path convention:

```
app/{Module}/{Submodule}/Models/{Model}.php
```

One model per file. No model files in shared `app/Models/` — models always belong to a module.

---

## 5. UUID Primary Key Convention

All tables use **UUID v7** (time-ordered) as primary keys. This is enforced by `BaseModel` and
`BaseAuthenticatable` via Laravel's `HasUuids` trait, which generates ordered UUIDs that preserve
B-tree insertion locality.

For the rare model that cannot extend `BaseModel`, apply `HasUuids` manually with
`$incrementing = false` and `$keyType = 'string'`.

### Foreign Keys in Migrations

All foreign key columns use `foreignUuid()` with explicit `onDelete()` behavior. No mixed key types
are permitted. Enforced through code review.

---

## 6. `#[Fillable]` Attribute Convention

Mass assignment protection uses PHP 8 **attributes**, not the traditional `$fillable` property.
This keeps the fillable declaration adjacent to the class signature for visibility.

Multi-line attribute syntax is required when the array spans multiple values. For a single value,
inline is acceptable.

The traditional `$fillable` property is **not used** anywhere in the codebase. All models use
`#[Fillable]`.

---

## 7. Relationship Naming Convention

Relationships follow a strict singular/plural convention based on cardinality:

| Type                        | Method Name | Example                       |
| --------------------------- | ----------- | ----------------------------- |
| `BelongsTo` / `HasOne`      | Singular    | `user()`, `academicYear()`    |
| `HasMany` / `BelongsToMany` | Plural      | `users()`, `registrations()`  |
| `MorphTo`                   | Singular    | `verifiable()`                |
| `MorphMany`                 | Plural      | `comments()`                  |

Always define the inverse relationship. The optional `$foreignKey` parameter is used when the column
name deviates from convention.

---

## 8. Entity Accessor Pattern

Models expose entities through **named accessors** using the `as{EntityName}()` pattern. This is the
bridge between the persistence layer (Model) and the domain layer (Entity). Never use a generic
`entity()` method.

A model may expose multiple entity accessors when it contains data for multiple domain concepts.

---

## 9. Scope Pattern

Query scopes encapsulate common WHERE conditions. Scopes are defined at the model level and
chain naturally through Eloquent queries.

### Base Scopes (inherited from BaseModel / BaseAuthenticatable)

- `active()` — WHERE `is_active = true`
- `inactive()` — WHERE `is_active = false`
- `recent(20)` — ORDER BY `created_at` DESC LIMIT 20
- `createdAfter($date)` — WHERE `created_at >= $date`
- `createdBefore($date)` — WHERE `created_at <= $date`
- `ordered('name')` — ORDER BY `name` DESC

### Convention

- Scope method returns `Builder`.
- Scope parameters are explicit and typed — avoid `...$args` patterns.
- Scopes are the **only** query logic on models. Complex query assembly belongs in Read Actions.

---

## 10. Casts Convention

Attribute casting uses `protected $casts` (property), not the `casts()` method, unless dynamic
casting is needed.

- **Standard casts** — use the `$casts` property for static configurations (e.g., `date`,
  `datetime`, `boolean`, `json`, `hashed`).
- **Enum casts** — use the enum class FQCN as the cast target. The column stores the enum's `value`
  (lowercase string), and Eloquent hydrates it back into the enum instance.
- **Custom casts** — for complex transformation logic, create a dedicated cast class.
- **Method-based casts** — use the `casts()` method only when cast configuration is dynamic (runtime
  conditions affect the return value).

Prefer the `$casts` property for static configurations.

---

## 11. Media Library Integration

File uploads use [spatie/laravel-medialibrary](https://spatie.be/docs/laravel-medialibrary). Models
implement `HasMedia` and use the `InteractsWithMedia` trait.

### Convention

- Avatar/media collections use `singleFile()` to restrict to one file per collection.
- For models with multiple named collections, use an enum to keep collection names consistent.

---

## 12. Factory Convention

Every model has a corresponding factory in `database/factories/`. Factories use Laravel's native
`HasFactory` trait.

Factory states use fluent methods. States never duplicate the full definition — they only override
the relevant attributes.

---

## 13. Testing Models

### What to Test / What Not to Test

- **Do not** test Eloquent relationships directly — the framework is trusted.
- **Do not** test query scopes in isolation — test them through Actions or Livewire components.
- **Do** test model-specific business methods (e.g., custom accessors, mutators, computed
  properties).
- **Do** test custom casts.

These are covered implicitly by Action and Livewire feature tests.
