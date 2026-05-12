# Database & Migrations

## Connection

SQLite (default), MySQL 8+, MariaDB, PostgreSQL 14+. Testing uses `:memory:` SQLite.

## Primary Keys

All business tables use UUID primary keys:
```php
$table->uuid('id')->primary();
```

## Foreign Keys

Always use constrained UUID foreign keys:
```php
$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
```

## Migration Structure

Use anonymous classes. File may omit `declare(strict_types=1)`.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

## Naming

| Type | Pattern | Example |
|---|---|---|
| Create | `YYYY_MM_DD_HHMMSS_create_{table}_table` | `2026_04_29_092750_create_users_table` |
| Modify | `YYYY_MM_DD_HHMMSS_add_{column}_to_{table}_table` | `2026_05_08_000005_add_created_by_to_assignments_table` |

## Key Rules

- All primary keys are UUIDs
- Foreign keys use `foreignUuid()` with `constrained()`
- `->nullable()` immediately after the type
- `->index()` for frequently queried columns
- Always include `->timestamps()`
