# Data / DTO Pattern Reference — DTO Lifecycle, Immutability & Boundary Rules

> **Last updated:** 2026-06-10
> **Changes:** initial metadata — no content changes

## Description
This document is a comprehensive reference on the Data Transfer Object (DTO) pattern as implemented
in the Internara codebase. It covers philosophy, the `BaseData` contract, conventions, specialized
subtypes, and the testing approach.

---


## 1. DTO Philosophy

DTOs are **typed, immutable value objects** that carry data between layers. They serve three
purposes:

1. **Contract documentation** — the typed constructor signature is self-documenting; you know
   exactly what data an Action expects without reading its body.
2. **Compiler-enforced correctness** — PHP type hints catch mismatches at call sites instead of
   surfacing as cryptic array-key errors at runtime.
3. **Layer isolation** — DTOs decouple callers from internal representations. A Livewire component
   hands a DTO to an Action; the Action never touches raw request input.

DTOs are **optional** — use them when the input has stabilized (3+ parameters or multiple callers).
For new or volatile code, start with a plain `array` parameter and migrate to a DTO later (see
§13).

---

## 2. BaseData Contract

All DTOs extend `BaseData`, a `readonly` abstract class that implements `JsonSerializable`. It
provides five instance methods and three static methods:

| Method | Signature | Purpose |
|--------|-----------|---------|
| `toArray()` | `(): array` | Recursively serialises all public properties to a camelCase associative array. Nested `BaseData` instances are recursively converted. Arrays of `BaseData` are mapped. Other `JsonSerializable` instances delegate to `jsonSerialize()`. |
| `jsonSerialize()` | `(): array` | Delegates to `toArray()`. Enables `json_encode($dto)` to produce the expected shape. |
| `only()` | `(string ...$keys): array` | Extracts a subset of keys into a new array. Silently ignores missing keys. |
| `except()` | `(string ...$keys): array` | Removes specified keys from the array representation. |
| `merge()` | `(array $overrides): static` | Returns a **new instance** with the given overrides applied. The original is never mutated. |
| `fromArray()` | `(array $data): static` (static) | Hydrates a new instance from an array. Resolves constructor parameters by name, falling back to `snake_case` keys. Throws `InvalidArgumentException` when a required parameter is missing. |
| `from()` | `(mixed $source): static` (static) | Polymorphic factory. Accepts an array or any object with a `toArray()` method. Delegates to `fromArray()` in both cases. Throws `InvalidArgumentException` for unsupported types. |
| `clearParamCache()` | `(): void` (static) | Clears the internal reflection cache. Used in tests to prevent stale metadata when mock DTOs change. |

Key design decision: `toArray()` uses `get_object_vars($this)`, which — because the class is
`readonly` — returns only the public promoted properties. This eliminates the risk of accidentally
including private/internal state.

### Recursive Serialisation

```php
$child  = new ChildData('Alice', 25);
$parent = new ParentData('Parent', $child);

$parent->toArray();
// [
//     'label' => 'Parent',
//     'child' => ['name' => 'Alice', 'age' => 25, 'isAdmin' => false],
// ]
```

This makes DTOs safe to serialise deeply nested structures without manual mapping.

---

## 3. Naming Convention

DTO names follow the pattern `{Verb}{Entity}Data` or `{Entity}Data`. The `Data` suffix distinguishes
them from Entities and Models:

| Pattern | Description |
|---------|-------------|
| `{Entity}Data` | Simple data carrier for a single entity concept |
| `{Verb}{Entity}Data` | Data for a specific operation on an entity |

Specialised DTOs that are not pure data carriers use suffixes like `ActionResponse`.

---

## 4. Immutability

Every DTO is declared `final readonly`. The `readonly` keyword (PHP 8.2) enforces:

- Properties can only be set once (during construction).
- No property can be modified after the object is created.
- The class itself cannot be extended.

This guarantees that once a DTO is constructed, its state is fixed for its entire lifetime. Any
"modification" returns a new instance via `merge()` or named constructors like
`ActionResponse::withRedirect()`.

