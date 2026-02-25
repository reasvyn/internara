# Verification Troubleshooting: Triage & Resolution

This document provides the authoritative technical record for diagnosing and resolving common 
failures within the Internara verification lifecycle.

---

## 1. Common Architectural Failures

### 1.1 Modular Leakage (Pest Arch)
- **Error**: `Expecting 'Modules\A' to not use 'Modules\B\Models'.`
- **Cause**: Violation of the **Strict Modular Isolation** invariant.
- **Resolution**: Refactor the code to use a **Service Contract** (Interface) from Module B 
  instead of accessing its models directly.

### 1.2 Identity Mismatch (UUID)
- **Error**: `ModelNotFoundException` or `404` when using IDs.
- **Cause**: Attempting to use integer-based auto-increment IDs instead of **UUID v4**.
- **Resolution**: Ensure the model uses the `HasUuid` concern and that the seeder/factory 
  generates valid UUIDs.

---

## 2. Infrastructure Failures

### 2.1 Database Lock (SQLite)
- **Error**: `Database is locked` or `General error: 5`.
- **Cause**: Concurrent write attempts or orphaned database connections during parallel testing.
- **Resolution**: Use `php artisan app:test` which runs modules sequentially, or increase the 
  `busy_timeout` in `config/database.php`.

### 2.2 Livewire Hydration Errors
- **Error**: `Component hydration failed` or `Unable to resolve dependency`.
- **Cause**: Constructor injection used in a Livewire component instead of method-level 
  injection (`mount` or `boot`).
- **Resolution**: Move service injection to the `boot()` or `mount()` method.

---

## 3. Triage Checklist

Before escalating a verification failure:
1.  **Clear Caches**: `php artisan optimize:clear`.
2.  **Lint Check**: `composer lint` (syntax errors often cause silent failures).
3.  **Dependency Sync**: `composer update` and `npm install`.
4.  **Forensic Audit**: Check `storage/logs/laravel.log` for the detailed stack trace.

---

_Deterministic verification is the foundation of Internara's stability. Resolve all defects 
at the source._
