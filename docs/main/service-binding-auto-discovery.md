# Service Binding and Auto-Discovery

Internara provides a robust and flexible Service Binding mechanism powered by the
`BindServiceProvider`. This system automates the registration of dependency injection bindings while
offering complete control through configuration.

## Features

- **Auto-Discovery:** Automatically binds interfaces to their implementation based on naming
  conventions.
- **High Performance:** Discovery results are cached (configurable TTL) to prevent I/O overhead in
  production.
- **Configurable Patterns:** Customize how implementations are guessed (e.g., `UserRepository`
  (Interface) -> `UserRepository` or `Repositories\EloquentUserRepository`).
- **Security:** Block sensitive namespaces from being auto-bound.
- **Contextual Binding:** Define dependencies based on the consuming class.
- **Singleton Support:** Toggle between singleton or transient bindings globally.

## Configuration

All configurations are managed in `config/bindings.php`.

### 1. Enabling/Disabling Auto-Bind

```php
'autobind' => true,
```

### 2. Caching

In production, discovery results are cached. In `local` environment, cache is disabled (TTL = 0) for
developer experience.

```php
'cache_ttl' => 1440, // Minutes (24 hours)
```

### 3. Singleton Mode

If your services are stateless, you can enable singleton mode to save memory.

```php
'bind_as_singleton' => false,
```

### 4. Custom Patterns

Define how the system should find the concrete class for a given interface. Placeholders:

- `{{root}}`: The root namespace (e.g., `Modules\User` or `App`).
- `{{short}}`: The interface name without `Interface` or `Contract` suffix.

```php
'patterns' => [
    '{{root}}\Services\{{short}}Service',
    '{{root}}\Services\{{short}}',
    '{{root}}\Repositories\Eloquent\{{short}}Repository',
],
```

### 5. Ignored Namespaces

Prevent auto-binding for specific namespaces (e.g., internal secrets).

```php
'ignored_namespaces' => [
    'Modules\Core',
    'App\Services\Secret',
],
```

## Manual & Contextual Binding

### Manual Binding

For cross-module bindings or exceptions to the rule, use the `default` array in the config.

```php
'default' => [
    'Modules\Auth\Contracts\UserService' => 'Modules\User\Services\UserService',
],
```

### Contextual Binding

Define what implementation to use based on the class that needs it.

```php
'contextual' => [
    [
        'when'  => 'App\Http\Controllers\PhotoController',
        'needs' => 'App\Contracts\Filesystem',
        'give'  => 'App\Services\LocalFilesystem',
    ],
],
```

Alternatively, you can define complex logic in the `BindServiceProvider::contextualBindings()`
method.

## How Auto-Discovery Works

1.  The provider scans `App/Contracts` and `Modules/*/src/Contracts`.
2.  It parses PHP files to find Interface definitions.
3.  It determines the **Root Namespace** (e.g., `Modules\User`).
4.  It generates candidate class names using the configured `patterns`.
5.  If a candidate class exists and implements the interface, it is registered.

> **Note:** Auto-discovery works best when the Interface and the Implementation share the same Root
> Namespace. For cross-module dependencies, use Manual Binding.

---

## Interaction with `ManagesModuleProvider` Trait

While the `BindServiceProvider` excels at auto-discovering bindings based on conventions, modules
often require explicit control over their service bindings (e.g., for custom implementations,
singletons, or more complex resolution logic).

The `Modules\Shared\Providers\Concerns\ManagesModuleProvider` trait (used by all standard module
service providers) offers a structured way to explicitly declare these bindings within a module's
`bindings()` method.

- **Auto-Discovery (by `BindServiceProvider`)**: Ideal for simple `Interface -> Implementation`
  bindings that follow strict naming conventions.
- **Explicit Declaration (by `ManagesModuleProvider` trait)**: Use this for:
    - Custom bindings that don't fit auto-discovery patterns.
    - Ensuring specific singletons.
    - Resolving ambiguities.
    - Decoupling intra-module dependencies where auto-discovery might be overridden or is less
      clear.

When a module uses the `ManagesModuleProvider` trait and explicitly binds an interface in its
`bindings()` method, that explicit binding will take precedence over any potential auto-discovery
attempts by the `BindServiceProvider` for that specific interface.

---

**Navigation**

[← Previous: ManagesModuleProvider Trait](module-provider-concerns.md) |
[Next: Conceptual Best Practices →](conceptual-best-practices.md)
