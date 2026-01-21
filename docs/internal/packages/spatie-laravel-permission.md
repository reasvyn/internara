# Spatie Permissions: Modular RBAC

Internara uses the `spatie/laravel-permission` package to power our **Role-Based Access Control**
system. We have heavily customized this integration to support modularity, portability, and UUID
identity.

---

## 1. Modular Customizations

To ensure the system works within our architecture, we've implemented three major enhancements:

### 1.1 Support for UUIDs

All permission-related models (Role, Permission) are configured to use **UUIDs** as primary keys,
matching our application-wide identity standard.

### 1.2 Module Ownership

We added a `module` column to the `roles` and `permissions` tables. This allows us to:

- Identify which module "owns" a permission.
- Automatically clean up permissions when a module is uninstalled.
- Group permissions in the UI for better administrative oversight.

### 1.3 Runtime Configuration Injection

To keep the `Permission` module portable, it overrides Spatie's global config at runtime. This means
you don't need to touch `config/permission.php` manually.

```php
protected function overrideSpatieConfig(): void
{
    config([
        'permission.models.role' => \Modules\Permission\Models\Role::class,
        'permission.models.permission' => \Modules\Permission\Models\Permission::class,
    ]);
}
```

---

## 2. Integration Best Practices

1.  **Use Policies**: Never check permissions directly in a view. Wrap them in a **Policy** to
    handle ownership logic.
2.  **Sync via CLI**: After adding permissions to a module seeder, run
    `php artisan permission:sync`.
3.  **Role Hierarchy**: Base roles are defined in the `Core` module. Avoid creating new roles within
    domain modules unless absolutely necessary.

---

_Refer to the **[Role & Permission Guide](../role-permission-management.md)** for a practical guide
on how to implement authorization in your features._
