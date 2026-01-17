# Development Conventions

This document outlines the coding and development conventions for the Internara project. Adhering to
these guidelines ensures consistency, maintainability, and high code quality across the application.
These conventions supplement the broader guidelines found in the root `GEMINI.md` file and align
with the principles detailed in the [Architecture Guide](architecture-guide.md).

---

**Table of Contents**

- [Internara - Development Conventions](#internara---development-conventions)
    - [1. General Conventions](#1-general-conventions)
    - [2. PHP Conventions](#2-php-conventions)
    - [3. Laravel Conventions](#3-laravel-conventions)
    - [4. Modular Architecture (Laravel Modules) Conventions](#4-modular-architecture-laravel-modules-conventions)
        - [4.1 General Module Rules](#41-general-module-rules)
        - [4.2 Service Layer Conventions](#42-service-layer-conventions)
        - [4.3 Inter-Module Communication](#43-inter-module-communication)
        - [4.4 Service Provider Conventions](#44-service-provider-conventions)
    - [5. Role & Permission Conventions](#5-role--permission-conventions)
    - [6. Exception Handling Conventions](#6-exception-handling-conventions)
    - [7. Livewire \& Volt Conventions](#7-livewire--volt-conventions)
    - [8. View \& Component Conventions](#8-view--component-conventions)
        - [8.1 UI Module Component Conventions](#81-ui-module-component-conventions)
        - [8.2 Cross-Module UI Injection](#82-cross-module-ui-injection)
    - [9. Testing (Pest) Conventions](#9-testing-pest-conventions)
    - [10. Code Formatting (Pint)](#10-code-formatting-pint)
    - [11. Tailwind CSS Conventions](#11-tailwind-css-conventions)

---

## 1. General Conventions

- **Language:** All code, comments, and documentation **must be written in English.**
- **Naming:**
    - Use descriptive names for variables, methods, classes, and modules (e.g.,
      `isRegisteredForDiscounts` instead of `discount()`).
    - **Setters:** Use the `set` prefix for methods that modify model state or related resources
      (e.g., `setAvatar()`, `setLogo()`, `setPassword()`) instead of `change` or other prefixes.
- **DRY Principle:** Reuse existing components, classes, and functions before creating new ones.
  Avoid unnecessary duplication.
- **Directory Structure:** Adhere strictly to the existing directory structure. **Do not create new
  base folders without explicit approval.**
- **Comments & PHPDoc:** Use comprehensive PHPDoc for every method and class. Focus comments on
  _why_ a piece of code exists or its complex logic, rather than _what_ it does.

## 2. PHP Conventions

- **Control Structures:** Always use curly braces for control structures (`if`, `for`, `while`,
  etc.), even for single-line statements, to prevent potential errors and improve readability.
- **Constructor Property Promotion:** Utilize PHP 8's constructor property promotion for dependency
  injection and class properties. **Avoid empty constructors.**
- **Type Declarations:** Always use explicit return type declarations and appropriate parameter type
  hints for all methods and properties.
- **PHPDoc:** Prefer comprehensive PHPDoc blocks. Use
  [array shapes](https://docs.phpdoc.org/latest/guides/types.html#array-shapes) in PHPDoc for
  complex arrays where appropriate.
- **Enums:** Enum keys should typically be `TitleCase`.
- **Interface Naming:** Interface names should clearly describe their functionality and **should not
  be suffixed** with `Interface` or `Contract`. For example, use `UserRepository` instead of
  `UserRepositoryInterface` or `UserRepositoryContract`.

## 3. Laravel Conventions

- **Artisan Commands:**
    - Always use `php artisan make:` commands to generate new files to ensure correct scaffolding
      and namespace adherence.
    - Pass `--no-interaction` and necessary options to `make` commands to automate creation.
    - Use `php artisan make:class` for generic PHP classes not covered by more specific `make`
      commands.
- **Database (Eloquent):**
    - Prefer Eloquent relationship methods with explicit return type hints.
    - Use Eloquent models (`Model::query()`) for database interactions over raw SQL queries
      (`DB::`).
    - Employ eager loading (e.g., `with()`) to prevent N+1 query problems.
    - For Laravel 12, utilize native eager load limiting (e.g., `$query->latest()->limit(10);`).
- **UUID Usage:**
    - UUIDs are reserved for tables with high security sensitivity (e.g., `User`) to mitigate
      enumeration attacks.
    - Other tables should use ID types appropriate for their requirements (e.g., `bigIncrements`).
- **Models:**
    - Create [factories](https://laravel.com/docs/12.x/eloquent-factories) and
      [seeders](https://laravel.com/docs/12.x/seeding) for new models.
    - Prefer the `casts()` method over the deprecated `$casts` property.
- **Controllers & Validation:**
    - Always use
      [Form Request classes](https://laravel.com/docs/12.x/validation#form-request-validation) for
      validation logic.
- **Queues:** Use `ShouldQueue` for all time-consuming operations.
- **Configuration:** Access configuration values via `config('app.name')`. **Never use `env()`
  directly outside of configuration files**.

### 3.1. Account Status Management

For entities utilizing the `HasStatus` trait (especially the `User` model), the following
standardized status names must be used to ensure consistency across the system:

- **`active`**: The account is fully operational. This is the default status for verified users.
- **`inactive`**: The account has been administratively disabled. Users with this status cannot log
  in or perform any actions.
- **`pending`**: The account is awaiting further action (e.g., administrator approval or completing
  initial registration steps).

Status transitions must be handled exclusively through the **Service Layer** to ensure auditability
and consistent business rule enforcement.

## 4. Modular Architecture (Laravel Modules) Conventions

Adhere strictly to the modular monolith architecture principles detailed in the
**[Architecture Guide](architecture-guide.md)**.

### 4.1 General Module Rules

- **Structure:** Follow the standard module structure (`modules/{ModuleName}/src/`).
- **Namespace:** Namespaces must **omit the `src` segment**.
    - _Correct:_ `Modules\User\Services\UserService`
    - _Incorrect:_ `Modules\User\src\Services\UserService`
- **Isolation:** Modules **MUST NOT** directly reference concrete classes or internal models of
  other modules.
- **Directory Nesting:** `Contracts` and `Concerns` specific to a domain (e.g., Services,
  Repositories) must be nested within that domain's directory (e.g., `src/Services/Contracts/`).
  Top-level `src/Contracts` is only for generic, module-wide contracts.

#### 4.1.1 Database Isolation Principle (Cross-Module Relations)

To maintain low coupling and ensure modules remain truly portable, **physical foreign key
constraints (`FOREIGN KEY`) MUST NOT be used across module boundaries.**

- **Rationale:** Hard foreign keys create tight coupling at the database level, making it impossible
  to run a module's migrations without its "parent" module being present.
- **Rule:** For cross-module relationships (e.g., `Internship` table referencing `School` table),
  store the foreign ID as a simple `uuid` or `unsignedBigInteger` column and create a manual index.
- **Integrity Enforcement:** Referential integrity **MUST** be managed at the **Application Layer**
  (Models and Services).
    - Use Services to validate existence before creation.
    - Use appropriate deletion logic (e.g., manual cleanup or soft deletes) within Services.
- **Exceptions:** Foreign keys are allowed and encouraged for tables **within the same module**
  (e.g., `internship_placements` referencing `internships`).

### 4.2 Service Layer Conventions

- **Role:** Services orchestrate business logic and interact with Models.
- **Implementation:**
    - Most services should extend `Modules\Shared\Services\EloquentQuery`.
    - Services must implement an interface (contract) that extends
      `Modules\Shared\Services\Contracts\EloquentQuery`.
    - Initialize the associated Model in the constructor using `$this->setModel(new YourModel())`.
- **Factory Access:** **(Mandatory)** Direct access to `Model::factory()` from outside the module is
  forbidden. Always use the Service contract: `app(ServiceContract::class)->factory()`. This ensures
  low-coupling and architectural integrity even in tests and seeders.

### 4.3 Inter-Module Communication

- **Golden Rule:** Communicate only via **Interfaces** (Synchronous) or **Events** (Asynchronous).
- **Service-to-Service:** Type-hint the **Service Interface** of the target module, not the concrete
  class.
- **Events:** Dispatch events for side effects. Listening modules should subscribe to these events
  without the dispatcher knowing.
- **Authorization:** Use `Gate` and `Policy` checks (e.g., `$user->can()`) rather than querying the
  Permission module directly.

### 4.4 Service Provider Conventions

- **Trait Usage:** All module service providers **must** utilize the
  `Modules\Shared\Providers\Concerns\ManagesModuleProvider` trait.
- **Bindings:** Declare explicit bindings in the `bindings()` method. These take precedence over
  auto-discovery.

## 5. Role & Permission Conventions

- **Philosophy:** Policies check for **Permissions**, not **Roles**.
- **Naming:** Permissions must follow `module.action` format (e.g., `user.create`, `post.publish`).
- **Super Admin:** Use `.manage` suffix for a module's "super" permission (e.g., `user.manage`).
- **Centralization:** Define all Permissions and Roles in the `Permission` module's seeders
  (`PermissionSeeder.php`, `RoleSeeder.php`).

## 6. Exception Handling Conventions

- **AppException:** Use `Modules\Exception\AppException` for domain/business logic errors.
- **Localization:**
    - Use translation keys for user messages (`$userMessage`).
    - Format: `{module_name}::exceptions.{key_name}` (e.g., `user::exceptions.super_admin_exists`).
    - Define translations in `modules/{ModuleName}/lang/{locale}/exceptions.php`.
- **RecordNotFound:** Use `Modules\Exception\RecordNotFoundException` for missing data.

## 7. Livewire & Volt Conventions

- **Thin Components:** Livewire components **MUST NOT** contain business logic. Delegate to
  Services.
- **Naming:** Use module-alias naming for embedding: `@livewire('module::component.name')`.
- **Dependency Injection:** Perform DI in the `boot()` method, not the constructor.
- **State:** Validate and authorize all actions. State lives on the server.
- **Volt:** Use Volt for simpler, single-file components where appropriate.

## 8. View & Component Conventions

### 8.1 UI Module Component Conventions

- **Central Library:** The `UI` module (`modules/UI`) is the source of truth for global components.
- **Usage:** Access directly via `ui` namespace: `<x-ui::navbar />`.
- **Preference Order:**
    1.  Existing `UI` Module Components.
    2.  **MaryUI** Components (wrapper for DaisyUI).
    3.  New Custom Component in `UI` Module.

### 8.2 Cross-Module UI Injection

- **Slot System:** Use `@slotRender('slot.name')` to allow other modules to inject UI elements
  (e.g., sidebar items, header actions) without tight coupling.
- **Registration:** Register components to slots via `SlotRegistry::register()` in the Service
  Provider.

## 9. Testing (Pest) Conventions

- **Framework:** All tests **must be written using Pest**.
- **Directory Structure:**
    - App Tests: `tests/Feature`, `tests/Unit`
    - Module Tests: `modules/{ModuleName}/tests/Feature`, `modules/{ModuleName}/tests/Unit`
- **Philosophy:**
    - **Unit Tests:** Isolated, fast, mocked dependencies.
    - **Feature Tests:** Integration, slower, DB/HTTP interaction.
- **Assertions:** Use specific assertions (e.g., `toThrow(AppException::class)`).

## 10. Code Formatting (Pint)

- **Automated Formatting:** Always run `vendor/bin/pint --dirty` before committing.

## 11. Tailwind CSS Conventions

- **Frameworks:** Use **Tailwind CSS v4** + **DaisyUI**.
- **Theming:** Use semantic classes (`btn-primary`, `text-error`) to support Light/Dark modes
  automatically.
- **Responsive:** Mobile-first approach. Use `md:`, `lg:` prefixes.
- **Spacing:** Use Tailwind's spacing scale (`p-4`, `gap-2`).

---

**Navigation**

[← Previous: Conceptual Best Practices](conceptual-best-practices.md) |
[Next: Shared Model Traits →](shared-traits.md)
