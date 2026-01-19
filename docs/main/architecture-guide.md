# Architecture Guide

Welcome, developer! This guide provides a comprehensive understanding of Internara's modular
architecture, helping you build features efficiently and maintain a robust codebase.

---

**Table of Contents**

1.  [Core Architectural Principles](#1-core-architectural-principles)
    - [1.1 Global Application & Infrastructure Layer (The "App" Module)](#11-global-application--infrastructure-layer-the-app-module)
    - [1.2 Modular Domain Layer](#12-modular-domain-layer)
2.  [The TALL Stack](#2-the-tall-stack)
3.  [The Layered Architecture](#3-the-layered-architecture)
    - [3.1 UI Layer: Livewire Components](#31-ui-layer-livewire-components)
    - [3.2 Business Logic Layer: Services](#32-business-logic-layer-services)
    - [3.3 Data Layer: Eloquent Models](#33-data-layer-eloquent-models)
    - [3.4 Optional Layers: Repositories & Entities](#34-optional-layers-repositories--entities)
4.  [Pragmatic Layered Architecture & Communication Rules](#4-pragmatic-layered-architecture--communication-rules)
    - [4.1 The Golden Rule of Layered Communication](#41-the-golden-rule-of-layered-communication)
    - [4.2 Interface-First & Knowledge Hiding](#42-interface-first--knowledge-hiding)
5.  [Inter-Module Communication: The Golden Rule](#5-inter-module-communication-the-golden-rule)
    - [5.1 Database Isolation Principle](#51-database-isolation-principle)
    - [5.2 Pattern 1: Service-to-Service Calls (Synchronous)](#52-pattern-1-service-to-service-calls-synchronous)
    - [5.3 Pattern 2: Events & Listeners (Decoupled Actions)](#53-pattern-2-events--listeners-decoupled-actions)
    - [5.4 Pattern 3: Standard Framework Interfaces (Policies/Gates)](#54-pattern-3-standard-framework-interfaces-policiesgates)
6.  [Practical Guide: Module Usage](#6-practical-guide-module-usage)
    - [6.1 Accessing Module Resources](#61-accessing-module-resources)
    - [6.2 Essential Module Artisan Commands](#62-essential-module-artisan-commands)

---

## 1. Core Architectural Principles

Internara is structured with a clear separation between **Global Application Concerns** (framework &
infrastructure) and **Modular Domain Concerns**.

### 1.1 Global Application & Infrastructure Layer (The "App" Module)

The `app/` directory at the project root functions as the **Infrastructure Layer** and **Framework
Integration Endpoint**. Unlike `modules/` which focus on business domains, `app/` handles the
technical aspects underlying the entire system.

Despite its distinct role, **`app/` is treated like the "App" module** regarding development
standards for specific parts:

- **Infrastructure & Framework Integration**: Home for Service Providers, Middleware, Artisan
  Commands, and other framework integrations not tied to a specific domain.
- **Service Parity**: Infrastructure-level Services (e.g., `AuditLogService` or
  `GlobalSettingService`) **must** follow the **Interface-First** convention (using
  `app/Services/Contracts`) and are registered via **Auto-Discovery** within the `App` namespace.
- **Base Layers**: Houses _base classes_ or _traits_ extended by all modules (e.g., `BaseService`,
  `BaseModel`).
- **Namespace Standard**: All components under `app/` use the `App\` root namespace, which is
  treated equally to the `Modules\` namespace by the binding system.

---

### 1.2 Modular Domain Layer

This layer embodies the **Modular Monolith** pattern, where Internara is a collection of
self-contained mini-applications (called **Modules**) residing within the `modules/` directory. Each
module (e.g., `User`, `Internship`) manages a specific business domain, enhancing organization,
maintainability, and scalability.

**Key Concepts for Modular Layer:**

- **Modular Monolith:** Leveraging `nwidart/laravel-modules` to encapsulate specific business
  domains comprehensively.
- **Isolation & Portability:** Each module is designed to be as self-contained as possible for its
  domain.
    - **Independence:** Modules **MUST NOT** depend on the physical existence or internal logic of
      other modules directly.
    - **External Dependencies:** Modules may depend on the Laravel framework or reputable external
      packages (e.g., Spatie) with full awareness and careful restriction.
    - **Self-Containment:** A module encapsulates its UI, business logic, and data structures. It
      should be extractable with minimal friction.
- **UI Framework:** All user interfaces and interactivity within modules are built using
  **Livewire** components, powered by the TALL Stack.
- **Module-Specific Backend Structure:** Logic within each module is organized into distinct layers:
    - **Services:** Orchestrate the module's business logic ("what to do").
    - **Models:** Represent the module's persisted domain data and contain limited, domain-relevant
      behavior.
    - **Optional Layers (Repositories/Entities):** Abstractions for complex data access or
      integrations, **not included by default within modules**.
- **Namespace Convention:** For module files located in
  `modules/{ModuleName}/src/{Subdirectory}/{FileName}.php`, the namespace **must omit the `src`
  segment**.
    - **Example:** `Modules\{ModuleName}\{Subdirectory}`. This applies to `Livewire`, `Services`,
      `Models`, and other subdirectories within a module's `src` folder.

For a complete, step-by-step example of creating a new feature within this architecture, refer to
the **[Development Workflow Guide](development-workflow.md)**.

## 2. The TALL Stack

The project is built on the TALL stack, a full-stack development solution that allows for building
robust, modern web applications primarily using PHP.

- **Tailwind CSS (v4):** A utility-first CSS framework that provides low-level utility classes to
  build custom designs directly in your markup. This approach avoids premature abstraction and
  allows for highly customizable and maintainable styling. To ensure consistency and rapid
  development, we use the **DaisyUI** component library and **MaryUI** for advanced Livewire
  interactions.
- **Alpine.js:** A rugged, minimal JavaScript framework for adding client-side interactivity to your
  templates. It's often used for small interactions like dropdowns or toggles. In this project,
  Alpine.js is automatically included with Livewire 3, making it readily available within Livewire
  components.
- **Laravel (v12):** The core backend framework providing the application's foundation. It handles
  routing, database management through its Eloquent ORM, authentication, and the overall structure
  that enables the modular monolith architecture.
- **Livewire (v3):** A full-stack framework for Laravel that allows you to build dynamic, reactive
  interfaces using PHP classes and Blade templates. It eliminates the need for a separate frontend
  JavaScript framework like React or Vue for most use cases, significantly simplifying the
  development workflow. This project also uses **Volt**, which allows for creating single-file
  Livewire components.

## 3. The Layered Architecture

Each module adheres to a strict layered architecture to enforce separation of concerns. The flow of
data and control generally moves from the UI layer downwards.

**Important Note on Namespaces:** Consistent with the project's namespace convention, all classes
within these layers will use a namespace that omits the `src` segment. For instance, a Service in
the `User` module would be namespaced `Modules\User\Services`, not `Modules\User\src\Services`.

### 3.1 UI Layer: Livewire Components

The entry point for user interaction with the application. Livewire components serve as the
**Presentation Layer**, responsible solely for rendering the UI and handling user input.

- **Purpose:** To capture user input, manage component state, and display data.
- **Responsibilities:**
    - **UI Interaction Logic ONLY:** Livewire components **MUST NOT** contain business logic. Their
      role is strictly limited to handling UI events, managing component state, and orchestrating
      interactions with the Service layer.
    - **Delegation to Services:** All business operations (e.g., data creation, updates, deletions,
      complex validations, authorization checks beyond simple rendering conditions) **MUST** be
      delegated to the appropriate Service methods.
    - **Authorization:** Use standard Laravel `can()` methods or Policies for UI rendering
      conditions and action authorization.
    - Displays data retrieved from the business logic layer.
- **Location:** `modules/ModuleName/src/Livewire/`
- **Creation Command:**
    ```bash
    php artisan module:make-livewire CreateUser User --view
    ```

#### Dependency Injection Convention

A critical convention for Livewire components is that all dependency injection **must** be performed
in the `boot()` method, not the constructor. This is due to Livewire's component lifecycle. Using
the constructor for DI can lead to unexpected behavior.

For more details and code examples, refer to the
[Livewire Integration Guide](packages/livewire.md#3-dependency-injection-in-livewire-components).

#### Livewire Component Naming in Modular Monolith

When embedding Livewire components within Blade views in a modular monolith (using
`nwidart/laravel-modules`), it is crucial to use the correct naming convention to ensure
auto-discovery and proper rendering.

- **Standard Embedding Directive:** Always use the `@livewire` Blade directive for embedding
  interactive Livewire components.
- **Module-Aliased Component Name:** Components residing within a module
  (`Modules\{ModuleName}\src\Livewire\Subdirectory\ComponentName.php`) should be referenced using
  the module's alias followed by a double colon (`::`), then the dot-notation path relative to the
  module's `src/Livewire` directory. The module alias typically matches the module's lowercase name
  (e.g., `user` for the `User` module).

    **Syntax:** `@livewire('{module-alias}::{component-dot-notation-name}', [...$parameters])`

    **Example:** For a Livewire component class: `Modules\User\src\Livewire\Users\DeleteUser.php`
    Its corresponding component name would be: `users.delete-user` The correct embedding in a Blade
    view would be:

    ```blade
    @livewire('user::users.delete-user')
    ```

    or with parameters:

    ```blade
    @livewire('user::users.delete-user', ['userId' => $user->id])
    ```

- **Avoid `<x-livewire-... />` for interactive embedding:** While `<x-livewire-... />` syntax works
  as a Blade Component alias, the `@livewire` directive is the recommended and clearer way to embed
  interactive Livewire components, especially when passing parameters or dealing with specific
  component lifecycle events.

### 3.2 Business Logic Layer: Services

The "brain" of your feature, orchestrating specific business operations.

- **Purpose:** To implement and coordinate the core business logic.
- **Responsibilities:**
    - Receives data (often as validated arrays or simple value objects) from the UI layer.
    - Performs domain-specific validation and business rules.
    - Interacts directly with **Eloquent Models** to save or retrieve data (in the default
      workflow).
    - Dispatches events to notify other parts of the application about changes.
    - Should be thin and focused on a single business concern.
- **Location:** `modules/ModuleName/src/Services/`
- **Creation Command:**
    ```bash
    php artisan module:make-service UserService User
    ```

### 3.3 Data Layer: Eloquent Models

The primary layer for interacting with the database.

- **Purpose:** To represent persisted domain data and provide an interface to the database via
  Eloquent ORM.
- **Responsibilities:**
    - Defining database relationships, scopes, and accessors.
    - Handling data persistence and retrieval.
    - Supporting configurable ID types (UUID or Integer).
    - May contain limited, domain-relevant behavior (e.g., helper methods like `isActive()`).
- **Location:** `modules/ModuleName/src/Models/`
- **Creation Command:**
    ```bash
    php artisan module:make-model User User --migration
    ```

### 3.4 Optional Layers: Repositories & Entities

These layers are **not included by default** and should only be introduced when strictly justified
by architectural needs (e.g., swapping storage backends, complex external integrations, or strict
decoupling requirements).

#### Repositories

- **Purpose:** To abstract data persistence details, completely decoupling business logic from
  Eloquent.
- **When to Use:** When you need to support multiple data sources (e.g., Database AND API) or when
  testing requires strict mocking of the data layer without touching the database.
- **Location:** `modules/ModuleName/src/Repositories/`

#### Entities (DTOs)

- **Purpose:** Plain PHP objects to transfer data between layers, independent of Eloquent.
- **When to Use:** When data structures are complex, come from external sources, or need to be
  strictly decoupled from the database schema.
- **Location:** `modules/ModuleName/src/Entities/`

## 4. Pragmatic Layered Architecture & Communication Rules

To ensure maintainability while avoiding over-engineering, Internara adopts a **Pragmatic Layered
Architecture**.

### 4.1 The Golden Rule of Layered Communication

Communication flows downwards.

- **Presentation Layer (Livewire)**: Communicates only with the **Service Layer**.
- **Service Layer**: Communicates with the **Model Layer** (or optionally, the **Repository
  Layer**).
- **Model Layer**: Represents the persistence mechanism and should not initiate communication
  upwards.

### 4.2 Interface-First & Standard Authorization

When communicating _between modules_, strictly adhere to the **Interface-First Principle** or use
standard Laravel interfaces.

- **Service Interaction:** If Module A needs to call a service in Module B, it should depend on an
  interface (e.g., `Modules\User\Services\Contracts\UserService`), not the concrete class.
- **Example:** The `AuthService` (Auth module) depends on the `UserService` (User module) to handle
  user creation. It type-hints the `UserService` interface, allowing the User module to change its
  internal implementation without affecting the Auth module.
- **Authorization Interaction:** Modules should use Laravel's standard `Gate` and `Policy` systems
  (e.g., `$user->can()`) to interact with the authorization layer, ensuring modules remain isolated
  from the specific implementation of Role/Permission management.

## 5. Inter-Module Communication: The Golden Rule

The most critical rule for maintaining a loosely coupled and maintainable modular monolith is the
**Isolation Principle**.

> **Golden Rule:** One module **MUST NOT** directly reference a concrete class (e.g., a `Service` or
> `Repository` implementation) or an internal model from another module. All interaction between
> modules **must** happen through shared interfaces (contracts), events, or framework standards
> (Gates/Policies).

### 5.1. Database Isolation Principle

To achieve true decoupling, the isolation principle extends to the database schema. **Cross-module
foreign key constraints are strictly forbidden.**

- **Manual Indexes Only:** When a table in `Module A` references an entity in `Module B`, use a
  simple data column (e.g., `$table->uuid('module_b_id')->index();`) instead of
  `$table->foreignUuid()->constrained();`.
- **Application-Level Integrity:** Data integrity must be guarded by the **Service Layer**. Before
  saving a record, the Service should use the related module's Interface to verify that the provided
  ID exists.
- **No Cascading Deletes:** Automated cascading deletes across modules are replaced by event-driven
  cleanup or explicit logic within Services.

### 5.2. Pattern 1: Service-to-Service Calls (Synchronous)

Use this pattern when an action in `Module A` **immediately** requires a result or a direct
interaction with `Module B`.

- **Usage:** For direct, synchronous dependencies.
- **Real-world Example:** The `UserService` (in `User` module) requires initializing a profile
  whenever a user is created.
    1.  `UserService` type-hints `Modules\Profile\Services\Contracts\ProfileService`.
    2.  The `Profile` module provides the implementation via its `ProfileServiceProvider`.
    3.  `UserService` calls `$this->profileService->getByUserId($user->id)` without knowing about
        the `Profile` model or its internal logic.
- **How it Works:**
    1.  `Module B` defines a `Service Interface` (e.g.,
        `Modules\User\Services\Contracts\UserService`) and provides a concrete implementation.
    2.  `Module A` (e.g., `InternshipService`) _type-hints_ `Module B`'s `Service Interface` in its
        constructor.
    3.  Laravel's Service Container automatically injects the concrete `UserService` implementation
        without `InternshipService` knowing the specific class.

### 5.3. Pattern 2: Events & Listeners (Decoupled Actions)

This is the preferred approach for handling side effects.

- **Usage:** When an action occurs in `Module A`, and other modules might need to react to it.
- **Real-world Example:** When a user's email is verified in the `Auth` module.
    1.  `AuthService` dispatches the standard Laravel `Illuminate\Auth\Events\Verified` event.
    2.  Other modules (like `Internship` or `Notification`) can listen to this event to unlock
        features or send congratulatory messages without the `Auth` module being aware of them.
- **How it Works:**
    1.  `Module A` (e.g., `InternshipService`) dispatches an `Event`. This event should carry
        necessary data.
    2.  Other modules (e.g., `User`, `Notification`) define `Listeners` that are configured to react
        to this specific `Event`.
    3.  The `Internship` module remains completely unaware of which other modules react to its
        event, ensuring strong decoupling.

### 5.4 Pattern 3: Standard Framework Interfaces (Policies/Gates)

Use this pattern for cross-cutting concerns like authorization.

- **Usage:** When a domain module needs to authorize an action based on roles or permissions.
- **How it Works:**
    1.  A domain module (e.g., `User`) defines its own `Policy`.
    2.  Roles and permissions are defined and assigned across relevant module seeders (e.g., `Core`
        for base roles, individual modules for their specific permissions).
    3.  The `Permission` module handles the underlying storage logic.
    4.  The domain module simply calls `$user->can('permission.name')`. It doesn't need to know
        about the `Permission` module or the specific models being used.

---

**Navigation**

[← Previous: Installation Guide](installation-guide.md) |
[Next: Module Inventory →](modules/table-of-contents.md)
