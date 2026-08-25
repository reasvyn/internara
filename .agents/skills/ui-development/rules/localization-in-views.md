# Localization in Views — Bilingual Display Text

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

All user-facing strings in Blade views MUST use `__()` for EN/ID bilingual support
(`docs/conventions.md` §14). A hardcoded string splits the app into English-only islands and breaks
the Indonesian locale the school audience depends on.

---

## Intent

Every visible text in a Blade view goes through `__('key')`. This covers button labels, modal
titles, table headers, and placeholder text. Keys follow a pattern per scope (module/submodule/
common), every key exists in both `lang/en/` and `lang/id/`, and dates render via localized Carbon
formatting.

## Rationale — What Fails Without It

- **Hardcoded English** renders English-only for Indonesian users and defeats the whole localization
  layer (D3). The same text typed verbatim in two views *drifts*: "Save" in one and "Save changes" in
  another becomes visible inconsistency users notice.
- **Key missing in `lang/id/`** renders... the key itself (`intern.name`) or the `en` fallback — a
  raw artifact on screen. Both locale files must contain the key.
- **Unlocalized dates** (`date('Y-m-d')` or Carbon's default locale) format in English month names.
  The school calendar is Indonesian; `Carbon::locale()` must match the app locale.
- **Wrong `lang` attribute** on `<html>` tells screen readers the page is English — a localizability
  bug with an accessibility cost.

## How to Apply

### Rules

- All visible text in Blade views uses `{{ __('key') }}` — no hardcoded English.
- Button labels, modal titles, table headers: all via `__()`.
- Date formatting: `Carbon::locale(app()->getLocale())->isoFormat(...)`.
- HTML `lang` attribute set in `base.blade.php`.
- Every key must exist in both `lang/en/` and `lang/id/`.

```blade
<x-mary-button>{{ __('common.actions.save') }}</x-mary-button>
<h1>{{ __('intern.list_title') }}</h1>
<p>{{ Carbon::parse($date)->locale(app()->getLocale())->isoFormat('D MMMM YYYY') }}</p>
```

### Key patterns (per scope)

| Scope           | Pattern           | Example                          |
| --------------- | ----------------- | -------------------------------- |
| Module-level    | `{module}.key`    | `__('enrollment.register')`      |
| Submodule-level | `{submodule}.key` | `__('internship.create_success')`|
| Shared          | `common.key`      | `__('common.actions.save')`      |

### Implementation discipline

- Add each new key to **both** `lang/en/{module}.php` and `lang/id/{module}.php` in the same change —
  never ship with one locale missing.
- For status labels reuse `LabelEnum::label()` (calls `__()` internally) instead of translating in
  the view — one label source, not a per-view copy.

## Anti-Patterns & Pitfalls

- `{{ 'Save' }}` / `{{ __('Save') }}` with a sentence as the key — keys are identifiers
  (`module.noun.key`), not sentences.
- Adding a key only to `lang/en/` "temporarily" — it renders raw in `id`.
- `date('l, d F Y')` in Blade — English-name dates; use localized Carbon.
- Translating a status inside the view when the enum already exposes `label()` — two label sources.
- Forgetting `lang="id"` (or leaving it unset) in `base.blade.php` — assistive tech mispronounces.

## Verification

- Grep views for hardcoded visible strings (`>text<`, `placeholder="..."` literal English) — none
  remain.
- Every new `__()` key exists in both `lang/en/` and `lang/id/`
  (`vendor/bin/pint --dirty --test` + `php artisan tinker --execute="echo __('key');"`).
- `base.blade.php` sets `lang` correctly; `npm run build` clean.