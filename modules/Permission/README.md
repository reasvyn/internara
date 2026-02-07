# Permission Module

The `Permission` module provides the centralized Role-Based Access Control (RBAC) engine for the
Internara ecosystem. It defines granular access levels for Instructors, Students, and Supervisors,
ensuring that every system action is authorized according to the stakeholder's role.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/developers/specs.md)**. All implementation must
> adhere to the **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## 1. Architectural Role

As a **Foundational Public Module**, the `Permission` module provides the security infrastructure
utilized by all domain modules. It works in coordination with the `Auth` and `User` modules to
enforce the **Policy Patterns** defined in the project conventions.

---

## 2. Core Components

### 2.1 Service Layer

- **`RoleService`**: Orchestrates the management of system roles.
    - _Features_: Role creation, module-based filtering, and permission synchronization.
    - _Contract_: `Modules\Permission\Services\Contracts\RoleService`.
- **`PermissionService`**: Manages granular system permissions.
- **`PermissionManager`**: A specialized facade/service for complex permission evaluation across
  modular boundaries.

### 2.2 Persistence Layer

- **`Role` & `Permission` Models**: Extends Spatie's base models to support **UUID v4** identities
  and module-specific scoping.

---

## 3. Engineering Standards

- **Identity Invariant**: All roles and permissions are identified by a unique UUID.
- **Strict Isolation**: Communication with this module is handled via Service Contracts, ensuring
  zero-coupling between domain logic and RBAC persistence.
- **Explicit Deny**: Authorization logic follows the **Deny by Default** principle as mandated by
  Section 12 of the project conventions.

---

## 4. Verification & Validation (V&V)

Authorization integrity is verified through **Pest v4**:

- **Unit Tests**: Verifies role management logic and permission synchronization.
- **Integration**: Ensures that RBAC policies correctly protect domain-specific resources.
- **Command**: `php artisan test modules/Permission`

---

_The Permission module ensures that Internara remains a secure, traceable, and authoritative
platform for internship management._
