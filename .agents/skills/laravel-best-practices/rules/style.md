# Code Style & Conventions

## What It Enforces

Consistent naming conventions across the project: singular Models, plural tables, snake_case
columns, camelCase methods/variables, kebab-case views. Shorter Laravel helper syntax over verbose
alternatives. Laravel helpers over raw PHP for strings, arrays, and numbers.
`declare(strict_types=1)` on every PHP file. No inline JS/CSS in Blade.

## Why It Matters

Consistent naming makes the codebase predictable — a developer can guess the table name from the
Model name, the route name from the resource, and the view file name from the component. Shorter
helper syntax (`session('cart')` over `Session::get('cart')`, `now()` over `Carbon::now()`) reduces
visual noise. Laravel helpers handle edge cases (UTF-8, locale formatting) that raw PHP functions
miss.

## When It Applies

Every PHP file must begin with `declare(strict_types=1)`. Naming conventions:

- Controllers: singular (`ArticleController`)
- Models: singular (`User`)
- Tables: plural snake_case (`article_comments`)
- Columns: snake_case (`meta_title`)
- Foreign keys: singular + `_id` (`article_id`)
- Routes: plural (`articles/1`)
- Variables: camelCase (`$activeUsers`)
- Views: kebab-case (`show-filtered.blade.php`)
- Actions: VerbNounAction (`CreateAcademicYearAction`)

Use `Str::`, `Arr::`, `Number::` helpers over raw PHP. Use `latest()`, `oldest()`, `value()` over
manual equivalents. Use comments sparingly — prefer descriptive method names.

Formatting: run `vendor/bin/pint --format agent` before finalizing.

Exceptions: Inline CSS is acceptable for experimental/styling purposes in development; all
production code follows the convention.
