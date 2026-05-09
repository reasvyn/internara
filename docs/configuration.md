# Configuration

## Three-Tier System

| Tier | Purpose | Source | Access |
|---|---|---|---|
| `config()` | Infrastructure defaults | `config/*.php`, `.env` | `config('file.key')` |
| `setting()` | Dynamic business rules | `settings` table (cached forever) | `setting('key')` |
| `app_info()` | App metadata (SSoT) | `composer.json` | `app_info('key')` |

### When to Use Each

- **`config()`** — database credentials, debug mode, queue drivers
- **`setting()`** — site logo, title, colors, attendance thresholds
- **`app_info()`** — app version, author, license

## Settings API

`App\Support\Settings` resolves values through a chain: runtime overrides → AppInfo → cached database → Laravel config → default.

```php
// Read (auto-cached forever)
setting('app_name');

// Write (invalidates cache automatically)
use App\Actions\Admin\SetSettingAction;

app(SetSettingAction::class)->execute('app_name', 'Internara');

// Batch update
app(SetSettingAction::class)->executeBatch([
    'app_name' => 'Internara',
    'app_tagline' => ['value' => 'Manage Internships', 'group' => 'branding'],
]);
```

Type detection (`boolean`, `integer`, `float`, `json`, `string`) is automatic. Cache invalidation occurs via `Settings::forget($key)` — clears the key, its group, and the `all` cache.

## Key Environment Defaults

| Variable | Default |
|---|---|
| `DB_CONNECTION` | `sqlite` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `FILESYSTEM_DISK` | `local` |
| `QUEUE_CONNECTION` | `database` |