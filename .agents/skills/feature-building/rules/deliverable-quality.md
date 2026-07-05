# Deliverable Quality — Measurable Completion Criteria

Checklist to ensure every deliverable is ready for review and merge.

## File Completeness

Every new file must pass:

- [ ] `declare(strict_types=1)` (PHP files, except migrations/config)
- [ ] No `dd/dump/ray` in committed code
- [ ] Constructor property promotion: `protected readonly`
- [ ] Explicit return types on all methods
- [ ] Type hints on all parameters

## Action Quality

- [ ] Extends correct base class (Command/Read/Process)
- [ ] Single `execute()` public method
- [ ] DTO for 3+ params (not raw `array`)
- [ ] `ActionResponse` for structured feedback
- [ ] `$this->transaction()` + `$this->log()` (Command/Process)
- [ ] Business rules via Entity → `RejectedException`

## Model Quality

- [ ] Extends `BaseModel` or `BaseAuthenticatable`
- [ ] `#[Fillable]` attribute (not `$fillable`)
- [ ] `HasFactory` trait + `newFactory()` method
- [ ] Entity bridge: `as{Role}(): EntityType` if business rules exist

## Feature Completeness

- [ ] Tests: happy path + edge cases minimum
- [ ] Translations: keys in both `lang/en/` and `lang/id/`
- [ ] Routes: in correct `routes/web/{module}.php` file
- [ ] Authorization: Policy or inline `$this->authorize()`
- [ ] Cache invalidation: event-driven or explicit `Cache::forget()`
- [ ] Docs updated: `{module}.md` and/or `{module}-reference.md`

## Gate

- [ ] `php artisan test --compact` — passes
- [ ] `vendor/bin/pint --dirty --format agent` — clean
- [ ] `vendor/bin/phpstan analyse --no-progress` — passes
