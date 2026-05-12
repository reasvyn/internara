# Caching & Settings

## Three-Tier Configuration

| Source | Purpose | Access |
|---|---|---|
| `config()` | Infrastructure defaults (DB, queue, drivers) | `config('file.key')` |
| `setting()` | Dynamic business rules | `setting('key')` |
| `app_info()` | Composer.json metadata | `app_info('key')` |

## Settings Resolution Chain

```
Runtime Overrides → AppInfo → Settings DB (cached) → Laravel Config → Default
```

## Caching Behavior

- Settings are cached forever via `Cache::rememberForever()`
- Individual keys: `settings.{key}`
- All settings: `settings.all`
- Group queries: `settings.group.{name}`

## Cache Invalidation

- **Single setting update**: clears `{key}`, group, and `all` caches
- **Group update**: clears all keys in the group + group + all caches
- **Full flush**: `php artisan cache:clear`

## Writing Settings

Always use `SetSettingAction` — never write to the `settings` table directly:

```php
app(SetSettingAction::class)->execute('key', 'value');
app(SetSettingAction::class)->executeBatch(['key1' => 'value1', 'key2' => 'value2']);
```

## Bypassing Cache

```php
setting('key', skipCache: true);
```
