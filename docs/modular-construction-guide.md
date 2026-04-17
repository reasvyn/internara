# Module Development: Standardized Construction Guide

This document provides the authoritative technical protocols for constructing and integrating new
domain modules within the Internara project, ensuring compliance with **ISO/IEC 12207**
(Infrastructure Process) and the **Project Genesis Blueprint (ARC01-INIT-01)**.

---

## 🏛️ The Modular Invariant: Zero-Coupling

Internara is engineered as a **Strict Modular Monolith**. To prevent "Spaghetti Modularity," every
module must adhere to the following isolation rules:

1.  **Logical Sovereignty**: A module owns its logic, models, and data.
2.  **Explicit Communication**: Inter-module interaction occurs ONLY via **Service Contracts**
    (Interfaces).
3.  **Physical Independence**: Physical foreign keys across module boundaries are **Forbidden**.

---

## 🛠️ Step 1: Scaffolding (Initial Construction)

Utilize the custom Internara generators to ensure your module satisfies the namespace and path
invariants from the start.

```bash
# 1. Generate the base module structure
php artisan module:make {ModuleName}

# 2. Generate foundational artifacts (standardized)
php artisan module:make-service {Name} {Module}
php artisan module:make-model {Name} {Module}
php artisan module:make-livewire {Name} {Module}
```

---

## 📂 Step 2: Directory Structure & Omissions

Internara enforces strict path mapping to maintain brevity and semantic clarity.

### 2.1 The Invariant Hierarchy

- **Namespace**: `Modules\{Module}\{Layer}` (Omit the `src` segment).
- **Domain Omission**: If your module name is the same as the domain name, the domain folder MUST be
  omitted.
    - ✅ `Modules/User/src/Models/User.php`
    - ❌ `Modules/User/src/User/Models/User.php`

### 2.2 Standard Layering

| Directory        | Responsibility     | Constraint                                    |
| :--------------- | :----------------- | :-------------------------------------------- |
| `src/Models/`    | Persistence Logic  | Must use `HasUuid` trait.                     |
| `src/Services/`  | Business Logic     | Must extend `BaseService` or `EloquentQuery`. |
| `src/Livewire/`  | UI Orchestration   | Must be "Thin" (No direct CRUD, Model as DTO OK). |
| `src/Enums/`     | Domain Constants   | Shared cross-module as stable primitives.     |
| `src/Contracts/` | Service Interfaces | Mandatory for inter-module calls.             |

---

## 🧠 Step 3: The Service Layer (Contract-First)

Every piece of business logic MUST reside in a Service that implements a Contract.

### 3.1 Persistence-Based Services

If the service manages a specific model, extend `EloquentQuery`.

```php
namespace Modules\Example\Services;

use Modules\Shared\Services\EloquentQuery;
use Modules\Example\Services\Contracts\ExampleService as Contract;

class ExampleService extends EloquentQuery implements Contract
{
    // Logic goes here...
}
```

### 3.2 Logic-Only Services

If the service manages orchestration without a specific model, extend `BaseService`.

```php
namespace Modules\Example\Services;

use Modules\Shared\Services\BaseService;

class AnalyticsService extends BaseService
{
    // ...
}
```

---

## 🛡️ Step 4: Security & Integrity (PEP)

### 4.1 Policy Enforcement

Every model MUST have a corresponding Policy. Invoke them at the system boundary:

- **Livewire**: `$this->authorize('action', $model);`
- **Service**: `Gate::authorize('action', $model);` (Mandatory for Write operations).

### 4.2 Cross-Module Integrity (SLRI)

When referring to data from another module, verify existence via their contract:

```php
// Inside ExampleService
if (!$this->userService->exists($userId)) {
    throw new UserNotFoundException();
}
```

---

## 🖥️ Step 3.3: Livewire Record Managers (RecordManager)

All data-management Livewire components **must** extend `RecordManager` from the `UI` module — never `Component` directly, and never use the deprecated `ManagesRecords` trait.

### Mandatory Contract

`RecordManager` declares two abstract methods your class must implement:

| Method | Purpose |
| :--- | :--- |
| `initialize(): void` | Set UI state: searchable columns, per-page defaults, sortable columns |
| `getTableHeaders(): array` | Declare the table column headers and sort configuration |

### Minimal Example

```php
namespace Modules\Example\Livewire;

use Livewire\Attributes\Computed;
use Modules\Example\Services\Contracts\ExampleService;
use Modules\UI\Livewire\RecordManager;

class ExampleManager extends RecordManager
{
    protected string $viewPermission = 'example.view';

    // Optional: narrow-down which columns the service can sort by
    protected array $sortable = ['name', 'created_at'];

    // DI via boot() — called before mount(), ideal for service injection
    public function boot(ExampleService $service): void
    {
        $this->service = $service;
    }

    // Set UI-specific state (runs inside parent::mount())
    public function initialize(): void
    {
        $this->searchable = ['name'];
    }

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('example::ui.name'), 'sortable' => true],
            ['key' => 'created_at', 'label' => __('ui::common.created_at'), 'sortable' => true],
        ];
    }
}
```

### Custom Records Query

Override `records()` with `#[Computed]` only when you need eager loading or extra filters:

```php
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;

#[Computed]
public function records(): LengthAwarePaginator
{
    return $this->service
        ->query([
            'search'   => $this->search,
            'sort_by'  => $this->sortBy['column'] ?? 'created_at',
            'sort_dir' => $this->sortBy['direction'] ?? 'desc',
        ])
        ->with(['relatedModel:id,name'])
        ->paginate($this->perPage);
}
```

> **`$this->sortBy` is always an array** — access it as `$this->sortBy['column']` and `$this->sortBy['direction']`. The property `$this->sortDir` does not exist.

### Cached Dropdown Properties

Dropdown data that rarely changes should be shared via `Cache::remember()` so all concurrent users hit the cache instead of querying the DB:

```php
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

#[Computed]
public function categories(): \Illuminate\Support\Collection
{
    return Cache::remember('dropdown:categories', 300, fn () =>
        app(CategoryService::class)->all(['id', 'name'])
    );
}
```

### Method Override Type Safety

`RecordManager::edit()` declares `mixed $id`. Child overrides **must** keep `mixed`:

```php
public function edit(mixed $id): void  // ✅
public function edit(string $id): void // ❌ Fatal incompatible declaration
```

---

Compliance is enforced by the **Architecture Police** (`tests/Arch/` and `modules/*/tests/Arch/`). Prior to committing,
ensure your module passes all quality gates.

1.  **Mirroring Invariant**: `tests/Unit/Services/` must mirror `src/Services/`.
2.  **Arch Audit**: `vendor/bin/pest modules/{ModuleName}/tests/Arch/`.
3.  **Style Gate**: `composer lint`.

---

_Failure to comply with these module standards will trigger an architectural defect rejection during
the verification phase._
