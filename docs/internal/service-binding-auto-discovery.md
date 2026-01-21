# Service Binding & Auto-Discovery

To facilitate easy cross-module communication and minimize Service Provider clutter, Internara
utilizes an **Auto-Discovery** system for its service layer. This system automatically maps
**Contracts** (Interfaces) to their concrete implementations based on directory structure.

---

## 1. How It Works

The system scans the following directories for PHP classes:

- `app/Services`
- `modules/{ModuleName}/src/Services`

### 1.1 The Directory Pattern

The auto-discovery engine expects a specific layout:

1.  **Contract**: Stored in `Services/Contracts/` (e.g., `UserServiceInterface.php`).
2.  **Implementation**: Stored directly in `Services/` (e.g., `UserService.php`).

If a class in `Services/` implements an interface found in its own `Contracts/` subdirectory, the
binding is registered automatically in the Laravel Service Container.

---

## 2. Configuration & Overrides

While auto-discovery handles 90% of cases, manual control is sometimes necessary.

### 2.1 The `config/bindings.php` File

Use this file to manually register complex bindings or override auto-discovered ones.

- **Example**: Mapping a Contract to different implementations based on environment.

### 2.2 Caching

For production performance, the discovered bindings are cached.

- **Refresh Cache**: `php artisan app:refresh-bindings` (Custom command).

---

## 3. Benefits for Developers

- **Zero-Config Injection**: Simply create your Contract and Service, and you can immediately
  type-hint the Contract in your constructors.
- **Decoupling**: Encourages the use of Contracts, making the code easier to test and refactor.
- **Clean Providers**: Module Service Providers remain focused on booting components rather than
  endless binding lists.

---

_Always follow the directory naming standard to ensure your services are discovered. If your service
isn't binding, verify that the Contract is in the `Contracts/` folder and the implementation
properly `implements` it._
