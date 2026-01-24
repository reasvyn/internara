# Architecture Guide: The Internara Modular Monolith

Welcome to the Internara engineering core. This guide provides a deep-dive into our **Modular
Monolith** architecture. By following these principles, you ensure that the features you build are
scalable, maintainable, and decoupled from the start.

> **Critical Context:** This guide implements the technical direction mandated by the authoritative
> **[Internara Specs](../internal/internara-specs.md)**. All architectural decisions must align with
> the product goals, user roles, and constraints defined therein.

---

## 1. Core Architectural Philosophy

Internara is built as a collection of self-contained business domains (Modules) within a single
Laravel application. We prioritize **Pragmatic Modularity**: enough isolation to manage complexity,
but enough integration to maintain high development velocity.

### 1.1 Pragmatic Modularity

We build modules to **manage complexity**, not to create extra work.

- **Do:** Create a new module when a domain concept has its own lifecycle, distinct data, and clear
  boundaries (e.g., `Internship`, `Assessment`).
- **Don't:** Create a module for every single database table or helper function.
- **Test:** "If I delete this module folder, does the rest of the app still compile (mostly)?" If
  yes, you have good isolation.

### 1.2 Service-Oriented "Brain"

The **Service Layer** is the single source of truth for business logic.

- **Controllers are dumb:** They validate input and call a service.
- **Models are dumb:** They hold data and relationships.
- **Services are smart:** They know _how_ to register a student, calculate a grade, or generate a
  report.

### 1.3 Explicit over Implicit

Magic is fun, but clarity is maintainable.

- **Prefer:** Explicit dependency injection over Facades (where possible/reasonable).
- **Prefer:** Named contracts (`StudentServiceInterface`) over generic containers.
- **Avoid:** "Magic" string parsing or hidden side-effects.

### 1.4 The "Infrastructure" Layer (`app/` & `modules/Core`)

The root `app/` directory and the `Core` module handle cross-cutting technical concerns:

- **Service Providers**: Bootstrapping the framework and modular auto-discovery.
- **Base Classes**: Abstract definitions extended by modules (e.g., `BaseModel`, `BaseService`).
- **Infrastructure Migrations**: The `Core` module houses foundational system migrations such as
  `jobs`, `failed_jobs`, and `cache` tables.
- **Infrastructure Services**: Technical services like `AuditLog` or `Settings`.
- **Dynamic Settings**: Application settings (brand name, logo, site title) must never be
  hard-coded. They are managed via the `Setting` module and accessed using the `setting()` helper.

### 1.5 Standardized Bootstrapping (`ManagesModuleProvider`)

The `ManagesModuleProvider` trait in the `Shared` module automates modular bootstrapping. It ensures
that adding a new module is consistent with the Internara architecture.

#### Automated Features:

- **`registerModule()`**:
    - **Config**: Recursively merges all files in `modules/{Module}/config/*.php`.
    - **Bindings**: Automatically maps **Contracts** to implementations based on the
      **Auto-Discovery** rules.
- **`bootModule()`**:
    - **Translations (i11n)**: Registers the module namespace for **Multi-Language** support (e.g.,
      `__('user::exceptions')`).
    - **Views (Mobile-First)**: Registers the Blade namespace for responsive components.
    - **Migrations**: Automatically loads modular migrations (UUID-based, No physical FKs).
    - **Settings**: Hooks into the `Setting` module to ensure `setting()` helper availability.

#### Advanced Binding Logic

The `bindings()` method is used for manual Dependency Injection (DI) overrides.

```php
protected function bindings(): array
{
    return [
        // Map Contract to Concrete implementation (Omit 'Interface' suffix)
        \Modules\User\Services\Contracts\UserService::class =>
            \Modules\User\Services\UserService::class,
    ];
}
```

#### Bootstrapping Best Practices

1. **Strict Identification**: Define `protected string $name = 'ModuleName'`.
2. **English-First**: All metadata and PHPDoc within the provider must be in English.
3. **No Hard-Coding**: Provider logic must not hard-code environment values; use `config()` or
   `setting()`.

---

## 2. Dependency Injection & Auto-Discovery

To facilitate easy cross-module communication and minimize Service Provider clutter, Internara
utilizes an **Auto-Discovery** system for its service layer. This system automatically maps
**Contracts** (Interfaces) to their concrete implementations based on directory structure.

### 2.1 How It Works

The system scans the following directories for PHP classes:

- `app/Services`
- `modules/{ModuleName}/src/Services`

#### The Directory Pattern

