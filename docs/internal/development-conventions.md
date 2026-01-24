# Development Conventions: Engineering Standards

To ensure that the Internara codebase remains clean, predictable, and accessible to all developers,
we adhere to a strict set of coding conventions. Consistency is the foundation of our
maintainability.

> **Governance Mandate:** These conventions are the technical implementation of the requirements
> defined in the **[Internara Specs](../internal/internara-specs.md)**. All code must support the
> product goals (e.g., Multi-Language, Mobile-First) outlined in the authoritative specifications.

---

## 1. Code Style & Quality

We follow standard PHP recommendations and rigorous static analysis to ensure interoperability.

- **PSR-12**: All PHP code must adhere to the PSR-12 Extended Coding Style Guide.
- **Strict Typing**: Use strict typing (`declare(strict_types=1);`) in every PHP file.
- **Linting**: We use **Laravel Pint** for automated linting. Always run `./vendor/bin/pint` before
  committing changes.

---

## 2. Modular Namespace Convention (The `src` Rule)

A critical rule in Internara is the handling of the `src` directory within modules.

- **Directory Structure**: Module logic is located in `modules/{ModuleName}/src/`.
- **Namespace Rule**: The `src` segment **MUST be omitted** from the namespace definition.
- **Why?** This keeps namespaces short, semantic, and aligned with standard Laravel conventions.

**Example:**

- **Path**: `modules/User/src/Services/UserService.php`
- **Namespace**: `namespace Modules\User\Services;` (✅ Correct)
- **Namespace**: `namespace Modules\User\src\Services;` (❌ Incorrect)

---

## 3. Naming Standards

### 3.1 PHP Classes & Files

- **Controllers/Livewire**: PascalCase (e.g., `StudentList`).
- **Services**: PascalCase with `Service` suffix (e.g., `InternshipService`).
- **Models**: PascalCase, singular (e.g., `JournalEntry`).
- **Contracts (Interfaces)**: PascalCase, named by capability. **No `Interface` suffix**.
    - **Service Contracts**: Located in `Services/Contracts/` (e.g., `UserService`).
    - **General Contracts**: Located in `Contracts/` (e.g., `PermissionManager`).
- **Concerns (Traits)**: PascalCase, ideally prefixed with `Has` or `Can`.
    - **Model Concerns**: Located in `Models/Concerns/` (e.g., `HasUuid`).
- **Enums**: PascalCase, located in `src/Enums/`. Used for statuses and fixed options.

---

## 4. Database Identity & State

### 4.1 Identity: `HasUuid`

We have standardized on **UUID v4** for all primary and foreign keys across the application.

- **Security (from Specs):** Prevents ID enumeration and unauthorized data guessing.
- **Portability:** Critical for our Modular Monolith, allowing ID generation before persistence.
- **Constraint:** **No physical foreign keys** between modules. Use simple indexed UUID columns.

```php
use Modules\Shared\Models\Concerns\HasUuid;

class Internship extends Model
{
    use HasUuid;
}
```

### 4.2 State Management: `HasStatuses`

Entities follow a lifecycle (e.g., `Pending` -> `Approved` -> `Finished`).

- **Rationale:** Centralizes state transitions and validation logic.
- **Audit:** Tracks "who" and "when" for every status change, supporting the **Monitoring** goals of
  the specs.

```php
$registration->setStatus('approved', 'Student met all entry requirements.');
```

### 4.3 Scoping: `HasAcademicYear`

Data integrity across academic cycles is critical. The `HasAcademicYear` concern ensures that
operational data is always filtered by the active year.

- **Automatic Scoping:** Populates the `academic_year` column from
  `setting('active_academic_year')`.
- **Integrity:** Prevents data leak between different academic periods.

```php
use Modules\Shared\Models\Concerns\HasAcademicYear;

class JournalEntry extends Model
{
    use HasAcademicYear;
}
```

---

## 5. Internationalization (i11n)

Internara is a **Multi-Language Application** (Indonesian & English).

- **Hardcoding Prohibited**: **Never** write raw text in Views, Controllers, or Services.
- **Translation Helper**: Always use `__('module::file.key')` or `@lang`.
- **Locale Awareness**: Code must respect the active locale (`id` or `en`) when formatting dates or
  currency.

---

## 6. Domain Logic & Service Layer

The Service Layer is the **Single Source of Truth** for business logic.

### 6.1 The `EloquentQuery` Pattern

To reduce boilerplate, domain services should extend `Modules\Shared\Services\EloquentQuery`.

- **Standard Methods**: `all()`, `paginate()`, `create()`, `update()`, `delete()`, `find()`,
  `query()`.
- **Implementation Example**:
    ```php
    class UserService extends EloquentQuery
    {
        protected function model(): string
        {
            return User::class;
        }
    }
    ```

#### Advanced Usage & Customization

You should override methods only when you need to inject cross-module logic or complex events.

```php
public function create(array $data): Model
{
    // 1. Perform global checks (e.g., Maintenance Mode)
    if (setting('maintenance_mode') === true) {
         throw new ServiceException(__('exception::messages.system_maintenance'));
    }

    $data['password'] = Hash::make($data['password']);

    // 2. Call parent to handle persistence
    $user = parent::create($data);

    // 3. Dispatch events or trigger cross-module side effects
    event(new UserCreated($user));

    return $user;
}
```

#### Building Complex Scopes

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

### 6.2 Service Design

- **Contract-First**: When interacting across modules, depend on **Contracts**, never concrete
  classes.
- **Strict Isolation**: It is strictly prohibited to call cross-module concrete classes (especially
  **Eloquent Models**) directly from within your service or utility layers. All data and business
  operations must be requested through the appropriate module's **Service Contract**.
- **Public Accessors**: Static classes or Framework Facades designed for public consumption are the
  only exceptions. Direct instantiation of another module's classes is a violation of modular
  integrity.
- **Role Awareness**: Business logic must explicitly handle the roles defined in Specs (Instructor,
  Staff, Student, Industry Supervisor).
- **Inheritance**: CRUD services should extend `Modules\Shared\Services\EloquentQuery`.

### 6.3 Configuration & Settings

- **No `env()`**: Never call `env()` directly in application code.
- **Infrastructure Config**: Use `config('app.timezone')` for static infrastructure values.
- **Dynamic Application Settings**: Use the `setting($key, $default)` helper for all business values
  (e.g., `site_title`, `brand_logo`, `contact_email`). **Hard-coding these values is strictly
  prohibited.**

---

## 7. UI/UX Implementation

While visual guidelines are in the **[UI/UX Guide](ui-ux-development-guide.md)**, code conventions
apply here:

- **Mobile-First Structure**: Livewire components must be structured to support mobile views by
  default.
- **Thin Controllers**: No business logic in Livewire components. Delegate to Services immediately.

---

## 8. Documentation (PHPDoc)

Every class and method must include a professional PHPDoc in English.

- **Intent**: Briefly describe _why_ the method exists.
- **Parameters**: Clearly type-hint all `@param` tags.
- **Return**: Clearly define the `@return` type.

---

_Adherence to these conventions is not optional. They are verified during the **Iterative Sync
Cycle** (Phase 4 of SDLC) and are a prerequisite for feature completion._
