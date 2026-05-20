# Migrations

## What It Enforces

Migrations are generated via Artisan (not created manually). Foreign keys use `constrained()` for automatic naming and referential integrity. Once deployed, migrations are immutable — always create new migrations to change tables. Indexes are added in the same migration as table creation. Each migration has one concern.

## Why It Matters

Artisan-generated migrations have correct timestamps and naming. `constrained()` eliminates guesswork in FK naming and ensures referential integrity. Immutability of deployed migrations means you never have to guess whether a migration was modified after running. Adding indexes at creation time prevents "deployed without indexes" performance issues.

## When It Applies

Every migration should:
- Be generated with `php artisan make:migration`
- Use `constrained()` for foreign keys with explicit table name when non-standard
- Add indexes in the same migration as the column
- Implement a reversible `down()` method
- Separate DDL (schema changes) from DML (data manipulation) into different migrations
- Mirror database defaults in Model `$attributes` array

Creating a migration: `php artisan make:migration create_{table}_table` or `php artisan make:migration add_{column}_to_{table}_table`.

Exceptions: Intentionally irreversible migrations (destructive backfills) should document this clearly. Their `down()` may throw an exception explaining the fix-forward approach.
