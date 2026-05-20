# Database Migrations

## What It Enforces

Migrations use UUID primary keys, anonymous classes, and constrained foreign keys via `foreignUuid()->constrained()`. Indexes are added in the same migration as the table creation. Names follow Artisan conventions.

## Why It Matters

UUID primary keys avoid sequential ID exposure and simplify data merging across environments. Anonymous migrations prevent class name conflicts. Constrained foreign keys ensure referential integrity with consistent naming. Adding indexes at creation time prevents the "forgot to index" performance issues that appear in production.

## When It Applies

Every migration follows:
- Primary keys: `$table->uuid('id')->primary()`
- Foreign keys: `$table->foreignUuid('col_id')->constrained()->cascadeOnDelete()`
- Anonymous migration classes (no class name, extend Migration)
- Indexes on frequently queried columns added immediately after the column
- Always include `timestamps()`
- One concern per migration (DDL and DML are separate migrations)

Migration naming: `create_{table}_table` for new tables, `add_{column}_to_{table}_table` for additions, `modify_{column}_on_{table}_table` for modifications.

Exceptions: The connection depends on the environment — SQLite (default/testing in-memory), MySQL 8+, MariaDB, or PostgreSQL 14+.
