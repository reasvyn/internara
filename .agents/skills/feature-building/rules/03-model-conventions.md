# Model Conventions

Models handle data access only: relationships, scopes, casts, and the entity bridge.

## Location

All models live directly in `app/Models/` with no sub-namespace.

## Base Class

Business models extend `BaseModel` (UUIDs, non-incrementing string keys).
`User` extends `Authenticatable` directly (applies `HasUuids` on its own).

## Structure

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\User\Apprentice;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'email', 'password'])]
class User extends Authenticatable
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    // ─── Relationships ─────────────────────
    public function profile(): HasOne { ... }

    // ─── Scopes ────────────────────────────
    public function scopeActive(Builder $query): Builder { ... }

    // ─── Entity Bridge ─────────────────────
    public function asApprentice(): Apprentice
    {
        return Apprentice::fromModel($this);
    }
}
```

## Rules

| Concern | Implementation |
|---|---|
| Mass assignment | `#[Fillable([...])]` attribute (not `$fillable`) |
| Hidden fields | `#[Hidden([...])]` attribute on `User` |
| Factories | `use HasFactory` (convention-based resolution) |
| Primary keys | UUID via `HasUuids` or `BaseModel` |
| Timestamps | Always include `->timestamps()` in migrations |
| Entity bridge | Named `as{EntityName()}()` method |
| Relationships | Singular for BelongsTo/HasOne, plural for HasMany/BelongsToMany |
