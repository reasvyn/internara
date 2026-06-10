# Data / DTO Pattern Reference

> **Last updated:** 2026-06-10

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

All DTOs extend `App\Core\Data\BaseData`, a `readonly` abstract class that implements
`JsonSerializable`. It provides five instance methods and three static methods:

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
$child  = new MockData('Alice', 25);
$parent = new NestedMockData('Parent', $child);

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

| Pattern | Examples |
|---------|----------|
| `{Entity}Data` | `LoginData`, `SetupData`, `SchoolData`, `AdminData`, `BrandData`, `SettingData`, `CompanyData`, `InternshipData`, `DepartmentData`, `AcademicYearData`, `NotificationData`, `RegistrationData` |
| `{Verb}{Entity}Data` | `SetupTokenData`, `RecoveryCodeData`, `InternshipGroupData`, `SettingGroupData`, `PartnershipData` |

Specialised DTOs that are not pure data carriers use different suffixes:
`ActionResponse`, `AuditCheck`, `AuditReport`.

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
$original = new SetupData(name: 'School A', institutionalCode: 'SC001', email: 'a@b.com');
$merged   = $original->merge(['name' => 'School B']);

$original->name; // 'School A'  — unchanged
$merged->name;   // 'School B'  — new instance
```

---

## 5. Constructor vs fromArray Hydration

DTOs have **two hydration paths**:

### Constructor (direct instantiation)

Preferred when all values are available in the calling scope. Full IDE autocompletion and type
checking:

```php
$data = new LoginData(
    identifier: 'admin@example.com',
    password: 'secret',
    remember: true,
);
```

### `fromArray()` (reflection hydration)

Used when data arrives as an array — from a form request, an API payload, or a serialised source.
Uses reflection to resolve constructor parameter names and match them against array keys:

```php
$data = LoginData::fromArray([
    'identifier' => $request->input('email'),
    'password' => $request->input('password'),
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
SetupData::fromArray(['name' => 'X', 'institutionalCode' => 'SC001', ...]);

// snake_case keys (e.g. from form requests or external APIs)
SetupData::fromArray(['name' => 'X', 'institutional_code' => 'SC001', ...]);
```

The resolution is powered by `BaseData::resolveConstructorParams()`, which caches the reflection
result per class for performance. The cache can be cleared with `clearParamCache()`.

---

## 7. Polymorphic Construction via `from()`

The static `from(mixed $source): static` method accepts either:

- **An array** — delegates directly to `fromArray()`.
- **An object with `toArray()`** — calls `$source->toArray()` then delegates to `fromArray()`.

This enables a single call site that works with both raw arrays and typed sources:

```php
// From array
$data = LoginData::from($request->validated());

// From an Eloquent model (has toArray())
$data = LoginData::from($user);

// From another DTO or any object with ->toArray()
$data = LoginData::from($existingDto);
```

Unsupported types (scalars, null, objects without `toArray()`) throw `InvalidArgumentException`.

---

## 8. Key Extraction

### `only(string ...$keys): array`

Extracts a subset of keys from the DTO's array representation. Missing keys are silently ignored:

```php
$dto = new MockData('Alice', 30, true);
$dto->only('name', 'isAdmin');
// ['name' => 'Alice', 'isAdmin' => true]
```

### `except(string ...$keys): array`

Removes specified keys from the array representation:

```php
$dto = new MockData('Bob', 25, false);
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
$original = new SetupData(
    name: 'School',
    institutionalCode: 'SC001',
    email: 'a@b.com',
    address: '',
    phone: '',
    website: '',
    principalName: '',
);

$updated = $original->merge(['address' => '123 Main St', 'phone' => '555-0100']);

$original->phone; // ''            — original unchanged
$updated->phone;  // '555-0100'    — new instance
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
$dto = new LoginData(identifier: 'user@a.com', password: 's', remember: true);
json_encode($dto);
// {"identifier":"user@a.com","password":"s","remember":true}
```

The `jsonSerialize()` method simply delegates to `toArray()`, so the JSON output matches the
array representation.

For `ActionResponse`, `jsonSerialize()` uses `array_filter` to omit `null` and empty-array fields
from the output, producing a clean payload:

```json
{"success":true,"data":{"id":1},"message":"Created"}
```

When data is an Eloquent `Model`, it is converted to array via `$model->toArray()`.

---

## 11. ActionResponse — Specialized Result DTO

`ActionResponse` (`app/Core/Data/ActionResponse.php`) is a `final readonly` class that does **not**
extend `BaseData` — it has a distinct contract tailored for Action return values.

### Purpose

Provides a uniform return type from Command and Process Actions so that calling code (Livewire
components, controllers, tests) can handle success/failure without inspecting catch blocks or
return types.

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$success` | `bool` | `true` | Whether the operation succeeded. |
| `$data` | `mixed` | `null` | The operation's result payload. |
| `$message` | `?string` | `null` | A human-readable status message. |
| `$redirect` | `?string` | `null` | Optional redirect URL for the UI layer. |
| `$errors` | `array` | `[]` | Validation or business-rule error details. |

### Named Constructors

| Factory | `$success` | `$data` | `$message` | Use Case |
|---------|------------|---------|------------|----------|
| `ActionResponse::ok($data, $message)` | `true` | optional | optional | General success |
| `ActionResponse::created($data, $message)` | `true` | optional | `__('common.created')` | Resource creation |
| `ActionResponse::updated($data, $message)` | `true` | optional | `__('common.updated')` | Resource update |
| `ActionResponse::deleted($message)` | `true` | `null` | `__('common.deleted')` | Resource deletion |
| `ActionResponse::error($message, $errors)` | `false` | `null` | required | Operation failure |

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

### Usage Pattern

```php
class CreateUserAction extends BaseAction
{
    public function execute(CreateUserData $data): ActionResponse
    {
        return $this->transaction(function () use ($data) {
            $user = User::create($data->toArray());

            $this->log('user_created', $user);

            return ActionResponse::created($user->asUserEntity());
        });
    }
}

// In Livewire:
public function save(): void
{
    $response = $this->action->execute($this->getData());

    if ($response->failed()) {
        $this->error($response->message);

        return;
    }

    $this->success($response->message);
    $this->redirect($response->redirect ?? '/users');
}
```

---

## 12. AuditCheck & AuditReport — Specialized DTOs

These two classes extend `BaseData` and are used by the auditing system
(`app/Core/Actions/AuditorAction.php`).

### AuditCheck

A single auditable fact: one check, one outcome.

```php
final readonly class AuditCheck extends BaseData
{
    public function __construct(
        public AuditCategory $category,   // e.g. AuditCategory::ENVIRONMENT
        public string $nameKey,            // Translation key for the check name
        public AuditStatus $status,        // AuditStatus::PASS | FAIL | WARNING | SKIP
        public string $messageKey,         // Translation key for the result message
        public array $nameParams = [],     // Replacement params for $nameKey translation
        public array $messageParams = [],  // Replacement params for $messageKey translation
    ) {}
}
```

Properties use enum types (`AuditCategory`, `AuditStatus`) rather than raw strings. The `nameKey`
and `messageKey` fields store translation keys, making the DTO locale-agnostic.

### AuditReport

A collection of `AuditCheck` instances with derived logic:

```php
final readonly class AuditReport extends BaseData
{
    /** @param AuditCheck[] $checks */
    public function __construct(public array $checks = []) {}

    // True only when ALL checks pass
    public function passed(): bool { ... }

    // Filter checks by category
    public function forCategory(AuditCategory $category): array { ... }
}
```

`passed()` iterates all checks and returns `false` if any has `AuditStatus::FAIL`. `forCategory()`
filters the checks array, enabling category-specific rendering in the UI.

Together they demonstrate how DTOs can encapsulate derived behaviour without losing their value-object
nature.

---

## 13. DTO Migration Path

DTOs follow a three-phase introduction process. This prevents premature abstraction while keeping the
door open to typing:

| Phase | Signature | Description |
|-------|-----------|-------------|
| **1 — Array** | `execute(array $data)` | Rapid development. No DTO exists. Parameters are documented in the Action docblock or inferred from call sites. |
| **2 — Union** | `execute(Data|array $data)` | DTO exists but callers can still pass raw arrays. `BaseData::from()` handles both inside the Action body. Migration is non-breaking. |
| **3 — Typed** | `execute(Data $data)` | DTO is mandatory. All callers have been migrated. The array path is removed. |

### Example

```php
// Phase 1 — volatile, don't commit to a shape yet
class RegisterStudentProcess extends BaseAction
{
    public function execute(array $data): Registration { ... }
}

// Phase 2 — DTO exists, both paths work
class RegisterStudentProcess extends BaseAction
{
    public function execute(RegisterStudentData|array $data): Registration
    {
        $data = $data instanceof RegisterStudentData ? $data : RegisterStudentData::from($data);
        // ...
    }
}

// Phase 3 — final, DTO-only
class RegisterStudentProcess extends BaseAction
{
    public function execute(RegisterStudentData $data): Registration { ... }
}
```

### When to Migrate

- **Phase 1 → 2:** When the Action has 3+ array parameters or a second caller appears.
- **Phase 2 → 3:** When all call sites are updated and the DTO shape is stable (no changes in the
  last 2 sprints or equivalent time period).

---

## 14. Testing DTOs

DTOs are tested as pure unit tests — no database, no HTTP. Tests live in
`tests/Unit/{Module}/Data/{Name}Test.php` or `tests/Unit/Core/Data/{Name}Test.php`.

### BaseData Contract Tests

The canonical test (`tests/Unit/Core/Data/BaseDataTest.php`) uses mock DTOs defined inline:

```php
readonly class MockData extends BaseData
{
    public function __construct(
        public string $name,
        public int $age,
        public bool $isAdmin = false,
    ) {}
}
```

Test coverage includes:

| Test | What It Verifies |
|------|------------------|
| Hydration from camelCase keys | `fromArray()` with exact parameter names |
| Hydration from snake_case keys | `fromArray()` with `is_admin` → `isAdmin` resolution |
| Missing required param throws | `InvalidArgumentException` on omission |
| Default values applied | Optional params use defaults when absent |
| `toArray()` serialisation | All public properties returned as array |
| Nested BaseData recursion | Child DTOs recursively converted |
| `from()` with array | Polymorphic construction from array |
| `from()` with `toArray()` object | Polymorphic construction from object |
| `from()` with unsupported type | Throws for scalars, null |
| `jsonSerialize()` | `json_encode` produces expected output |
| `only()` extraction | Subset key extraction |
| `only()` ignores missing keys | Silent skip for non-existent keys |
| `except()` removal | Key exclusion |
| `merge()` immutability | Original unchanged, new instance with overrides |
| `clearParamCache()` | Reflection cache reset |

### ActionResponse Tests

`tests/Unit/Core/Data/ActionResponseTest.php` covers every named constructor, immutability of
`withRedirect()`, and `jsonSerialize()` output (omission of null/empty fields, Model conversion).

### Concrete DTO Tests

Each concrete DTO has a dedicated test file that typically verifies:

- **Construction** — all parameters pass through correctly
- **`toArray()`** — output matches expected shape
- **`fromArray()`** — round-trip: array → DTO → array
- **`merge()`** — overrides produce correct new instance

Example structure (from `tests/Unit/Setup/Data/SetupDataTest.php`):

```php
describe('SetupData', function () {
    it('can be constructed with all required fields', function () {
        $data = new SetupData(
            name: 'School',
            institutionalCode: 'SC001',
            email: 'a@b.com',
        );

        expect($data->name)->toBe('School');
        expect($data->institutionalCode)->toBe('SC001');
        expect($data->email)->toBe('a@b.com');
    });

    it('can be hydrated from snake_case array and back', function () {
        $data = SetupData::fromArray([
            'name' => 'School',
            'institutional_code' => 'SC001',
            'email' => 'a@b.com',
        ]);

        expect($data->toArray())->toMatchArray([
            'name' => 'School',
            'institutionalCode' => 'SC001',
        ]);
    });
});
```

---

## 15. Complete DTO Inventory

### Shared DTOs (`app/Core/Data/`)

| Class | Extends | Properties | Purpose |
|-------|---------|------------|---------|
| `BaseData` | — | (abstract) | Base class; `toArray`, `fromArray`, `from`, `only`, `except`, `merge`, `jsonSerialize` |
| `ActionResponse` | — (standalone) | `success`, `data`, `message`, `redirect`, `errors` | Uniform Action return value |
| `AuditCheck` | `BaseData` | `category`, `nameKey`, `status`, `messageKey`, `nameParams`, `messageParams` | Single audit check result |
| `AuditReport` | `BaseData` | `checks` | Collection of `AuditCheck` with `passed()`, `forCategory()` |

### Module DTOs (`app/{Module}/Data/` and below)

| Module | Submodule | Class | Properties |
|--------|-----------|-------|------------|
| Setup | — | `SetupData` | `name`, `institutionalCode`, `email`, `address`, `phone`, `website`, `principalName` |
| Setup | — | `AdminData` | (varies) |
| Setup | — | `SchoolData` | (varies) |
| Setup | Installation | `SetupTokenData` | `plaintext`, `expiresAt` |
| Auth | Login | `LoginData` | `identifier`, `password`, `remember` |
| Auth | AccountRecovery | `RecoveryCodeData` | (varies) |
| Academics | AcademicYear | `AcademicYearData` | (varies) |
| Academics | Department | `DepartmentData` | (varies) |
| Enrollment | Registration | `RegistrationData` | `internshipId`, `placementId`, `academicYear`, `startDate`, `endDate`, `proposedCompanyName`, `proposedCompanyAddress` |
| Partners | Company | `CompanyData` | (varies) |
| Partners | Partnership | `PartnershipData` | (varies) |
| Program | Internship | `InternshipData` | (varies) |
| Program | InternshipGroup | `InternshipGroupData` | (varies) |
| Settings | — | `SettingData` | `key`, `value`, `type`, `group`, `description` |
| Settings | — | `SettingGroupData` | `name`, `count` |
| Settings | Branding | `BrandData` | `name`, `title`, `logo`, `favicon`, `colors`, `version`, `authorName`, `authorEmail`, `description`, `license`, `gitUrl` |
| User | Notifications | `NotificationData` | (varies) |

### Test Files

All located under `tests/Unit/` mirroring the source structure:

```
tests/Unit/Core/Data/BaseDataTest.php
tests/Unit/Core/Data/ActionResponseTest.php
tests/Unit/Core/Data/AuditCheckTest.php
tests/Unit/Core/Data/AuditReportTest.php
tests/Unit/Setup/Data/SetupDataTest.php
tests/Unit/Setup/Data/AdminDataTest.php
tests/Unit/Auth/Login/Data/LoginDataTest.php
tests/Unit/Academics/AcademicYear/Data/AcademicYearDataTest.php
tests/Unit/Academics/Department/Data/DepartmentDataTest.php
tests/Unit/Enrollment/Registration/Data/RegistrationDataTest.php
tests/Unit/Partners/Company/Data/CompanyDataTest.php
tests/Unit/Program/Internship/Data/InternshipDataTest.php
tests/Unit/Settings/Data/SettingDataTest.php
tests/Unit/Settings/Data/SettingGroupDataTest.php
tests/Unit/Settings/Branding/Data/BrandDataTest.php
tests/Unit/User/Notifications/Data/NotificationDataTest.php
```
