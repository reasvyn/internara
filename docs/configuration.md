# Configuration

## Three-Tier System

| Tier | Purpose | Source | Access |
|---|---|---|---|
| `config()` | Infrastructure defaults | `config/*.php`, `.env` | `config('file.key')` |
| `setting()` | Dynamic business rules | `settings` table (cached) | `setting('key')` |
| `app_info()` | App metadata (SSoT) | `composer.json` | `app_info('key')` |

## Decision Matrix

| Use case | Tier |
|---|---|
| Database credentials | `config()` |
| Site logo, title, colors | `setting()` |
| Attendance thresholds | `setting()` |
| App version, author | `app_info()` |
| Debug mode | `config()` |

## Settings Resolution

`App\Support\Settings` resolves values through a chain:

1. Runtime overrides (testing)
2. `App\Support\AppInfo` metadata (composer.json)
3. Database (cached forever)
4. Laravel config fallback
5. Provided default

```php
// Read (auto-cached)
setting('app_name');

// Write (action-based)
app(SetSettingAction::class)->execute('app_name', 'Internara');

// Invalidate
Settings::forget('app_name');
```

## Performance

- Database settings cached forever until explicitly invalidated
- `Settings::forget($key)` clears the key, its group cache, and the `all` cache
- `app_info()` values are memoized per request
