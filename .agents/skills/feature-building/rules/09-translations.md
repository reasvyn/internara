# Translations

## Dual Language

Every user-facing string must have translations in both languages:

| File | Language |
|---|---|
| `lang/en/{domain}.php` | English |
| `lang/id/{domain}.php` | Indonesian |

## Usage

```php
// In PHP/Blade
__('domain.key')
__('domain.subkey.key')
__('domain.key', ['param' => $value])

// With locale override
__('domain.key', [], 'en')
__('domain.key', [], 'id')
```

## File Convention

One file per domain: `lang/{lang}/{domain}.php`

```php
<?php

declare(strict_types=1);

return [
    'title' => 'Page Title',
    'subtitle' => 'Page description.',
    'create' => 'Create New',
    'save_success' => 'Saved successfully.',
    'delete_success' => 'Deleted successfully.',
];
```

## Key Naming

- Dot notation: `domain.key`, `domain.subkey.key`
- snake_case for multi-word keys: `save_success`, `delete_blocked`
- Group related keys under sub-arrays: `statuses.draft`, `statuses.active`

## Existing Domain Files

| Domain | File | Key Prefix |
|---|---|---|
| School | `school.php` | `school.*` |
| Department | `department.php` | `department.*` |
| Internship | `internship.php` | `internship.*` |
| Company | `company.php` | `company.*` |
| User | `user.php` | `user.admin.*`, `user.teacher.*` |
| Setting | `setting.php` | `setting.*` |
| Sidebar | `sidebar.php` | `sidebar.*` |
| Setup | `setup.php` | `setup.wizard.*`, `setup.cli.*` |
| Common | `common.php` | `common.actions.*`, `common.status.*` |
| Profile | `profile.php` | `profile.*` |
| Academic Year | `academic_year.php` | `academic_year.*` |
