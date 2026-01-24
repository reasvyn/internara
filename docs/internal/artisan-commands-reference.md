# Artisan Commands Reference: Modular Tooling

Internara extends the Laravel CLI with custom Artisan commands designed to streamline modular
development and ensure spec compliance.

> **Governance Mandate:** All generated code must adhere to the standards defined in the
> **[Internara Specs](../internal/internara-specs.md)** (e.g., UUID identity, Mobile-First
> structure).

---

## 1. Modular Generators

These commands respect our **Namespace Conventions** (omitting the `src` segment).

### 1.1 Service Generation

```bash
php artisan module:make-service MyService ModuleName
```

- **Result**: `modules/ModuleName/src/Services/MyService.php`
- **Convention**: Generates a **Contract-First** service.

### 1.2 Model & Migration

```bash
php artisan module:make-model MyModel ModuleName --migration
```

- **Standards**: Enforces **UUID** primary keys and **Snake_case** table naming.
- **Constraint**: Migrations generated will follow the "No physical cross-module FKs" rule.

### 1.3 Livewire Component

```bash
php artisan module:make-livewire MyComponent ModuleName
```

- **UI Standard**: Scaffolds components with **Mobile-First** responsive wrappers.

---

## 2. System Identity & Status

### 2.1 Application Info

```bash
php artisan app:info
```

- **Goal:** Verify version, **Series Code**, and environment health.

### 2.2 Refresh Bindings

```bash
php artisan app:refresh-bindings
```

- **Goal:** Sync the **Auto-Discovery** cache for Services and Contracts.

---

## 3. Database & Security

### 3.1 Permission Sync

```bash
php artisan permission:sync
```

- **Goal:** Synchronize the User Roles defined in
  **[Internara Specs](../internal/internara-specs.md)** across the database.

---

_Always use modular generators to ensure architectural integrity._
