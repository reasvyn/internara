# Development Conventions

This document outlines the **mandatory** coding and development conventions for the Internara project. These rules ensure consistency, maintainability, and high code quality.

For the high-level philosophy behind these rules, refer to the **[Conceptual Best Practices](conceptual-best-practices.md)** guide.

---

**Table of Contents**

- [1. General Conventions](#1-general-conventions)
- [2. PHP Conventions](#2-php-conventions)
- [3. Laravel Conventions](#3-laravel-conventions)
- [4. Modular Architecture (Laravel Modules)](#4-modular-architecture-laravel-modules)
- [5. Role & Permission Conventions](#5-role--permission-conventions)
- [6. Exception Handling Conventions](#6-exception-handling-conventions)
- [7. UI, View & Component Conventions](#7-ui-view--component-conventions)
- [8. Testing (Pest) Conventions](#8-testing-pest-conventions)

---

## 1. General Conventions

- **Language:** English only for all code, comments, and commits.
- **Naming:**
    - **Variables/Methods:** `camelCase` (e.g., `isVerified`).
    - **Classes/Modules:** `PascalCase` (e.g., `UserService`).
    - **Setters:** Use `set{Property}` (e.g., `setAvatar`). Avoid `change` or `update` prefix for simple state mutations.
- **Directory Structure:** **Do not create new root directories.** Follow the `modules/{Module}/src/` structure strictly.

## 2. PHP Conventions

- **Strict Types:** `declare(strict_types=1);` is recommended for new files.
- **Constructor Promotion:** Use PHP 8 constructor property promotion.
- **Return Types:** **Mandatory** for all methods. Use `void` if nothing is returned.
- **Enums:** Keys should be `TitleCase`.
- **Interfaces:** Name by capability, not implementation.
    - **Bad:** `UserRepositoryInterface`, `IUserRepository`.
    - **Good:** `UserRepository`.

## 3. Laravel Conventions

- **Eloquent:**
    - Use `Model::query()` for starting chains.
    - Use `casts()` method (Laravel 11+) over `$casts` property.
    - **No Logic in Models:** Keep models lightweight. Move business logic to Services.
- **Config:** Use `config('key')`. **Never** use `env()` outside of config files.
- **Controllers:** Keep them "skinny". Validate input (FormRequest) $\rightarrow$ Call Service $\rightarrow$ Return Response.

### 3.1. Account Status (Standardized)

- **`active`**: Operational.
- **`inactive`**: Administratively disabled.
- **`pending`**: Awaiting verification.

## 4. Modular Architecture (Laravel Modules)

**Golden Rule:** A module must be portable. It should not "know" about the existence of other modules' concrete classes.

### 4.1. Namespace Rules
- Omit `src` from the namespace.
- **File:** `modules/User/src/Services/UserService.php`
- **Namespace:** `Modules\User\Services`

### 4.2. Database Isolation
- **No Cross-Module Foreign Keys:** Never use `$table->foreign()->constrained()` between modules.
- **Constraint:** Use `uuid('other_module_id')->index()` instead.
- **Integrity:** Enforce referential integrity in the **Service Layer**.

### 4.3. Service Layer
- **Pattern:** Interface-First.
- **Base Class:** Extend `Modules\Shared\Services\EloquentQuery` for CRUD operations.
- **Injection:** Always type-hint the **Interface**, not the concrete class.

## 5. Role & Permission Conventions

- **Format:** `module.action` (e.g., `user.create`).
- **Authorization:** Use Policies (`$user->can('user.create')`). Never check Roles directly in code (`hasRole('admin')` is forbidden in business logic).

## 6. Exception Handling Conventions

- **Custom Exceptions:** Extend `Modules\Exception\AppException`.
- **Translation:** Use `{module}::exceptions.{key}` for messages.

## 7. UI, View & Component Conventions

### 7.1. The UI Module
- **Source of Truth:** All generic components (`Button`, `Card`, `Input`) reside in `Modules\UI`.
- **Usage:** `<x-ui::button variant="primary" />`.
- **Constraint:** Do not create custom buttons/inputs in Feature modules. Use the `UI` library.

### 7.2. Cross-Module Injection
- **Slots:** Use `@slotRender('sidebar.items')` to allow other modules to inject content.
- **Registry:** Register slots in the `Boot` method of your ServiceProvider.

## 8. Testing (Pest) Conventions

- **Framework:** **Pest** is mandatory.
- **Location:** `modules/{Module}/tests/`.
- **Coverage:**
    - **Unit:** Test Services and isolated logic.
    - **Feature:** Test Livewire components and End-to-End flows.

---

**Navigation**

[← Previous: Conceptual Best Practices](conceptual-best-practices.md) |
[Next: Software Development Life Cycle →](software-lifecycle.md)