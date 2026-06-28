# Localization — Translation & Locale System

> **Last updated:** 2026-06-10
> **Changes:** sync — initial metadata sync with new format

## Description
Internara is designed for a global audience with Indonesian vocational education as its primary design reference. The interface and data model use international terminology, and the application ships with two complete language packs.

**Community contributions for additional languages are welcome.**

---


## Supported Languages

| Locale | Language   | Status                |
| ------ | ---------- | --------------------- |
| `en`   | English    | ✅ Complete (default) |
| `id`   | Indonesian | ✅ Complete           |

---

## How Localization Works

Internara uses Laravel's built-in localization system. Translation files are stored in two formats:

1. **PHP files** — `lang/{locale}/{module}.php` for structured translations. Keys follow `{module}.{key}` dot notation. Example: `auth.failed`, `validation.required`, `registration.status.pending`.

2. **JSON files** — `lang/{locale}.json` for short strings and UI labels that don't belong to a specific module.

### Locale Resolution

The active locale is determined by this priority order:

1. **User preference** — stored in session, set by the language switcher
2. **`APP_LOCALE`** environment variable (fallback default)
3. **Browser `Accept-Language` header** (last resort)

Users can switch languages at any time using the language switcher in the interface. Their preference persists across sessions.

---

## Translation File Structure

Translation files mirror the application's module structure:

```
lang/
├── en/
│   ├── auth.php
│   ├── validation.php
│   ├── setup.php
│   ├── evaluation.php
│   ├── registration.php
│   ├── internship.php
│   ├── attendance.php
│   └── ... (one file per module that needs translations)
├── id/
│   ├── auth.php
│   ├── validation.php
│   ├── setup.php
│   ├── evaluation.php
│   ├── registration.php
│   ├── internship.php
│   ├── attendance.php
│   └── ...
├── en.json
└── id.json
```

### PHP Translation Files

Each PHP file returns an array of key-value pairs:

```php
// lang/id/attendance.php
return [
    'clock_in' => 'Jam Masuk',
    'clock_out' => 'Jam Keluar',
    'absent' => 'Tidak Hadir',
    'status' => [
        'present' => 'Hadir',
        'late' => 'Terlambat',
        'excused' => 'Izin',
    ],
];
```

Usage in code:

```php
__('attendance.clock_in');
__('attendance.status.present');
```

### JSON Translation Files

JSON files contain short strings and UI labels. The key is the English string, and the value is the translation:

```json
{
    "Save": "Simpan",
    "Cancel": "Batal",
    "Search...": "Cari...",
    "No records found.": "Tidak ada data ditemukan."
}
```

Usage in code:

```php
__('Save');
```

---

## Enum Labels

All enums implement the `LabelEnum` contract with a `label()` method. Labels use the `__()` helper for translation:

```php
enum AttendanceStatus: string implements LabelEnum
{
    case PRESENT = 'present';
    case LATE = 'late';
    case ABSENT = 'absent';

    public function label(): string
    {
        return __("attendance.status.{$this->value}");
    }
}
```

State machine enums additionally implement `StatusEnum` for transition validation.

---

## UI Localization

- All user-facing strings use the `__()` helper
- Date formatting uses `Carbon::setLocale()` with the active locale
- maryUI and daisyUI components respect the `lang` attribute on `<html>`
- The language switcher is available in the application header

---

## Adding a New Language (Open Contribution)

### Step 1: Create Language Directory

```bash
# Example: adding French
mkdir -p lang/fr
```

### Step 2: Copy Existing Translations

```bash
cp lang/en/*.php lang/fr/
cp lang/en.json lang/fr.json
```

### Step 3: Translate String Values

Translate the **values** in each file. Keep the **keys** unchanged — they must match the English version exactly.

```php
// Translated (French)
return [
    'clock_in' => "Pointer à l'arrivée",  // ← only values change
    'clock_out' => 'Pointer au départ',
    'status' => [
        'present' => 'Présent',
    ],
];
```

### Step 4: Update Config

Add the new locale to `config/app.php`:

```php
'available_locales' => [
    'en' => 'English',
    'id' => 'Bahasa Indonesia',
    'fr' => 'Français',  // ← add your language
],
```

### Step 5: Submit Contribution

Open a pull request with:
- The new `lang/{locale}/` directory
- Updated `config/app.php` if locale configuration needs changes
- No changes to PHP or JavaScript code — translations are purely data files

### Translation Checklist

Before submitting, verify:

- [ ] All PHP files from `lang/en/` are present in your locale directory
- [ ] `lang/{locale}.json` exists with UI labels translated
- [ ] String keys are identical to the English version
- [ ] Date and number formats work correctly for the target locale
- [ ] Right-to-left languages may need additional CSS adjustments

---

## References

- `config/app.php` — `locale`, `fallback_locale`, `available_locales`
- `config/localization.php` — locale resolution configuration
- `app/Settings/Locale/Http/Middleware/SetLocaleMiddleware.php` — locale detection
- `app/Settings/Livewire/LangSwitcher.php` — UI language toggle
- `lang/` — translation files
- `app/Core/Contracts/LabelEnum.php` — enum label contract
- `app/Core/Contracts/StatusEnum.php` — state machine enum contract
