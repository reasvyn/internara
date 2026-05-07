# Database

## Standards

### UUID Primary Keys

All models use UUIDs. Most extend `BaseModel` which includes Laravel's built-in `HasUuids` concern:

```php
// Migration
$table->uuid('id')->primary();

// Model (via BaseModel)
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasUuids;

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
```

Models that extend `Authenticatable` (e.g., `User`) apply `HasUuids` directly.

### Mass Assignment

Use PHP 8 `#[Fillable]` and `#[Hidden]` attributes on all models except legacy exceptions:

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'email', ...])]
class User extends Authenticatable
{
    // ...
}
```

Some older models (e.g., `Setup`) still use the traditional `$fillable` property.

### Relationships

Always use constrained UUID foreign keys:

```php
$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
```

High-growth tables (activity_log, attendance, logbook) have compound indexes on common filter columns.

## Model Organization

All models live flat in `app/Models/`. There is no sub-namespacing — every model uses `namespace App\Models;`.

## Spatie Integrations

| Package | Purpose | Tables |
|---|---|---|
| `laravel-permission` | RBAC | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` |
| `laravel-medialibrary` | File attachments | `media` |
| `laravel-activitylog` | Model change tracking | `activity_log` |
| `laravel-model-status` | Stateful entities | `statuses` |

## Monitoring

Laravel Pulse stores metrics in `pulse_*` tables. Dashboard available at `/pulse`.

## Testing

Tests use SQLite `:memory:` via the `LazilyRefreshDatabase` trait. Every model has a factory in `database/factories/`. Seeders in `database/seeders/` manage initial system state.