```php
$original = new EntityData(name: 'Original', code: 'C001', email: 'a@b.com');
$merged   = $original->merge(['name' => 'Updated']);

$original->name; // 'Original' — unchanged
$merged->name;   // 'Updated'   — new instance
```

---

## 5. Constructor vs fromArray Hydration

DTOs have **two hydration paths**:

### Constructor (direct instantiation)

Preferred when all values are available in the calling scope. Full IDE autocompletion and type
checking:

```php
$data = new EntityData(
    identifier: 'user@example.com',
    label: 'Example',
    active: true,
);
```

### `fromArray()` (reflection hydration)

Used when data arrives as an array — from a form request, an API payload, or a serialised source.
Uses reflection to resolve constructor parameter names and match them against array keys:

```php
$data = EntityData::fromArray([
    'identifier' => $request->input('email'),
    'label' => $request->input('name'),
]);
```

Missing required parameters throw `\InvalidArgumentException`. Optional parameters (those with a
default value in the constructor) are silently filled with their default when absent from the
array.

---

## 6. Snake_case Key Resolution

`fromArray()` resolves keys in two passes:

1. First, it looks for the **exact camelCase key** matching the constructor parameter name.
2. If not found, it looks for the **`snake_case` equivalent** via `Str::snake()`.

This means both of these work:

```php
// camelCase keys
EntityData::fromArray(['label' => 'X', 'institutionalCode' => 'C001', ...]);

// snake_case keys (e.g. from form requests or external APIs)
EntityData::fromArray(['label' => 'X', 'institutional_code' => 'C001', ...]);
```

The resolution is powered by reflection, with caching per class for performance. The cache can be
cleared with `clearParamCache()`.

---

## 7. Polymorphic Construction via `from()`

The static `from(mixed $source): static` method accepts either:

- **An array** — delegates directly to `fromArray()`.
- **An object with `toArray()`** — calls `$source->toArray()` then delegates to `fromArray()`.

This enables a single call site that works with both raw arrays and typed sources:

```php
// From array
$data = EntityData::from($request->validated());

// From an Eloquent model (has toArray())
$data = EntityData::from($model);

// From another DTO or any object with ->toArray()
$data = EntityData::from($existingDto);
```

Unsupported types (scalars, null, objects without `toArray()`) throw `InvalidArgumentException`.

---

## 8. Key Extraction

### `only(string ...$keys): array`

Extracts a subset of keys from the DTO's array representation. Missing keys are silently ignored:

```php
$dto = new EntityData('Alice', 30, true);
$dto->only('name', 'isAdmin');
// ['name' => 'Alice', 'isAdmin' => true]
```

### `except(string ...$keys): array`

Removes specified keys from the array representation:

```php
$dto = new EntityData('Bob', 25, false);
$dto->except('age');
// ['name' => 'Bob', 'isAdmin' => false]
```

Both methods return plain arrays, not DTOs. They are useful when serialising a DTO for a subset of
its consumers (e.g., building a response payload without internal fields).

---

## 9. Merging

`merge(array $overrides): static` creates a **new instance** with the provided overrides applied
on top of the existing DTO data:

```php
$original = new EntityData(
    name: 'Original',
    code: 'C001',
    email: 'a@b.com',
    address: '',
    phone: '',
);

$updated = $original->merge(['address' => '123 Main St', 'phone' => '555-0100']);

$original->phone; // ''              — original unchanged
$updated->phone;  // '555-0100'      — new instance
```

This is critical for immutability: `merge()` calls `static::fromArray()` internally, returning a
fresh object. The original instance is never modified.

Internally, `merge()`:

```
$this->toArray()  →  array_merge with $overrides  →  static::fromArray()
```

---

## 10. JSON Serialization

`BaseData` implements `JsonSerializable`, so any DTO can be passed directly to `json_encode()`:

```php
$dto = new EntityData(identifier: 'user@a.com', label: 's', active: true);
json_encode($dto);
// {"identifier":"user@a.com","label":"s","active":true}
```

The `jsonSerialize()` method simply delegates to `toArray()`, so the JSON output matches the
array representation.

For `ActionResponse`, `jsonSerialize()` uses `array_filter` to omit `null` and empty-array fields
from the output, producing a clean payload:

