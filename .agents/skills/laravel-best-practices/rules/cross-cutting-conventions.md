# Cross-Cutting Conventions — Uploads, Localization & Pitfalls

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Three conventions span every feature and every layer: file uploads go through Spatie MediaLibrary
only, user-facing strings are bilingual via `__()`, and certain stock-Laravel habits are forbidden.
They are cheap to follow and expensive to retrofit — enforce them at code-review time.

---

## Intent

All file storage uses Spatie MediaLibrary (never `Storage::put()`); all display text is localized
with `__()` keys present in both `lang/en/` and `lang/id/`; and the classic Laravel anti-patterns
(direct Model mutation in Livewire, `app()->make()`, inline cache keys, missing strict types, skipped
lang files) are structurally prevented.

## Rationale — What Fails Without It

- **`Storage::put()` bypasses MediaLibrary** — no collection validation (size/MIME), no conversions,
  no model link, no global media dashboard. Uploads recorded two ways become two sources of truth and
  cannot be served consistently (see `medialibrary-development`).
- **Hardcoded display text breaks `lang/id`** — the app is bilingual; a hardcoded string renders
  English-only in the Indonesian locale and the same text typed in two places drifts (D3).
- **`Model::create()` in Livewire** skips the Command Action's transaction + audit log and mutates
  directly from the presentation layer (C1).
- **`app()->make()` / `new Action()`** hides dependencies, resists testability, and bypasses method
  injection the framework otherwise wires (C2).
- **Inline cache keys** cannot be flushed or shared (C4 — see the data/cache rule).
- **Missing `declare(strict_types=1)`** lets PHP silently coerce scalar types — a stray `"5"` for an
  `int` sails past type hints and corrupts business logic (D1).
- **Skipping lang files** produces a raw key on screen — `__('intern.name')` with no `lang/en`
  entry renders `intern.name` literally.

## How to Apply

### File uploads — MediaLibrary only

```php
// In a Command Action (never Livewire):
$user->addMedia($uploadedFile)->toMediaCollection('avatar');
```

- Never `Storage::put()`/`Storage::store()` for user uploads.
- Load `medialibrary-development` for collection/conversion specifics.

### Localization — `__('module.key')` both locales

- Every user-facing string uses `__('{module}.{key}')`.
- Add the key to both `lang/en/{module}.php` and `lang/id/{module}.php`.

```blade
{{ __('intern.name') }}
```

### Structural guards (stock habits that are violations)

| Habit                                  | Correct Internara form                          | Violation |
| -------------------------------------- | ----------------------------------------------- | --------- |
| `Model::create()` in a component        | Command Action via method injection             | C1        |
| `app()->make(X::class)`                 | Constructor (Services) / method param (Livewire)| C2        |
| `'some_cache_key'` inline string        | Register in `config/cache-keys.php`             | C4        |
| PHP file without `declare(strict_types=1)` | First line after `<?php`                     | D1        |
| `__()` referenced only in `lang/en/`    | Key in both `lang/en/` and `lang/id/`           | D3        |

## Anti-Patterns & Pitfalls

- `Storage::put($path, $file)` in an Action "because it's faster" — MediaLibrary exists for a reason;
  route every upload through it.
- Using `__()` inside a `{{ }}` echo with hardcoded fallback like `__('x') ?? 'Fallback'` — the
  fallback re-introduces a hardcoded string and hides missing keys.
- Only adding a translation key to `lang/id/` and not `lang/en/` (or vice versa) — a mismatch renders
  the raw key in the untouched locale.
- Adding `declare(strict_types=1)` to migrations or config files — those are exempt in Internara,
  and forcing it there can break artisan tooling assumptions.

## Verification

- Grep for `Storage::put|Storage::store|Storage::disk` in feature code — only MediaLibrary calls.
- For every new `__()` key: entry present in both `lang/en/` and `lang/id/` (`vendor/bin/pint
  --dirty --test` + `php artisan tinker --execute="echo __('key');"`).
- `python3 scripts/scan_conventions.py` (strict types, debug) and `python3 scripts/scan_violations.py`
  (C1/C2/C4) clean.
- `docs/conventions.md` is the authoritative source for all cross-cutting conventions.