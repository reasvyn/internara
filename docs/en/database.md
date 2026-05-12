# Database

## Connection
The default database is SQLite. MySQL, MariaDB, and PostgreSQL are also supported. Configure via `DB_CONNECTION` in your `.env` file.
Testing uses SQLite `:memory:` with `LazilyRefreshDatabase`.

## UUID Primary Keys
All business models use UUIDs instead of auto-incrementing integers. Most models extend `BaseModel` which provides `HasUuids`, non-incrementing keys, and string key type. The `User` model extends `Authenticatable` and applies `HasUuids` directly.

## Mass Assignment
Models use PHP 8 `#[Fillable]` and `#[Hidden]` attributes. Older models may still use the traditional `$fillable` property.

## Foreign Keys
Always use constrained UUID foreign keys: `$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();`

## Package Integrations
| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | Role-based access control |
| `spatie/laravel-medialibrary` | File attachments (User avatar, School logo, Document files, RegistrationDocument uploads, Submission files) |
| `spatie/laravel-activitylog` | Model change tracking and audit trail |
| `spatie/laravel-model-status` | Polymorphic status tracking (used on Registration model) |
| `spatie/laravel-honeypot` | Spam protection for forms |
