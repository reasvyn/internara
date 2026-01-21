# EloquentQuery: The Standardized Service Layer

To maintain consistency and reduce boilerplate across our modular domain services, Internara
utilizes the `EloquentQuery` base service. This pattern centralizes standard CRUD operations while
providing a flexible architecture for complex business orchestration.

---

## 1. Core Philosophy

In a Modular Monolith, services often repeat basic logic (Find, Create, Update). `EloquentQuery`
abstracts this away, allowing developers to focus on **unique business rules** rather than
repetitive Eloquent calls.

### Why use this pattern?

- **DRY (Don't Repeat Yourself)**: Common query patterns are inherited.
- **Predictable APIs**: All domain services share a consistent method signature.
- **Easy Mocking**: Centralized data access makes unit testing logic easier.

---

## 2. Standard Implementation

Every domain service that manages an Eloquent model should extend this base class.

### 2.1 Basic Service Setup

```php
namespace Modules\User\Services;

use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;

class UserService extends EloquentQuery
{
    /**
     * Define the model handled by this service.
     */
    protected function model(): string
    {
        return User::class;
    }
}
```

---

## 3. Available Inherited Methods

The following methods are automatically available once you extend `EloquentQuery`.

| Method                            | Description                                       |
| :-------------------------------- | :------------------------------------------------ |
| `all(array $columns = ['*'])`     | Returns a collection of all records.              |
| `paginate(int $perPage = 15)`     | Returns a paginated result set.                   |
| `create(array $data)`             | Validates and persists a new record.              |
| `update(string $id, array $data)` | Updates an existing record by its UUID.           |
| `delete(string $id)`              | Deletes a record by its UUID.                     |
| `find(string $id)`                | Retrieves a single record or throws an exception. |
| `query()`                         | Returns a fresh Eloquent query builder instance.  |

---

## 4. Advanced Usage & Customization

While `EloquentQuery` provides the basics, most services will require custom orchestration.

### 4.1 Extending Standard Logic

You should override methods only when you need to inject cross-module logic or complex events.

```php
public function create(array $data): Model
{
    // 1. Perform custom validation or logic
    $data['password'] = Hash::make($data['password']);

    // 2. Call parent to handle persistence
    $user = parent::create($data);

    // 3. Dispatch events or trigger cross-module side effects
    event(new UserCreated($user));

    return $user;
}
```

### 4.2 Building Complex Scopes

Use the `query()` method to keep your service methods expressive.

```php
public function getActiveStudentsInDepartment(string $departmentId)
{
    return $this->query()
        ->where('department_id', $departmentId)
        ->where('is_active', true)
        ->get();
}
```

---

_The `EloquentQuery` pattern is our primary tool for keeping the service layer lean and
maintainable. Always check the `Modules\Shared\Services\EloquentQuery` source for the latest utility
methods._
