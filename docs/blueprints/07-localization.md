# Blueprint 07: Localization & Multi-Language

## Supported Languages

| Locale | Language | Status |
|---|---|---|
| `en` | English | ✅ Complete |
| `id` | Indonesian | ✅ Complete |

## How Localization Works

Internara uses Laravel's built-in localization system. Translation files are
stored in two formats:

1. **PHP files** — `lang/{locale}/{domain}.php` for structured translations.
   Keys follow `{domain}.{key}` dot notation: `auth.failed`, `validation.required`.
2. **JSON files** — `lang/{locale}.json` for short strings and UI labels.

The active locale is determined by:
1. User preference (stored in session, set by `SetLocaleMiddleware`)
2. `APP_LOCALE` environment variable (fallback)
3. Browser `Accept-Language` header (last resort)

## Translation File Organization

Translation files mirror the domain structure:

```
lang/
├── en/
│   ├── auth.php
│   ├── validation.php
│   ├── setup.php
│   ├── evaluation.php
│   └── registration.php
├── id/
│   ├── auth.php
│   ├── validation.php
│   ├── setup.php
│   ├── evaluation.php
│   └── registration.php
├── en.json
└── id.json
```

## Adding a New Language

1. Create `lang/{locale}/` directory
2. Copy existing translation files from `lang/en/`
3. Translate string values (keep keys unchanged)
4. Set `APP_LOCALE` and `APP_FALLBACK_LOCALE` in `.env`

```bash
# Example: adding French
mkdir -p lang/fr
cp lang/en/*.php lang/fr/
# Now translate lang/fr/*.php
```

## Enums with Labels

All enums implement `LabelEnum` with a `label()` method. Labels use the
`__()` helper for translation when applicable:

```php
case PENDING = 'pending';

public function label(): string
{
    return __('registration.status.pending');
}
```

## UI Localization

- All user-facing strings use `__()` helper
- `strftime` and `Carbon` use the active locale for date formatting
- Number formatting uses PHP's `NumberFormatter` with the active locale
- maryUI and daisyUI components respect the `lang` attribute on `<html>`

## References

- `config/app.php` — `locale`, `fallback_locale`, `faker_locale`
- `config/localization.php` — locale configuration
- `app/Domain/Settings/Http/Middleware/SetLocaleMiddleware.php` — locale detection
- `lang/` — translation files
- `docs/blueprints/00-blueprint-index.md` — index of all blueprints
