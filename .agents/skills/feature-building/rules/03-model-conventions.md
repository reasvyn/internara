# Model Conventions

## What It Enforces

Models handle data access only: relationships, scopes, casts, and the entity bridge. Business models
extend `BaseModel` (UUID primary keys). The User model extends `Illuminate\Foundation\Auth\User`
directly. Mass assignment uses the `#[Fillable]` attribute.

## Why It Matters

Consistent model conventions ensure every Model in the codebase follows the same patterns. UUID
primary keys are used across all business tables. The `#[Fillable]` attribute is preferred over the
`$fillable` property for modern Laravel conventions. The `casts()` method is preferred over the
`$casts` property.

## When It Applies

Every Model creation follows these rules:

- Business models extend `App\Core\Models\BaseModel` (UUID PK)
- User extends `Illuminate\Foundation\Auth\User` (applies `HasUuids` independently)
- Mass assignment uses `#[Fillable([...])]` attribute
- Hidden fields use `#[Hidden([...])]` attribute
- Factory uses `HasFactory` trait with `newFactory()` method
- All timestamps included
- Entity bridge via `as{EntityName()}()` accessor
- Relationships use singular names for BelongsTo/HasOne, plural for HasMany/BelongsToMany

Media-related methods go on the Model: `registerMediaCollections()`, `registerMediaConversions()`.

Exceptions: The User model does not extend BaseModel because it extends the framework's
Authentication base class. All other module models extend BaseModel.
