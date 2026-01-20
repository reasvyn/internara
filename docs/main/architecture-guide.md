# Architecture Guide: The Internara Modular Monolith

Welcome to the Internara engineering core. This guide provides a deep-dive into our **Modular
Monolith** architecture. By following these principles, you ensure that the features you build are
scalable, maintainable, and decoupled from the start.

---

## 1. Core Architectural Philosophy

Internara is built as a collection of self-contained business domains (Modules) within a single
Laravel application. We prioritize **Pragmatic Modularity**: enough isolation to manage complexity,
but enough integration to maintain high development velocity.

### 1.1 The "Infrastructure" Layer (`app/` & `modules/Core`)

The root `app/` directory and the `Core` module handle cross-cutting technical concerns:

- **Service Providers**: Bootstrapping the framework and modular auto-discovery.
- **Base Classes**: Abstract definitions extended by modules (e.g., `BaseModel`, `BaseService`).
- **Infrastructure Migrations**: The `Core` module houses foundational system migrations such as 
  `jobs`, `failed_jobs`, and `cache` tables.
- **Infrastructure Services**: Technical services like `AuditLog` or `Settings`.

---

## 2. The TALL Stack: Our UI Engine

### 2.1 Global Notification Bridge
To ensure a consistent user experience, we implement a **Global Notification Bridge** in the base 
layout. This bridge listens for `notify` events dispatched from individual components and 
translates them into `mary-toast` notifications. This ensures that background job feedback or 
service-level exceptions reach the user regardless of their current view.

---

## 3. Layered Architecture (Inside a Module)

Every module follows a strict 3-tier internal structure to ensure a "separation of concerns."

### 3.1 UI Layer: Presentation & Interaction

**Location:** `src/Livewire/` The UI layer is responsible for **presentation logic only**. It
captures user input and displays results.

- **Convention:** All business operations **MUST** be delegated to the Service layer.
- **DI Rule:** Inject dependencies in the `boot()` method, never the constructor.
- **Naming:** Refer to components via their alias (e.g., `@livewire('user::users.delete-user')`).

### 3.2 Business Logic Layer: Services

**Location:** `src/Services/` The "Brain" of the module. Services orchestrate business rules and
cross-model operations.

- **Base Service**: Most services extend `Modules\Shared\Services\EloquentQuery` for standardized
  CRUD.
- **Input**: Services accept validated arrays or DTOs—never `Request` objects.
- **Output**: Returns models, collections, or simple data structures.

### 3.3 Data Layer: Eloquent Models

**Location:** `src/Models/` Represents persisted state.

- **Logic:** Keep logic here minimal (e.g., `isActive()`, `getFullnameAttribute()`).
- **Relationship Rules**: Models should only have `belongsTo` or `hasMany` relationships with models
  **within their own module**. Cross-module relations must be handled via Services.

### 3.4 Optional: Repositories & Entities

Only introduce these if you are swapping data sources or dealing with complex external APIs. **Avoid
premature abstraction.**

---

## 4. Communication & Isolation Rules

The strength of a Modular Monolith lies in how modules _don't_ talk to each other.

### 4.1 The Golden Rule of Communication

> **Modules MUST NOT reference another module's concrete implementation or models directly.**

### 4.2 Database Isolation

**Physical foreign keys across modules are forbidden.**

- Use simple indexed columns (e.g., `module_b_id` as UUID).
- **Why?** It prevents a "death by a thousand joins" and allows modules to be refactored or deleted
  without cascading database failures.
- **Data Integrity**: Managed by the **Service Layer** of the module owning the data.

### 4.3 Pattern 1: Service-to-Service (Synchronous)

If `Module A` needs data from `Module B`, it must type-hint a **Contract**.

- **Example**: `AuthService` needs to create a user. It injects `UserService`, not the concrete
  `UserServiceProvider` implementation.

### 4.4 Pattern 2: Events & Listeners (Asynchronous)

The preferred way to handle side-effects.

- **Example**: When an internship is completed, the `Internship` module dispatches
  `InternshipCompleted`. The `Certificate` and `Notification` modules listen and react
  independently.

### 4.5 Pattern 3: Framework Standards (Authorization)

Modules should use standard Laravel `Policies` and `Gates`.

- **Example**: `$user->can('assessment.create')`. The `Assessment` module doesn't care how the
  `Permission` module stores that capability.

---

_Adhering to this architecture ensures Internara remains clean, predictable, and joy to develop.
When in doubt, prioritize module isolation over clever code reuse._
