# Artisan Commands Reference: Modular Tooling

Internara extends the Laravel CLI with custom Artisan commands designed to streamline modular
development. These commands respect our **Namespace Conventions** and help you maintain
architectural consistency with minimal effort.

---

## 1. Modular Generators

Use these commands to scaffold new components within a specific module.

### 1.1 Service Generation

Creates a new Service class and, optionally, its Contract.

```bash
php artisan module:make-service MyService ModuleName
```

- **Result**: `modules/ModuleName/src/Services/MyService.php`
- **Options**: Use `--contract` to also generate the interface in `Contracts/`.

### 1.2 Model & Migration

Creates a new Eloquent model with UUID support and a corresponding migration.

```bash
php artisan module:make-model MyModel ModuleName --migration
```

- **Result**: `modules/ModuleName/src/Models/MyModel.php`

### 1.3 Livewire Component

Generates a Livewire (or Volt) component and its Blade view.

```bash
php artisan module:make-livewire MyComponent ModuleName --view
```

- **Result**: `modules/ModuleName/src/Livewire/MyComponent.php`

---

## 2. System Identity & Status

Utility commands for inspecting the application state.

### 2.1 Application Info

Displays current version, series code, and tech stack versions.

```bash
php artisan app:info
```

### 2.2 Refresh Bindings

Forces a re-scan of Services and Contracts to refresh the Auto-Discovery cache.

```bash
php artisan app:refresh-bindings
```

---

## 3. Database & Security

### 3.1 Permission Sync

Scans all modular seeders and synchronizes the roles/permissions table.

```bash
php artisan permission:sync
```

---

_Always use the modular generators
(`module:make-_`) instead of standard Laravel generators  (`make:_`) to ensure that your files are
placed in the correct directories and use the proper namespaces._
