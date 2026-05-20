# Translations

## What It Enforces

Every user-facing string must have translations in both English (`lang/en/{domain}.php`) and Indonesian (`lang/id/{domain}.php`). Translation keys use dot notation with `snake_case` and are referenced via `__('domain.key')`.

## Why It Matters

Bilingual support is a hard requirement — every user-facing string must be available in both languages. A consistent key naming convention makes translations predictable: `domain.key`, `domain.subkey.key`. Grouping related keys (`fields.name`, `fields.start_date`) keeps files organized.

## When It Applies

Every new feature with user-facing strings must:
- Create or update `lang/en/{domain}.php` with English translations
- Create or update `lang/id/{domain}.php` with Indonesian translations
- Use `__('domain.key')` in all PHP and Blade contexts
- Use `snake_case` for multi-word keys
- Group related keys: `fields.name`, `actions.create`, `messages.created`

Key naming patterns:
- Labels: `{domain}.{field}` or `{domain}.fields.{field}`
- Actions: `{domain}.created`, `{domain}.deleted`, `{domain}.saved`
- Errors: `{domain}.cannot_delete_active`, `{domain}.not_found`
- UI elements: `{domain}.title`, `{domain}.subtitle`, `{domain}.search_placeholder`

Exceptions: System-level log messages that are never shown to users do not need translations. Debug and internal messages are always in English.
