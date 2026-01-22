# ManagesModuleProvider: Standardizing Bootstraps

The `ManagesModuleProvider` **Concern** (Trait) is the architectural backbone of Internara's modular
bootstrapping. Located in the `Shared` module, it automates the registration of configurations,
views, translations, and migrations.

> **Spec Alignment:** This automation enforces the modular isolation and portability mandated by the
> **[Internara Specs](../internal/internara-specs.md)**.

---

## 1. The Strategy: Automation over Boilerplate

### 1.1 `registerModule()`
- **Config**: Recursively merges all files in `modules/{Module}/config/*.php`.
- **Bindings**: Automatically maps **Contracts** to implementations based on the **Auto-Discovery** rules.

### 1.2 `bootModule()`
- **Translations (i11n)**: Registers the module namespace for **Multi-Language** support (e.g., `__('user::exceptions')`).
- **Views (Mobile-First)**: Registers the Blade namespace for responsive components.
- **Migrations**: Automatically loads modular migrations (UUID-based, No physical FKs).
- **Settings**: Hooks into the `Setting` module to ensure `setting()` helper availability.

---

## 2. Advanced Binding Logic

The `bindings()` method is used for manual Dependency Injection (DI) overrides.

```php
protected function bindings(): array
{
    return [
        // Map Contract to Concrete implementation (Omit 'Interface' suffix)
        \Modules\User\Services\Contracts\UserService::class =>
            \Modules\User\Services\UserService::class,
    ];
}
```

---

## 3. Best Practices

1.  **Strict Identification**: Define `protected string $name = 'ModuleName'`.
2.  **English-First**: All metadata and PHPDoc within the provider must be in English.
3.  **No Hard-Coding**: Provider logic must not hard-code environment values; use `config()` or `setting()`.

---

_By leveraging `ManagesModuleProvider`, we ensure that adding a new module is consistent with the Internara architecture._