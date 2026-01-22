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

### 3.2 Database & Migrations

- **Tables**: Snake_case, plural (e.g., `internship_registrations`).
- **Columns**: Snake_case (e.g., `academic_year`).
- **Foreign Keys**: Must be simple **UUID** columns with indexes. **Physical foreign key constraints between modules are prohibited.**

---

## 4. Internationalization (i11n)

Internara is a **Multi-Language Application** (Indonesian & English).

- **Hardcoding Prohibited**: **Never** write raw text in Views, Controllers, or Services.
- **Translation Helper**: Always use `__('module::file.key')` or `@lang`.
- **Locale Awareness**: Code must respect the active locale (`id` or `en`) when formatting dates or currency.

---

## 5. Domain Logic & Service Layer

The Service Layer is the **Single Source of Truth** for business logic.

### 5.1 Service Design
- **Contract-First**: When interacting across modules, depend on **Contracts**, never concrete classes.
- **Role Awareness**: Business logic must explicitly handle the roles defined in Specs (Instructor, Staff, Student, Industry Supervisor).
- **Inheritance**: CRUD services should extend `Modules\Shared\Services\EloquentQuery`.

### 5.2 Configuration & Settings
- **No `env()`**: Never call `env()` directly in application code.
- **Infrastructure Config**: Use `config('app.timezone')` for static infrastructure values.
- **Dynamic Application Settings**: Use the `setting($key, $default)` helper for all business values (e.g., `site_title`, `brand_logo`, `contact_email`). **Hard-coding these values is strictly prohibited.**

---

## 6. UI/UX Implementation

While visual guidelines are in the **[UI/UX Guide](ui-ux-development-guide.md)**, code conventions apply here:

- **Mobile-First Structure**: Livewire components must be structured to support mobile views by default.
- **Thin Controllers**: No business logic in Livewire components. Delegate to Services immediately.

---

## 7. Documentation (PHPDoc)

Every class and method must include a professional PHPDoc in English.

- **Intent**: Briefly describe _why_ the method exists.
- **Parameters**: Clearly type-hint all `@param` tags.
- **Return**: Clearly define the `@return` type.

---

_Adherence to these conventions is not optional. They are verified during the **Iterative Sync Cycle** (Phase 4 of SDLC) and are a prerequisite for feature completion._