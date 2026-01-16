# Modular Monolith Workflow Guide

This document provides a practical, step-by-step guide for developers on how to implement new
features within the modular architecture of the Internara application. It builds upon the
foundational concepts introduced in the [Architecture Guide](architecture.md).

---

**Table of Contents**

- [1. Core Philosophy](#1-core-philosophy)
- [2. Key Concepts: Eloquent Models vs. Services](#2-key-concepts-eloquent-models-vs-services)
- [3. Standard Workflow (Service-Model)](#3-standard-workflow-service-model)
    - [Step 1: Create the Module](#step-1-create-the-module)
    - [Step 2: Create the Eloquent Model and Migration](#step-2-create-the-eloquent-model-and-migration)
    - [Step 3: Create the Service (Interface and Implementation)](#step-3-create-the-service-interface-and-implementation)
    - [Step 4: Create the UI Component](#step-4-create-the-ui-component)
    - [Step 5: Create the Route](#step-5-create-the-route)
- [4. Advanced Workflow (Optional: Repositories & Entities)](#4-advanced-workflow-optional-repositories--entities)
- [5. Data Flow Summary](#5-data-flow-summary)
- [6. Testing Your Module](#6-testing-your-module)

---

## 1. Core Philosophy

Internara's modular architecture enforces a strict separation of concerns, with each module acting
as a self-contained unit for a specific business domain. This workflow guide operates under these
foundational principles. For a comprehensive overview of architectural principles, including the
Isolation Principle and Namespace Conventions, refer to the
**[Architecture Guide](architecture.md)**, the
**[Development Conventions](development-conventions.md)**, and the
**[Foundational Module Philosophy Guide](foundational-module-philosophy.md)**.

## 2. Key Concepts: Eloquent Models vs. Services

- **Eloquent Model (`Modules/User/Models/User.php`):**
    - Represents the database table and handles persistence.
    - Handles configurable ID types (UUID or Integer).
    - **It is our data persistence object.**

- **Service (`Modules/User/Services/UserService.php`):**
    - Encapsulates the core business logic and rules.
    - Orchestrates operations on Models.
    - The entry point for UI components to interact with the domain.

## 3. Standard Workflow (Service-Model)

### Step 1: Create the Module

If your feature requires a new domain, start by creating a new module.

```bash
php artisan module:make User
```

### Step 2: Create the Eloquent Model and Migration

The **Eloquent Model** defines your direct interface to the database table. Ensure you use
configurable ID types (matching the User ID type) if the module needs to be portable.

```bash
php artisan module:make-model User User --migration
```

### Step 3: Create the Service (Interface and Implementation)

The service encapsulates all business logic. First, create the contract (interface), then the
implementation.

**1. Create the Service Interface** Generate the contract in `src/Contracts/Services/`:

```bash
php artisan module:make-interface Services/UserService User
```

For services based on `EloquentQuery`, this interface should extend the base contract.

```php
// modules/User/src/Contracts/Services/UserService.php
<?php namespace Modules\User\Contracts\Services;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<\Modules\User\Models\User>
 */
interface UserService extends EloquentQuery
{
    // Add custom, non-CRUD method signatures here
}
```

**2. Create the Service Implementation** Generate the concrete service class in `src/Services/`:

```bash
php artisan module:make-service UserService User
```

**Using the `EloquentQuery` Base Class (Recommended)**

For most services that manage a single Eloquent model, you should extend
`Modules\Shared\Services\EloquentQuery`. This provides a complete set of CRUD and query methods out
of the box, reducing boilerplate code significantly.

**How to Implement:**

1.  **Extend `EloquentQuery`**: Your service implementation class must `extend` the `EloquentQuery`
    base class and implement your service contract.
2.  **Set Model in Constructor**: In the constructor, you **must** call `$this->setModel()` with an
    instance of the Eloquent model your service will manage. This initializes the base class.

_Example Implementation for `modules/User/src/Services/UserService.php`:_

```php
<?php

namespace Modules\User\Services;

use Modules\Shared\Services\EloquentQuery;
use Modules\User\Contracts\Services\UserService as UserServiceContract;
use Modules\User\Models\User;

class UserService extends EloquentQuery implements UserServiceContract
{
    /**
     * UserService constructor.
     */
    public function __construct()
    {
        $this->setModel(new User());
    }

    // Add any specific business logic methods here.
    // You can also override base methods like create() or update()
    // to add authorization checks, dispatch events, etc.
}
```

### Step 4: Create the UI Component

For user interaction, create a Livewire component in `src/Livewire`. This component will interact
with your **Service interface**.

```bash
php artisan module:make-livewire CreateUser User --view
```

### Step 5: Create the Route

Finally, expose your Livewire component via a route in your module's `routes/web.php`.

```php
// Modules/User/routes/web.php
use Illuminate\Support\Facades\Route;
use Modules\User\Livewire\CreateUser;

Route::get('/register', CreateUser::class)->name('user.register');
```

---

## 4. Advanced Workflow (Optional: Repositories & Entities)

This workflow is reserved for complex scenarios where strict decoupling from Eloquent is required.

1.  **Create Entity:** `php artisan module:make-entity UserEntity User`
2.  **Create Repository Interface:**
    `php artisan module:make-interface Repositories/UserRepository User`
3.  **Create Repository Implementation:** `php artisan module:make-repository UserRepository User`
4.  **Inject Repository into Service:** The Service will now interact with the Repository using
    Entities, instead of the Model directly.

## 5. Data Flow Summary

1.  **Route** (`web.php`) maps to a **Livewire Component**.
2.  The **Livewire Component** authorizes the user using **Policies** and calls a method on a
    **Service interface**.
3.  The **Service** executes business logic and interacts with the **Eloquent Model**.
4.  The **Model** persists/retrieves data from the database.
5.  Data returns up the chain to the **Livewire Component** for rendering.

## 6. Testing Your Module

Testing is an integral part of the development workflow. All tests **must** be written using Pest.
For a comprehensive guide on the project's testing philosophy, framework usage, directory structure,
and detailed examples, refer to the **[Testing Guide](testing.md)**.

Basic commands for module testing include:

- **Location**: Tests for a specific module reside in `modules/{ModuleName}/tests/`.
- **Creation**:
    ```bash
    php artisan module:make-test <TestName> <ModuleName> [--feature]
    ```
- **Execution**:
    ```bash
    php artisan test --filter=<ModuleName>
    ```

---

**Navigation**

[← Previous: Permission UI Components](permission-ui-components.md) |
[Next: Release Guidelines →](release-guidelines.md)
