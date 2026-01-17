# Shared Model Traits

Internara provides a set of reusable Eloquent traits in the `Shared` module to standardize common
behaviors across domain models.

## Available Traits

### 1. `Modules\Shared\Models\Concerns\HasUuid`

Automatically handles UUID generation for models that require non-integer primary keys.

#### Features:

- Sets `incrementing` to `false` and `keyType` to `string` automatically.
- Generates a version 4 UUID on the `creating` event if the ID is empty.
- Supports toggling via the `usesUuid()` method (defaults to `true`).

#### Usage:

```php
use Modules\Shared\Models\Concerns\HasUuid;

class MyModel extends Model
{
    use HasUuid;
}
```

---

### 2. `Modules\Shared\Models\Concerns\HasStatus`

Wraps `spatie/laravel-model-status` to provide a standardized way to track entity lifecycles.

#### Features:

- **Status History:** Tracks every status change with optional reasons.
- **Helper Methods:**
    - `latestStatus()`: Returns the current status object.
    - `getStatusLabel()`: Returns a translated label for the current status.
    - `getStatusColor()`: Returns a CSS/Theme color key (e.g., `success`, `warning`).
- **Customizable:** Models can override `getStatusColorMap()` and `getStatusTranslationPrefix()`.

#### Usage:

```php
use Modules\Shared\Models\Concerns\HasStatus;

class Internship extends Model
{
    use HasStatus;

    protected function getStatusColorMap(): array
    {
        return [
            'approved' => 'success',
            'rejected' => 'error',
            'review' => 'info',
        ];
    }
}
```

---

## Best Practices

1.  **UUID for Security:** Use `HasUuid` for any model whose ID is exposed in URLs to prevent
    enumeration attacks.
2.  **Status over Booleans:** Prefer using `HasStatus` instead of multiple boolean flags (e.g.,
    `is_active`, `is_verified`) for better auditability and flexibility.
3.  **Standardize Labels:** Always define status translations in
    `modules/Shared/lang/{locale}/status.php` or within the domain module's language files using the
    prefix.

---

**Navigation**

[← Previous: EloquentQuery Base Service](eloquent-query-service.md) |
[Next: ManagesModuleProvider Trait →](module-provider-concerns.md)
