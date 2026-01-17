# ManagesModuleProvider Trait

The `ManagesModuleProvider` trait is a core utility located in the `Shared` module. it is designed
to standardize and simplify the lifecycle of Service Providers within Internara's modular
architecture.

## Purpose

- **Automation:** Automatically handles the registration of configurations, views, translations, and
  migrations.
- **Boilerplate Reduction:** Provides clean methods like `registerModule()` and `bootModule()` to
  encapsulate common tasks.
- **Standardization:** Ensures every module follows the same bootstrapping pattern, improving
  maintainability.
- **Flexibility:** Allows modules to easily define service bindings and UI injection slots.

---

## Core Methods

### `registerModule()`

Should be called in the provider's `register()` method. It automatically executes:

- **`registerConfig()`**: Merges module-specific configuration files into the global config.
- **`registerBindings()`**: Registers service bindings defined in the `bindings()` method.

### `bootModule()`

Should be called in the provider's `boot()` method. It automatically executes:

- **`registerTranslations()`**: Loads translations from the module's `lang/` directory.
- **`registerViews()`**: Loads Blade views and registers component namespaces.
- **`registerMigrations()`**: Loads database migrations.
- **`registerCommands()`**: Registers custom Artisan commands.
- **`registerViewSlots()`**: Configures UI injection slots.

---

## Technical Features

### 1. Automatic Migration Loading

Modules no longer need to manually call `loadMigrationsFrom()`. The trait automatically looks for
migrations in the module's `database/migrations` directory.

### 2. Intelligent Service Binding

The `bindings()` method allows you to define interface-to-implementation maps. The trait is smart
enough to:

- Bind as a **Singleton** if the implementation is an instantiable class string.
- Bind as a standard **Transient** if it's a Closure or non-instantiable class.

### 3. Recursive Config Merging

Configurations within the module's `config/` directory are merged recursively, supporting nested
directories and preventing key collisions.

---

## Implementation Example

```php
namespace Modules\User\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Providers\Concerns\ManagesModuleProvider;

class UserServiceProvider extends ServiceProvider
{
    use ManagesModuleProvider;

    protected string $name = 'User';
    protected string $nameLower = 'user';

    public function register(): void
    {
        $this->registerModule();

        // Register other module-specific providers
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->bootModule();
    }

    protected function bindings(): array
    {
        return [
            \Modules\User\Services\Contracts\UserService::class =>
                \Modules\User\Services\UserService::class,
        ];
    }
}
```

---

## Best Practices

1.  **Always define `$name` and `$nameLower`:** These properties are required by the trait to locate
    resources.
2.  **Use `registerModule()` and `bootModule()`:** Prefer these wrapper methods over calling
    individual registration methods unless you need highly granular control.
3.  **Keep Providers Thin:** Use the `bindings()` method for DI registration instead of manual
    `app()->bind()` calls inside the `register` method.

---

**Navigation**

[← Previous: Shared Model Traits](shared-traits.md) |
[Next: Service Binding & Auto-Discovery →](service-binding-auto-discovery.md)
