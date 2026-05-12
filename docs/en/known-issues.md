# Known Issues

## ActivityLog Model Disconnected

`config/activitylog.php` references `Spatie\Activitylog\Models\Activity` instead of `App\Models\ActivityLog`. This means the custom scopes (`forUser`, `forSubject`, `ofAction`, etc.) defined on `ActivityLog` are not available through the standard `activity()` pipeline.

**Fix**: Change `'activity_model'` in `config/activitylog.php` to `App\Models\ActivityLog::class`.

## Unused Package

`spatie/laravel-model-states` is installed but not used anywhere in `app/`. State machine behavior is handled through enums and the Entity pattern. Consider removing the dependency.

## Duplicate Notification Classes

Root-level notification classes duplicate domain-scoped ones:

- `App\Notifications\JobFailedNotification` vs `App\Notifications\Document\JobFailedNotification`
- `App\Notifications\TestMailNotification` vs `App\Notifications\User\TestMailNotification`

The root-level versions should be removed in favor of the domain-scoped ones.

## Legacy Reference

The legacy modular monolith code is preserved in `legacy/internara-modular-monolith/` for reference only. It may contain patterns that still need refactoring.