The auto-discovery engine expects a specific layout:

1.  **Contract**: Stored in `Services/Contracts/` (e.g., `UserService.php`).
    - _Note:_ Internara convention omits the `Interface` suffix for contracts.
2.  **Implementation**: Stored directly in `Services/` (e.g., `UserService.php`).
    - _Note:_ Implementation and Contract share the same name but live in different namespaces.

If a class in `Services/` implements an interface found in its own `Contracts/` subdirectory, the
binding is registered automatically in the Laravel Service Container.

### 2.2 Cross-Module Communication Rules

Per our **Modular Monolith** architecture:

- **Strict Rule:** Always type-hint the **Contract**, never the concrete implementation when
  injecting services from another module.
- **Example:**
    ```php
    // CORRECT: Injecting the contract
    public function __construct(
        protected UserServiceInterface $userService
    ) {}
    ```

### 2.3 Configuration & Caching

- **Configuration**: Use `config/bindings.php` to manually register complex bindings or override
  auto-discovered ones.
- **Caching**: For production performance, the discovered bindings are cached.
    - **Refresh Cache**: `php artisan app:refresh-bindings`

---

## 3. The TALL Stack: Our UI Engine

**Stack Versions:** Laravel v12, Livewire v3, AlpineJS v4, Tailwind CSS v4.

### 3.1 Global Notification Bridge

To ensure a consistent user experience, we implement a **Global Notification Bridge** in the base
layout. This bridge listens for `notify` events dispatched from individual components and translates
them into `mary-toast` notifications.

### 3.2 Mobile-First Responsiveness

The specs mandate a **Mobile-First** design strategy.

- **Responsive Components:** Livewire components and Blade templates must be built using mobile
  layouts as the default, with `md:`, `lg:`, and `xl:` overrides for larger screens.
- **Touch-Friendly:** UI interactions (buttons, menus) must be sized and spaced for touch
  interfaces.

---

## 4. Layered Architecture (Inside a Module)

Every module follows a strict 3-tier internal structure.

### 4.1 UI Layer: Presentation & Interaction

**Location:** `src/Livewire/` (Volt)

- **Convention:** All business operations **MUST** be delegated to the Service layer.
- **DI Rule:** Inject dependencies in the `boot()` method, never the constructor.

### 4.2 Business Logic Layer: Services

**Location:** `src/Services/`

- **Base Service**: Most services extend `Modules\Shared\Services\EloquentQuery` for standardized
  CRUD.
- **Input**: Services accept validated arrays or DTOs—never `Request` objects.
- **Output**: Returns models, collections, or simple data structures.

### 4.3 Data Layer: Eloquent Models

**Location:** `src/Models/`

- **Relationship Rules**: Models should only have `belongsTo` or `hasMany` relationships with models
  **within their own module**. Cross-module relations must be handled via Services.

---

## 5. Communication & Isolation Rules

### 5.1 Strict Isolation Principle

To maintain modular portability, Internara enforces a **Strict Isolation** policy for cross-module
communication:

- **No Direct Class Access**: Modules must never instantiate or call concrete classes (especially
  Models) from another module directly below the Services/Utilities layer.
- **Contract-Only Communication**: All interactions between modules must be performed via **Service
  Contracts (Interfaces)** or designated Service classes.
- **Testing Boundaries**: This isolation also applies to testing. Feature tests for one module must
  not directly interact with the concrete models of another; they should rely on Services,
  Contracts, or Factories.
- **Exceptions**: The only concrete classes allowed to be called directly are:
    - **Static Classes or Facades** specifically designed for public use (e.g., helper utilities).
    - **Extreme Edge Cases**: Only when no other architectural path exists and isolation is
      impossible to maintain via contracts.

### 5.2 Database Isolation

**Physical foreign keys across modules are forbidden.**

- Use simple indexed columns (e.g., `student_id` as UUID).
- **Data Integrity**: Managed by the **Service Layer** of the module owning the data.

### 5.3 Pattern 1: Service-to-Service (Synchronous)

If `Module A` needs data from `Module B`, it must type-hint a **Contract**.

### 5.4 Pattern 2: Events & Listeners (Asynchronous)

The preferred way to handle side-effects. When an `Internship` is completed, the module dispatches
`InternshipCompleted`.

### 5.5 Pattern 3: Framework Standards (Authorization)

Modules should use standard Laravel `Policies` and `Gates`.

---

_Adhering to this architecture ensures Internara remains clean, predictable, and joy to develop.
When in doubt, prioritize module isolation over clever code reuse._
