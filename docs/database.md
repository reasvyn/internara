# Database

## Standards

### UUID Primary Keys

All business models use UUIDs. Most extend `BaseModel` which provides `HasUuids`, non-incrementing keys, and string key type. `User` extends `Authenticatable` and applies `HasUuids` directly.

```php
// Migration
$table->uuid('id')->primary();

// BaseModel handles the rest
abstract class BaseModel extends Model
{
    use HasUuids;

    public function getIncrementing(): bool { return false; }
    public function getKeyType(): string { return 'string'; }
}
```

### Mass Assignment

Use PHP 8 `#[Fillable]` and `#[Hidden]` attributes on all models. Some older models still use `$fillable` (e.g. `Setup`).

### Foreign Keys

Always use constrained UUID foreign keys:

```php
$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
```

## Spatie Integrations

| Package | Purpose | Notes |
|---|---|---|
| `laravel-permission` ^6.24 | RBAC (with team support) | |
| `laravel-medialibrary` ^11.17 | File attachments | Used by `School` and `Submission` |
| `laravel-activitylog` ^4.10 | Change tracking | Custom `ActivityLog` model with scopes |
| `laravel-model-status` ^1.18 | Status tracking | Used on `User` model |
| `laravel-model-states` ^2.14 | State machines | **Installed but not used** — Entities handle state instead |
| `laravel-honeypot` ^4.6 | Spam protection | |

## Known Issue: ActivityLog Model

`App\Models\ActivityLog` extends Spatie's `Activity` with useful scopes (`forUser`, `forSubject`, `ofAction`, `recent`, `forModule`, `groupedByDay`), but `config/activitylog.php` still points to the default `Activity::class`. This means `activity()` and `SmartLogger` create `Activity` instances — the custom scopes are not available through the standard pipeline. See [Known Issues](known-issues.md).

## Testing

Tests use SQLite `:memory:` via `LazilyRefreshDatabase`. Every model has a factory in `database/factories/`.