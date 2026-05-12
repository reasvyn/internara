# Model Responsibilities

Models handle data access only — relationships, scopes, attributes, and the entity bridge.

## Structure

```php
#[Fillable(['name', 'email', 'password'])]
class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use HasFactory, HasRoles, HasStatuses, HasUuids, InteractsWithMedia, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─── Relationships ───────────────────────────
    public function profile(): HasOne { ... }
    public function mentees(): HasMany { ... }
    public function roles(): BelongsToMany { ... }

    // ─── Scopes ─────────────────────────────────
    public function scopeActive(Builder $query): Builder { ... }
    public function scopeLocked(Builder $query): Builder { ... }

    // ─── Entity Bridge ──────────────────────────
    public function asApprentice(): Apprentice
    {
        return Apprentice::fromModel($this);
    }

    // ─── Media ──────────────────────────────────
    public function registerMediaCollections(): void { ... }
    public function registerMediaConversions(?Media $media = null): void { ... }
}
```

## Allowed in Models

| Concern | Example |
|---|---|
| **Relationships** | `hasMany`, `belongsTo`, `belongsToMany` |
| **Scopes** | `scopeActive()`, `scopeByRole()` |
| **Casts** | `protected function casts(): array` |
| **Accessors** | `getXAttribute()` |
| **Appends** | `#[Appends(['logo_url'])]` |
| **Entity bridge** | `as{EntityName}()` |
| **Media collections** | `registerMediaCollections()` |
| **Media conversions** | `registerMediaConversions()` |
| **Factory** | `HasFactory` trait, `newFactory()` method |
| **Simple helpers** | `initials()`, `avatarUrl()` — only if pure string/formatting |

## NOT Allowed in Models

```php
// ❌ Business rules in Model
public function canLogin(): bool  // → Entity
public function isActive(): bool  // → Entity (if complex)
public function hasAvailableSlots(): bool  // → Entity

// ❌ Static utility methods
public static function formatSomething(...): string  // → Support
```

## Rule of Thumb

Ask: "Would this method still make sense if I swapped the database for an API?"

- If YES (it's about data): keep in Model
- If NO (it's about business logic): move to Entity

```
Data question: "How many students are in this department?"
    → Model scope or relationship

Business question: "Can this student submit a logbook entry?"
    → Entity method (MenteeState::canSubmitLogbook())
```