```json
{"success":true,"data":{"id":1},"message":"Created"}
```

---

## 11. ActionResponse — Specialized Result DTO

`ActionResponse` is a `final readonly` class that does **not** extend `BaseData` — it has a
distinct contract tailored for Action return values.

### Purpose

Provides a uniform return type from Command and Process Actions so that calling code (Livewire
components, controllers, tests) can handle success/failure without inspecting catch blocks or
return types.

### Properties

- `$success` (`bool`, default `true`) — Whether the operation succeeded.
- `$data` (`mixed`, default `null`) — The operation's result payload.
- `$message` (`?string`, default `null`) — A human-readable status message.
- `$redirect` (`?string`, default `null`) — Optional redirect URL for the UI layer.
- `$errors` (`array`, default `[]`) — Validation or business-rule error details.

### Named Constructors

- `ActionResponse::ok($data, $message)` — General success.
- `ActionResponse::created($data, $message)` — Resource creation.
- `ActionResponse::updated($data, $message)` — Resource update.
- `ActionResponse::deleted($message)` — Resource deletion.
- `ActionResponse::error($message, $errors)` — Operation failure.

### Helper Methods

| Method | Purpose |
|--------|---------|
| `failed(): bool` | Convenience: `! $this->success` |
| `withRedirect(string $url): self` | Returns a **new instance** with the redirect URL set. The original is unchanged. |

### Immutability

Like all DTOs, `ActionResponse` is immutable. `withRedirect()` constructs a fresh instance:

```php
$response = ActionResponse::ok(['id' => 1]);
$response->redirect; // null

$withRedirect = $response->withRedirect('/dashboard');
$withRedirect->redirect; // '/dashboard'
```

### JSON Serialization

`jsonSerialize()` omits `null` data, `null` message, `null` redirect, and empty errors via
`array_filter`, producing a compact wire format.

---

## 12. DTO Migration Path

DTOs follow a three-phase introduction process. This prevents premature abstraction while keeping the
door open to typing:

| Phase | Signature | Description |
|-------|-----------|-------------|
| **1 — Array** | `execute(array $data)` | Rapid development. No DTO exists. Parameters are documented in the Action docblock or inferred from call sites. |
| **2 — Union** | `execute(Data|array $data)` | DTO exists but callers can still pass raw arrays. `BaseData::from()` handles both inside the Action body. Migration is non-breaking. |
| **3 — Typed** | `execute(Data $data)` | DTO is mandatory. All callers have been migrated. The array path is removed. |

```php
// Phase 1 — volatile, don't commit to a shape yet
public function execute(array $data): Model { ... }

// Phase 2 — DTO exists, both paths work
public function execute(EntityData|array $data): Model
{
    $data = $data instanceof EntityData ? $data : EntityData::from($data);
    // ...
}

// Phase 3 — final, DTO-only
public function execute(EntityData $data): Model { ... }
```

### When to Migrate

- **Phase 1 → 2:** When the Action has 3+ array parameters or a second caller appears.
- **Phase 2 → 3:** When all call sites are updated and the DTO shape is stable (no changes in the
  last 2 sprints or equivalent time period).

---

## 13. Testing DTOs

DTOs are tested as pure unit tests — no database, no HTTP.

### BaseData Contract Tests

The contract tests use mock DTOs defined inline to verify:

- Hydration from camelCase keys
- Hydration from snake_case keys
- Missing required param throws
- Default values applied
- `toArray()` serialisation
- Nested BaseData recursion
- `from()` with array
- `from()` with `toArray()` object
- `from()` with unsupported type
- `jsonSerialize()`
- `only()` extraction
- `only()` ignores missing keys
- `except()` removal
- `merge()` immutability
- `clearParamCache()`

### ActionResponse Tests

Tests cover every named constructor, immutability of `withRedirect()`, and `jsonSerialize()` output
(omission of null/empty fields).

### Concrete DTO Tests

Each concrete DTO has a dedicated test file that typically verifies:

- **Construction** — all parameters pass through correctly
- **`toArray()`** — output matches expected shape
- **`fromArray()`** — round-trip: array → DTO → array
- **`merge()`** — overrides produce correct new instance
