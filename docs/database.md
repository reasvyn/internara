# Database

## Connection

The default database is SQLite. MySQL, MariaDB, and PostgreSQL are also supported. Configure via `DB_CONNECTION` in your `.env` file.

Testing uses SQLite `:memory:` with `LazilyRefreshDatabase`.

## UUID Primary Keys

All business models use UUIDs instead of auto-incrementing integers. Most models extend `BaseModel` which provides `HasUuids`, non-incrementing keys, and string key type. The `User` model extends `Authenticatable` and applies `HasUuids` directly.

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

## Mass Assignment

Models use PHP 8 `#[Fillable]` and `#[Hidden]` attributes. Older models may still use the traditional `$fillable` property.

## Foreign Keys

Always use constrained UUID foreign keys:

```php
$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
```

## Package Integrations

| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | Role-based access control with team support |
| `spatie/laravel-medialibrary` | File attachments (used by School and Submission models) |
| `spatie/laravel-activitylog` | Model change tracking and audit trail |
| `spatie/laravel-model-status` | Polymorphic status tracking (used on User model) |
| `spatie/laravel-honeypot` | Spam protection for forms |

## Known Config Issue

See [Known Issues](known-issues.md) for the known `config/activitylog.php` configuration issue where the activity model reference points to the default Spatie class instead of the custom `App\Models\ActivityLog`.
