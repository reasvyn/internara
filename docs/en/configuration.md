# Configuration

## Three-Tier System

Configuration values come from three sources, each with a specific purpose:

| Source | Purpose | Access |
|---|---|---|
| `config()` | Infrastructure defaults (database, queue, drivers) | `config('file.key')` |
| `setting()` | Dynamic business rules (site name, colors, thresholds) | `setting('key')` |
| `app_info()` | App metadata from composer.json (version, author) | `app_info('key')` |

### When to use each

- **`config()`** — database credentials, debug mode, queue drivers, mail settings
- **`setting()`** — site logo, app title, colors, attendance thresholds, anything changeable at runtime
- **`app_info()`** — app version, author name, license, support email

## Settings API

The `Settings` class resolves values through a chain: runtime overrides → AppInfo metadata → cached database → Laravel config → default value.

```php
// Read a setting (auto-cached forever)
setting('app_name');

// Write settings
use App\Actions\Admin\SetSettingAction;

app(SetSettingAction::class)->execute('app_name', 'Internara');

// Bulk update
app(SetSettingAction::class)->executeBatch([
    'app_name' => 'Internara',
    'app_tagline' => ['value' => 'Manage Internships', 'group' => 'branding'],
]);
```

You can also read settings directly through the `Settings` class for more advanced use:

```php
// Get all settings
Settings::all();

// Get a group of settings
Settings::group('branding');

// Bypass cache
setting('app_name', skipCache: true);
```

Type detection (boolean, integer, float, json, string) is automatic. Cache invalidation happens automatically when settings are updated.

## Environment Defaults

See `.env.example` for the full list of environment variables with their defaults. Key drivers default to database-backed storage for zero-dependency setups.
