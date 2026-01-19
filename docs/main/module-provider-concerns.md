# ManagesModuleProvider: Standardizing Bootstraps

The `ManagesModuleProvider` **Concern** (Trait) is the architectural backbone of Internara's modular
bootstrapping. Located in the `Shared` module, it automates the registration of configurations,
views, translations, and migrations, ensuring every module follows a predictable lifecycle.

---

## 1. The Strategy: Automation over Boilerplate

In a standard Laravel app, you manually call `loadMigrationsFrom()`, `mergeConfigFrom()`, etc. In
Internara, this concern handles the "wiring" automatically based on convention.

### 1.1 `registerModule()`

Triggered during the Laravel `register` phase.

- **Config**: Recursively merges all files in `modules/{Module}/config/*.php`.
- **Contracts**: Automatically maps **Contracts** to implementations defined in the `bindings()`
  method.

### 1.2 `bootModule()`

Triggered during the Laravel `boot` phase.

- **Translations**: Registers the module's lang namespace (e.g., `__('user::messages')`).
- **Views**: Registers the Blade namespace (e.g., `<x-user::profile />`).
- **Migrations**: Automatically loads all files from `database/migrations/`.
- **Commands**: Discovers and registers custom Artisan commands.
- **UI Slots**: Hooks into the `SlotManager` to inject modular UI elements into layouts.

---

## 2. Advanced Binding Logic

The `bindings()` method is the preferred way to handle Dependency Injection (DI) within a module.

```php
protected function bindings(): array
{
    return [
        // Map Contract to Concrete implementation
        \Modules\User\Services\Contracts\UserServiceInterface::class =>
            \Modules\User\Services\UserService::class,
    ];
}
```

**Intelligence Features**:

- **Singleton Detection**: If you provide a class string, the concern binds it as a singleton.
- **Closure Support**: If you provide a Closure, it handles custom instantiation logic.

---

## 3. Configuration Discovery

Internara supports **Recursive Config Merging**.

- **Structure**: You can nest configuration files (e.g., `config/api/settings.php`).
- **Merging**: These are merged into the global config tree, making them accessible via
  `config('user.api.settings')`.

---

## 4. Best Practices for Provider Concerns

1.  **Strict Identification**: Always define `protected string $name = 'ModuleName'`. This is the
    "Anchor" used to locate all resources.
2.  **Keep it Declarative**: Avoid complex logic in `register()` or `boot()`. Use the provided hook
    methods (`bindings()`, `commands()`) to keep the provider clean.
3.  **Cross-Module Gating**: Use the `bootModule()` phase to register events or listeners that react
    to other modules.

---

_By leveraging `ManagesModuleProvider`, we ensure that adding a new module to Internara is as simple
as creating the directory. The framework handles the rest._
