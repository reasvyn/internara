# Service Binding & Auto-Discovery

To facilitate easy cross-module communication and minimize Service Provider clutter, Internara
utilizes an **Auto-Discovery** system for its service layer. This system automatically maps
**Contracts** (Interfaces) to their concrete implementations based on directory structure.

> **Spec Alignment:** This mechanism ensures the modular isolation and portability required by the
> **[Internara Specs](../internal/internara-specs.md)**.

---

## 1. How It Works

The system scans the following directories for PHP classes:

- `app/Services`
- `modules/{ModuleName}/src/Services`

### 1.1 The Directory Pattern

The auto-discovery engine expects a specific layout:

1.  **Contract**: Stored in `Services/Contracts/` (e.g., `UserService.php`).
    - *Note:* Internara convention omits the `Interface` suffix for contracts.
2.  **Implementation**: Stored directly in `Services/` (e.g., `UserService.php`).
    - *Note:* Implementation and Contract share the same name but live in different namespaces.

If a class in `Services/` implements an interface found in its own `Contracts/` subdirectory, the
binding is registered automatically in the Laravel Service Container.

---

## 2. Cross-Module Communication

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

---

## 3. Configuration & Overrides

### 3.1 The `config/bindings.php` File

Use this file to manually register complex bindings or override auto-discovered ones.

### 3.2 Caching

For production performance, the discovered bindings are cached.
- **Refresh Cache**: `php artisan app:refresh-bindings`.

---

## 4. Benefits for Developers

- **Zero-Config Injection**: Simply create your Contract and Service.
- **Decoupling**: Encourages the use of Contracts, making the code easier to test and refactor.
- **Portability:** Modules remain independent as they rely on abstract definitions.

---

_Always follow the directory naming standard to ensure your services are discovered. If your service
isn't binding, verify that the Contract is in the `Contracts/` folder and the implementation
properly `implements` it._