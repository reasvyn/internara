# Development Conventions: Engineering Standards

To ensure that the Internara codebase remains clean, predictable, and accessible to all developers,
we adhere to a strict set of coding conventions. Consistency is the foundation of our
maintainability.

---

## 1. PSR Compliance & Formatting

We follow the standard PHP recommendations to ensure interoperability and readability.

- **PSR-12**: All PHP code must adhere to the PSR-12 Extended Coding Style Guide.
- **Strict Typing**: Use strict typing (`declare(strict_types=1);`) where possible to prevent
  unexpected type coercion.
- **Pint**: We use **Laravel Pint** for automated linting. Always run `./vendor/bin/pint` before
  committing changes.

---

## 2. Modular Namespace Convention (The `src` Rule)

A critical rule in Internara is the handling of the `src` directory within modules.

- **Directory Structure**: Module logic is located in `modules/{ModuleName}/src/`.
- **Namespace Rule**: The `src` segment **MUST be omitted** from the namespace definition.
- **Why?** This keeps namespaces short, semantic, and aligned with standard Laravel conventions if
  the module were to be moved.

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
    - **General Concerns**: Located in `Concerns/` (e.g., `HandlesAppException`).
- **Enums**: PascalCase, located in `src/Enums/`. Used for standardizing types, statuses, and fixed 
  options.

### 3.2 Database & Migrations

- **Tables**: Snake_case, plural (e.g., `internship_registrations`).
- **Columns**: Snake_case (e.g., `academic_year`).
- **Foreign Keys**: Must be simple UUID columns, no physical constraints across modules.

---

## 4. Multi-Language Support

Internara is built for a global audience. **Hardcoding strings in views or controllers is
prohibited.**

- **Locales**: Supported: English (`en`) and Indonesian (`id`).
- **Translations**: Always use the `__()` helper or `@lang` directive.
- **Keys**: Organize keys by module (e.g., `__('user::messages.created')`).

---

## 5. Domain Logic & Service Layer

- **Service Injection**: When cross-module communication is needed, type-hint the **Contract**, not
  the concrete service.
- **EloquentQuery**: CRUD services must extend `Modules\Shared\Services\EloquentQuery` to reuse
  standard logic.
- **No `env()`**: Never call `env()` directly in the code. Use `config()` for infrastructure
  settings and `setting()` for application-level data.

---

## 6. Documentation (PHPDoc)

Every class and method must include a professional PHPDoc in English.

- **Intent**: Briefly describe _why_ the method exists.
- **Parameters**: Clearly type-hint all `@param` tags.
- **Return**: Clearly define the `@return` type.

---

_Adherence to these conventions is not optional. They are verified during the **Iterative Sync
Cycle** and are a prerequisite for feature completion._
