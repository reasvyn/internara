# EloquentQuery Base Service

The `EloquentQuery` base service is a core architectural component of Internara. it provides a
standardized, boilerplate-free implementation for services that primarily perform database
operations on a single Eloquent model.

## Purpose

- **Standardization:** Ensures all domain services have a consistent API for CRUD and querying.
- **Efficiency:** Reduces repetitive code by providing common logic for pagination, filtering,
  searching, and caching.
- **Security:** Enforces mass-assignment protection and safe querying patterns.
- **Abstraction:** Decouples the UI and Business Logic layers from direct Eloquent builder
  manipulation.

---

## Core Features

### 1. Automatic Pagination & Filtering

The `paginate()` and `get()` methods automatically handle common query parameters:

- `sort_by`: The column to sort by.
- `sort_dir`: The direction (`asc` or `desc`).
- `search`: A string to search across designated columns.
- Other keys in the `$filters` array are applied as exact matches (`where`).

### 2. Searchable & Sortable Columns

Modules can explicitly define which columns are available for searching and sorting:

```php
$this->setSearchable(['name', 'email']);
$this->setSortable(['created_at', 'name']);
```

### 3. Integrated Caching

The `remember()` method provides a clean way to cache query results:

```php
return $this->remember('cache-key', now()->addHour(), function ($service) {
    return $service->all();
});
```

### 4. Base Query Extension

You can set a base query (e.g., with global scopes or eager loading) that all subsequent queries
will build upon:

```php
$this->setBaseQuery(User::active()->with('profile'));
```

---

## Technical Reference

### Interface: `Modules\Shared\Services\Contracts\EloquentQuery`

| Method                                                                  | Description                                                             |
| :---------------------------------------------------------------------- | :---------------------------------------------------------------------- |
| `all(array $columns = ['*'])`                                           | Returns all records.                                                    |
| `paginate(array $filters, int $perPage, array $columns)`                | Returns a paginated result set based on filters.                        |
| `get(array $filters, array $columns)`                                   | Returns a collection based on filters.                                  |
| `first(array $filters, array $columns)`                                 | Returns the first record matching filters, or null.                     |
| `firstOrFail(array $filters, array $columns)`                           | Returns the first record or throws `ModelNotFoundException`.            |
| `find(mixed $id, array $columns)`                                       | Finds a record by its primary key.                                      |
| `exists(array $filters)`                                                | Checks if a record exists matching filters.                             |
| `create(array $data)`                                                   | Creates a new record (respects `$fillable`).                            |
| `update(mixed $id, array $data)`                                        | Updates a record (respects `$fillable`). Throws exception if not found. |
| `save(array $attributes, array $values)`                                | `updateOrCreate` implementation.                                        |
| `delete(mixed $id, bool $force)`                                        | Deletes a record. Supports soft/force delete.                           |
| `destroy(mixed $ids, bool $force)`                                      | Deletes multiple records.                                               |
| `factory()`                                                             | Returns a new Model Factory instance.                                   |
| `remember(string $key, mixed $ttl, Closure $callback, bool $skipCache)` | Caching wrapper.                                                        |

---

## Model Factories & Low-Coupling

To maintain strict modular isolation and low-coupling, direct access to `Model::factory()` is
discouraged when interacting with models from other modules (e.g., in Seeders or Tests).

### The Rule

Always access model factories through their respective **Service Contract**.

- **Incorrect (High-Coupling):** `\Modules\User\Models\User::factory()->create();`
- **Correct (Low-Coupling):**
  `app(\Modules\User\Services\Contracts\UserService::class)->factory()->create();`

### Why?

1.  **Abstraction:** The Service layer remains the only authoritative entry point for a module.
2.  **Flexibility:** If a module decides to change its persistence layer (e.g., swapping Eloquent
    for another ORM), the factory method can be updated within the Service without breaking external
    callers.
3.  **Consistency:** Ensures that even test data generation follows the same architectural
    boundaries as production code.

---

## Implementation Example

### 1. Define the Contract

```php
namespace Modules\User\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<\Modules\User\Models\User>
 */
interface UserService extends EloquentQuery
{
    // Custom business methods...
}
```

### 2. Implement the Service

```php
namespace Modules\User\Services;

use Modules\Shared\Services\EloquentQuery;
use Modules\User\Services\Contracts\UserService as Contract;
use Modules\User\Models\User;

class UserService extends EloquentQuery implements Contract
{
    public function __construct()
    {
        $this->setModel(new User());
        $this->setSearchable(['name', 'email']);
        $this->setSortable(['created_at']);
    }
}
```

---

## Best Practices

1.  **Always use the Contract:** Type-hint the interface in your controllers or other services, not
    the concrete class.
2.  **Initialize in Constructor:** Set the model and searchable/sortable columns in the
    `__construct` method.
3.  **Use `setBaseQuery` for Eager Loading:** If a service almost always needs certain
    relationships, define them in a base query.

---

**Navigation**

[← Previous: Architecture Guide](architecture-guide.md) |
[Next: Module Structure Overview →](foundational-module-philosophy.md)
