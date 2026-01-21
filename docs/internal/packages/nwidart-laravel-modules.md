# Laravel Modules: The Modular Engine

Internara utilizes the `nwidart/laravel-modules` package as the foundational engine for our
**Modular Monolith** architecture. It provides the directory structure, autoloader logic, and CLI
tools necessary to manage isolated business domains effectively.

---

## 1. Structural Customizations

We have modified the default behavior of this package to align with our engineering standards.

### 1.1 Source Directory (`src/`)

Unlike the default `app/` folder, our logic resides in a clean `src/` directory.

- **Config**: `'paths.app_folder' => 'src/'`
- **Impact**: All models, services, and components are located at `modules/{Module}/src/`.

### 1.2 Namespace Convention (Omit `src`)

We have configured the autoloader to ignore the `src` segment in the namespace.

- **Path**: `modules/User/src/Models/User.php`
- **Namespace**: `Modules\User\Models\User` (✅ Correct)

---

## 2. Modular Scaffolding

Our custom generators pre-configure every module with Internara's standard layers:

- `Contracts/`: For interface definitions.
- `Services/`: For business logic orchestration.
- `Models/`: For persistence logic.
- `Livewire/`: For UI components.

### Essential Commands

Always use the `module:` prefix to ensure paths and namespaces are generated correctly.

```bash
# Create a new module
php artisan module:make MyModule

# Add resources to a module
php artisan module:make-service MyService MyModule
php artisan module:make-model MyModel MyModule --migration
```

---

## 3. Strict Isolation Enforcement

The package is configured to prevent accidental leakage between modules.

- **Zero-Foreign-Key**: No physical database constraints are allowed across modules.
- **Service Dependency**: Cross-module calls MUST be done via **Contracts** injected into Service
  classes.

---

_Refer to the **[Architecture Guide](../architecture-guide.md)** for a deep-dive into the
communication rules enforced by this modular engine._
