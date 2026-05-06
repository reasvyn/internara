# Database

## Standards

### UUID Primary Keys

All models use UUIDs. Apply the `HasUuid` trait to enable automatic UUID generation:

```php
// Migration
$table->uuid('id')->primary();

// Model
use App\Traits\HasUuid;

class User extends Model
{
    use HasUuid;
}
```

The `HasUuid` trait lives outside `app/Domain/` — it is a technical utility, not a business rule.

### Mass Assignment

Use PHP 8 `#[Fillable]` attributes and `#[Hidden]` attributes. Avoid legacy `$fillable` arrays.

### Relationships

Always use constrained UUID foreign keys:

```php
$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
```

High-growth tables (audit, attendance, logbook) have compound indexes on common filter columns.

## Model Organization

Models live in `app/Domain/{Domain}/Models/`. Each model belongs to the domain it represents.

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
