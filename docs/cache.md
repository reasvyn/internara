# Cache

| Setting | Production | Testing |
|---|---|---|
| Default store | `database` | `array` |
| Prefix | Auto-generated from app name | — |

Tables: `cache`, `cache_locks`. Configuration is in `config/cache.php`.

## Usage

- **Settings** — cached forever via `Cache::rememberForever()`. Invalidated automatically when settings change.
- **Dashboard stats** — cached with a time-to-live, manually invalidated.
- **User activity** — per-request cache with short-lived keys.

## Invalidation

- Settings: cleared automatically when updated via `SetSettingAction`
- Full flush: `php artisan cache:clear`
