# UI/UX Guidelines

> **Last updated:** 2026-06-10

## Localization

### Translation File Organization

Translation files live in `lang/{locale}/` — one file per module or submodule, flat (no subdirectories). Every module has a corresponding lang file named after the module or its submodule.

See [Conventions: Localization](conventions.md#24-localization) for the full file index and technical rules.

### English Strings — Writing Style

All English translation strings follow these principles:

- **Sentence case** for titles and headers: "Create super administrator", not "Create Super Administrator".
- **Sentence case** for buttons and actions: "Save changes", "Cancel", "Delete account".
- **Descriptive error messages**: "A super administrator already exists.", not "Already exists.".
- **Use colon for field labels in forms**: "Email:", "Password:". The colon is part of the label string.
- **Avoid exclamation marks** in UI text unless conveying urgency or success (e.g., "Account activated successfully!").
- **Parameters** use `:param` syntax and are always lowercase:
  ```php
  __('Welcome, :name!')
  __(':count records updated.')
  ```

### Adding a Translation Key

1. Identify the correct module file. Keys for a feature belong to the feature's module.
2. Add the English string to `lang/en/{file}.php`.
3. Add the Indonesian translation to `lang/id/{file}.php` (same key name).
4. Use the key via `__('file.key_name')` in code.

### Key Naming Guidelines

- Use `snake_case` keys.
- Group related keys under a sub-key (e.g., `create.*`, `actions.*`, `status.*`).
- Prefix subdomain keys when they coexist in a module file:
  ```php
  // assessment.php — presentation features
  'presentation_title' => 'Presentations'
  'presentation_schedule' => 'Schedule'
  ```
- Keep keys descriptive but concise: `user_not_found` over `the_user_was_not_found_in_the_database`.

### Pluralization

Laravel's pluralization is not used. Use explicit parameter substitution:

```php
// ✅ Correct
__(':count records updated.', ['count' => $count])

// ❌ Avoid
trans_choice('records.updated', $count)
```

### Common Patterns

| Context | Example |
|---|---|
| Success message | `'Internship created successfully.'` |
| Error message | `'An unexpected error occurred.'` |
| Confirmation prompt | `'Are you sure you want to delete this item?'` |
| Button label | `'Save changes'`, `'Cancel'`, `'Delete'` |
| Empty state | `'No internships found.'` |
| Placeholder | `'Search by name or email...'` |
| Tooltip | `'Click to copy recovery key'` |

### Accessibility

- All user-facing labels must be translatable via `__()`. Hardcoded English text in Blade templates
  is not allowed unless it is structural markup with no semantic meaning (e.g., an icon's `title`
  attribute must use `__()`).
- Screen reader text: use `class="sr-only"` with a translated label for icon-only buttons:
  ```blade
  <button aria-label="{{ __('common.actions.edit') }}">
      <x-mary-icon name="o-pencil" class="size-4" />
  </button>
  ```
